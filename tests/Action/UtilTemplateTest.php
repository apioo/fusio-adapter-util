<?php
/*
 * Fusio
 * A web-application to create dynamically RESTful APIs
 *
 * Copyright (C) 2015-2024 Christoph Kappestein <christoph.kappestein@gmail.com>
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

use Fusio\Adapter\Util\Action\UtilTemplate;
use Fusio\Adapter\Util\Tests\UtilTestCase;
use Fusio\Engine\Model\Action;
use Fusio\Engine\Response;
use Fusio\Engine\Test\CallbackAction;
use PSX\Http\Environment\HttpResponseInterface;

/**
 * UtilTemplateTest
 *
 * @author  Christoph Kappestein <christoph.kappestein@gmail.com>
 * @license http://www.apache.org/licenses/LICENSE-2.0
 * @link    https://www.fusio-project.org/
 */
class UtilTemplateTest extends UtilTestCase
{
    protected function setUp(): void
    {
        $action = new Action(1, 'a', CallbackAction::class, false, [
            'callback' => function(Response\FactoryInterface $response){
                return $response->build(200, [], ['bar' => 'Hello World']);
            },
        ]);

        $this->getActionRepository()->add($action);
    }

    public function testHandle()
    {
        $parameters = $this->getParameters([
            'statusCode' => 200,
            'context' => 1,
            'content_type' => 'text/xml',
            'template' => '<foo>{{ bar }}</foo>',
        ]);

        $action   = $this->getActionFactory()->factory(UtilTemplate::class);
        $response = $action->handle($this->getRequest(), $parameters, $this->getContext());

        $this->assertInstanceOf(HttpResponseInterface::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals(['content-type' => 'text/xml'], $response->getHeaders());
        $this->assertEquals('<foo>Hello World</foo>', $response->getBody());
    }

    public function testHandleDefaultContentType()
    {
        $parameters = $this->getParameters([
            'statusCode' => 200,
            'context' => 1,
            'template' => '<foo>{{ bar }}</foo>',
        ]);

        $action   = $this->getActionFactory()->factory(UtilTemplate::class);
        $response = $action->handle($this->getRequest(), $parameters, $this->getContext());

        $this->assertInstanceOf(HttpResponseInterface::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals(['content-type' => 'text/html'], $response->getHeaders());
        $this->assertEquals('<foo>Hello World</foo>', $response->getBody());
    }
}
