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

use Fusio\Adapter\Util\Action\UtilTransform;
use Fusio\Engine\Form\Builder;
use Fusio\Engine\Form\Container;
use Fusio\Engine\Model\Action;
use Fusio\Engine\RequestInterface;
use Fusio\Engine\Response;
use Fusio\Engine\ResponseInterface;
use Fusio\Engine\Test\CallbackAction;
use Fusio\Engine\Test\EngineTestCaseTrait;
use PSX\Framework\Test\Environment;
use PSX\Record\Record;

/**
 * UtilTransformTest
 *
 * @author  Christoph Kappestein <christoph.kappestein@gmail.com>
 * @license http://www.gnu.org/licenses/agpl-3.0
 * @link    http://fusio-project.org
 */
class UtilTransformTest extends \PHPUnit_Framework_TestCase
{
    use EngineTestCaseTrait;

    protected function setUp()
    {
        $action = new Action();
        $action->setId(1);
        $action->setName('foo');
        $action->setClass(CallbackAction::class);
        $action->setConfig([
            'callback' => function(Response\FactoryInterface $response, RequestInterface $request){
                return $response->build(200, [], $request->getBody());
            },
        ]);

        $this->getActionRepository()->add($action);
    }

    public function testHandle()
    {
        $patch = <<<JSON
[
    { "op": "test", "path": "/title", "value": "foo" },
    { "op": "remove", "path": "/id" },
    { "op": "add", "path": "/foo", "value": "bar" },
    { "op": "replace", "path": "/author/name", "value": "foo" }
]
JSON;

        $parameters = $this->getParameters([
            'action' => 1,
            'patch'  => $patch,
        ]);

        $body = Record::fromArray([
            'id'     => 1,
            'title'  => 'foo',
            'author' => Record::fromArray([
                'name' => 'bar'
            ]),
        ]);

        $action   = $this->getActionFactory()->factory(UtilTransform::class);
        $request  = $this->getRequest(null, [], [], [], $body);
        $response = $action->handle($request, $parameters, $this->getContext());

        unset($body->id);
        $body->foo = 'bar';
        $body->author->name = 'foo';

        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals([], $response->getHeaders());
        $this->assertEquals($body, $response->getBody());
    }

    public function testGetForm()
    {
        $action  = $this->getActionFactory()->factory(UtilTransform::class);
        $builder = new Builder();
        $factory = $this->getFormElementFactory();

        $action->configure($builder, $factory);

        $this->assertInstanceOf(Container::class, $builder->getForm());
    }
}
