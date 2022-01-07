<?php
/*
 * Fusio
 * A web-application to create dynamically RESTful APIs
 *
 * Copyright (C) 2015-2022 Christoph Kappestein <christoph.kappestein@gmail.com>
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

use Fusio\Adapter\Util\Action\UtilABTest;
use Fusio\Adapter\Util\Action\UtilCache;
use Fusio\Engine\Form\Builder;
use Fusio\Engine\Form\Container;
use Fusio\Engine\Model\Action;
use Fusio\Engine\Model\Connection;
use Fusio\Engine\Response;
use Fusio\Engine\Test\CallbackAction;
use Fusio\Engine\Test\CallbackConnection;
use Fusio\Engine\Test\EngineTestCaseTrait;
use PHPUnit\Framework\TestCase;
use PSX\Http\Environment\HttpResponseInterface;

/**
 * UtilABTestTest
 *
 * @author  Christoph Kappestein <christoph.kappestein@gmail.com>
 * @license http://www.gnu.org/licenses/agpl-3.0
 * @link    https://www.fusio-project.org/
 */
class UtilABTestTest extends TestCase
{
    use EngineTestCaseTrait;

    protected function setUp(): void
    {
        $action = new Action();
        $action->setId(1);
        $action->setName('a');
        $action->setClass(CallbackAction::class);
        $action->setConfig([
            'callback' => function(Response\FactoryInterface $response){
                return $response->build(200, [], ['a' => true]);
            },
        ]);

        $this->getActionRepository()->add($action);

        $action = new Action();
        $action->setId(2);
        $action->setName('b');
        $action->setClass(CallbackAction::class);
        $action->setConfig([
            'callback' => function(Response\FactoryInterface $response){
                return $response->build(200, [], ['b' => true]);
            },
        ]);

        $this->getActionRepository()->add($action);
    }

    public function testHandle()
    {
        $parameters = $this->getParameters([
            'a' => 1,
            'b' => 2,
            'percentage' => 100,
        ]);

        $action   = $this->getActionFactory()->factory(UtilABTest::class);
        $response = $action->handle($this->getRequest(), $parameters, $this->getContext());

        $this->assertInstanceOf(HttpResponseInterface::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals([], $response->getHeaders());
        $this->assertEquals(['a' => true], $response->getBody());
    }

    public function testHandleZero()
    {
        $parameters = $this->getParameters([
            'a' => 1,
            'b' => 2,
            'percentage' => 0,
        ]);

        $action   = $this->getActionFactory()->factory(UtilABTest::class);
        $response = $action->handle($this->getRequest(), $parameters, $this->getContext());

        $this->assertInstanceOf(HttpResponseInterface::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals([], $response->getHeaders());
        $this->assertEquals(['b' => true], $response->getBody());
    }

    public function testHandleRandom()
    {
        $parameters = $this->getParameters([
            'a' => 1,
            'b' => 2,
            'percentage' => 50,
        ]);

        $action   = $this->getActionFactory()->factory(UtilABTest::class);
        $response = $action->handle($this->getRequest(), $parameters, $this->getContext());

        $this->assertInstanceOf(HttpResponseInterface::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals([], $response->getHeaders());

        $data = $response->getBody();
        if (isset($data['a'])) {
            $this->assertEquals(['a' => true], $data);
        } else {
            $this->assertEquals(['b' => true], $data);
        }
    }

    public function testGetForm()
    {
        $action  = $this->getActionFactory()->factory(UtilABTest::class);
        $builder = new Builder();
        $factory = $this->getFormElementFactory();

        $action->configure($builder, $factory);

        $this->assertInstanceOf(Container::class, $builder->getForm());
    }
}
