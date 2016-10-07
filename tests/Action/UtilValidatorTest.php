<?php
/*
 * Fusio
 * A web-application to create dynamically RESTful APIs
 *
 * Copyright (C) 2015-2016 Christoph Kappestein <christoph.kappestein@gmail.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace Fusio\Adapter\Util\Tests\Action;

use Fusio\Adapter\Util\Action\UtilValidator;
use Fusio\Engine\Form\Builder;
use Fusio\Engine\Form\Container;
use Fusio\Engine\Model\Action;
use Fusio\Engine\Model\Connection;
use Fusio\Engine\Response;
use Fusio\Engine\ResponseInterface;
use Fusio\Engine\Test\CallbackAction;
use Fusio\Engine\Test\CallbackConnection;
use Fusio\Engine\Test\EngineTestCaseTrait;
use PSX\Framework\Test\Environment;
use PSX\Record\Record;

/**
 * UtilValidatorTest
 *
 * @author  Christoph Kappestein <christoph.kappestein@gmail.com>
 * @license http://www.gnu.org/licenses/agpl-3.0
 * @link    http://fusio-project.org
 */
class UtilValidatorTest extends \PHPUnit_Framework_TestCase
{
    use EngineTestCaseTrait;

    protected function setUp()
    {
        $connection = new Connection();
        $connection->setId(1);
        $connection->setName('foo');
        $connection->setClass(CallbackConnection::class);
        $connection->setConfig([
            'callback' => function(){
                return new \stdClass();
            },
        ]);

        $this->getConnectionRepository()->add($connection);

        $action = new Action();
        $action->setId(1);
        $action->setName('foo');
        $action->setClass(CallbackAction::class);
        $action->setConfig([
            'callback' => function(Response\FactoryInterface $response){
                return $response->build(200, [], ['id' => 1, 'title' => 'foo', 'content' => 'bar', 'date' => '2015-02-27 19:59:15']);
            },
        ]);

        $this->getActionRepository()->add($action);
    }

    public function testHandle()
    {
        $rules = <<<YAML
/~query/foo: filter.alnum(value)
/~path/bar: filter.alnum(value)
/id: database.rowExists('foo', 'fusio_user', 'id', value)
/title: filter.alnum(value)
/author/name: filter.alnum(value)
YAML;

        $parameters = $this->getParameters([
            'action' => 1,
            'rules'  => $rules,
        ]);

        $body = Record::fromArray([
            'title'  => 'foo',
            'author' => Record::fromArray([
                'name' => 'bar'
            ]),
        ]);

        $request = $this->getRequest(
            null,
            ['bar' => 'foo'],
            ['foo' => 'bar'],
            [],
            $body
        );

        $action   = $this->getActionFactory()->factory(UtilValidator::class);
        $response = $action->handle($request, $parameters, $this->getContext());

        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals([], $response->getHeaders());
        $this->assertEquals(['id' => 1, 'title' => 'foo', 'content' => 'bar', 'date' => '2015-02-27 19:59:15'], $response->getBody());
    }

    /**
     * @expectedException \PSX\Validate\ValidationException
     * @expectedExceptionMessage /bar contains an invalid value
     */
    public function testHandleInvalidPath()
    {
        $body = Record::fromArray([
            'title'  => 'foo',
            'author' => Record::fromArray([
                'name' => 'bar'
            ]),
        ]);

        $request = $this->getRequest(
            null,
            ['bar' => '!foo'],
            ['foo' => 'bar'],
            [],
            $body
        );

        $this->handle($request);
    }

    /**
     * @expectedException \PSX\Validate\ValidationException
     * @expectedExceptionMessage /foo contains an invalid value
     */
    public function testHandleInvalidQuery()
    {
        $body = Record::fromArray([
            'title'  => 'foo',
            'author' => Record::fromArray([
                'name' => 'bar'
            ]),
        ]);

        $request = $this->getRequest(
            null,
            ['bar' => 'foo'],
            ['foo' => '!bar'],
            [],
            $body
        );

        $this->handle($request);
    }

    /**
     * @expectedException \PSX\Validate\ValidationException
     * @expectedExceptionMessage /id contains an invalid value
     */
    public function testHandleInvalidBodyId()
    {
        $body = Record::fromArray([
            'id'     => 8,
            'title'  => 'foo',
            'author' => Record::fromArray([
                'name' => 'bar'
            ]),
        ]);

        $request = $this->getRequest(
            null,
            ['bar' => 'foo'],
            ['foo' => 'bar'],
            [],
            $body
        );

        $this->handle($request);
    }

    /**
     * @expectedException \PSX\Validate\ValidationException
     * @expectedExceptionMessage /title contains a custom error message
     */
    public function testHandleInvalidBodyTitle()
    {
        $body = Record::fromArray([
            'title'  => '!foo',
            'author' => Record::fromArray([
                'name' => 'bar'
            ]),
        ]);

        $request = $this->getRequest(
            null,
            ['bar' => 'foo'],
            ['foo' => 'bar'],
            [],
            $body
        );

        $this->handle($request);
    }

    /**
     * @expectedException \PSX\Validate\ValidationException
     * @expectedExceptionMessage /author/name contains an invalid value
     */
    public function testHandleInvalidBodyAuthorName()
    {
        $body = Record::fromArray([
            'title'  => 'foo',
            'author' => Record::fromArray([
                'name' => '!bar'
            ]),
        ]);

        $request = $this->getRequest(
            null,
            ['bar' => 'foo'],
            ['foo' => 'bar'],
            [],
            $body
        );

        $this->handle($request);
    }

    public function testGetForm()
    {
        $action  = $this->getActionFactory()->factory(UtilValidator::class);
        $builder = new Builder();
        $factory = $this->getFormElementFactory();

        $action->configure($builder, $factory);

        $this->assertInstanceOf(Container::class, $builder->getForm());
    }

    protected function handle($request)
    {
        $action = $this->getActionFactory()->factory(UtilValidator::class);

        $rules = <<<YAML
/~query/foo: filter.alnum(value)
/~path/bar: filter.alnum(value)
/id: database.rowExists('foo', 'fusio_user', 'id', value)
/title:
    rule: filter.alnum(value)
    message: %s contains a custom error message
/author/name: filter.alnum(value)
YAML;

        $parameters = $this->getParameters([
            'action' => 3,
            'rules'  => $rules,
        ]);

        $action->handle($request, $parameters, $this->getContext());
    }
}
