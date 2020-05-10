<?php
/*
 * Fusio
 * A web-application to create dynamically RESTful APIs
 *
 * Copyright (C) 2015-2020 Christoph Kappestein <christoph.kappestein@gmail.com>
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

use Fusio\Adapter\Util\Action\UtilStaticResponse;
use Fusio\Engine\Form\Builder;
use Fusio\Engine\Form\Container;
use Fusio\Engine\Test\EngineTestCaseTrait;
use PHPUnit\Framework\TestCase;
use PSX\Http\Environment\HttpResponseInterface;

/**
 * UtilStaticResponseTest
 *
 * @author  Christoph Kappestein <christoph.kappestein@gmail.com>
 * @license http://www.gnu.org/licenses/agpl-3.0
 * @link    http://fusio-project.org
 */
class UtilStaticResponseTest extends TestCase
{
    use EngineTestCaseTrait;

    public function testHandle()
    {
        $parameters = $this->getParameters([
            'statusCode' => 200,
            'response' => '{"foo": "bar"}',
        ]);

        $action   = $this->getActionFactory()->factory(UtilStaticResponse::class);
        $response = $action->handle($this->getRequest(), $parameters, $this->getContext());

        $body = new \stdClass();
        $body->foo = 'bar';

        $this->assertInstanceOf(HttpResponseInterface::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals([], $response->getHeaders());
        $this->assertEquals($body, $response->getBody());
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testHandleInvalidResponseFormat()
    {
        $parameters = $this->getParameters([
            'statusCode' => 200,
            'response' => '<foo />',
        ]);

        $action = $this->getActionFactory()->factory(UtilStaticResponse::class);
        $action->handle($this->getRequest(), $parameters, $this->getContext());
    }

    public function testGetForm()
    {
        $action  = $this->getActionFactory()->factory(UtilStaticResponse::class);
        $builder = new Builder();
        $factory = $this->getFormElementFactory();

        $action->configure($builder, $factory);

        $this->assertInstanceOf(Container::class, $builder->getForm());
    }
}
