<?php
/*
 * Fusio
 * A web-application to create dynamically RESTful APIs
 *
 * Copyright (C) 2015-2018 Christoph Kappestein <christoph.kappestein@gmail.com>
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

use Fusio\Adapter\Util\Action\UtilCache;
use Fusio\Engine\Form\Builder;
use Fusio\Engine\Form\Container;
use Fusio\Engine\Model\Action;
use Fusio\Engine\Model\Connection;
use Fusio\Engine\Response;
use Fusio\Engine\Test\CallbackAction;
use Fusio\Engine\Test\CallbackConnection;
use Fusio\Engine\Test\EngineTestCaseTrait;
use PSX\Http\Environment\HttpResponseInterface;

/**
 * UtilCacheTest
 *
 * @author  Christoph Kappestein <christoph.kappestein@gmail.com>
 * @license http://www.gnu.org/licenses/agpl-3.0
 * @link    http://fusio-project.org
 */
class UtilCacheTest extends \PHPUnit_Framework_TestCase
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
                // make sure that this is only called once
                static $count = 0;
                $count++;

                if ($count > 1) {
                    $this->fail('Callback should be only called once');
                }

                return $response->build(200, [], ['id' => 1, 'title' => 'foo', 'content' => 'bar', 'date' => '2015-02-27 19:59:15']);
            },
        ]);

        $this->getActionRepository()->add($action);
    }

    public function testHandle()
    {
        $parameters = $this->getParameters([
            'connection' => 1,
            'action' => 1,
            'expire' => 3600,
        ]);

        $action   = $this->getActionFactory()->factory(UtilCache::class);
        $response = $action->handle($this->getRequest(), $parameters, $this->getContext());

        $this->assertInstanceOf(HttpResponseInterface::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals([], $response->getHeaders());
        $this->assertEquals(['id' => 1, 'title' => 'foo', 'content' => 'bar', 'date' => '2015-02-27 19:59:15'], $response->getBody());

        // the next requests should come from the cache
        for ($i = 0; $i < 10; $i++) {
            $response = $action->handle($this->getRequest(), $parameters, $this->getContext());

            $this->assertInstanceOf(HttpResponseInterface::class, $response);
            $this->assertEquals(200, $response->getStatusCode());
            $this->assertEquals([], $response->getHeaders());
            $this->assertEquals(['id' => 1, 'title' => 'foo', 'content' => 'bar', 'date' => '2015-02-27 19:59:15'], $response->getBody());
        }
    }

    public function testGetForm()
    {
        $action  = $this->getActionFactory()->factory(UtilCache::class);
        $builder = new Builder();
        $factory = $this->getFormElementFactory();

        $action->configure($builder, $factory);

        $this->assertInstanceOf(Container::class, $builder->getForm());
    }
}
