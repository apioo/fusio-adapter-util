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

use Fusio\Adapter\Util\Action\UtilChain;
use Fusio\Adapter\Util\Tests\UtilTestCase;
use Fusio\Engine\Model\Action;
use Fusio\Engine\Response;
use Fusio\Engine\Test\CallbackAction;
use PSX\Http\Environment\HttpResponseInterface;

/**
 * UtilChainTest
 *
 * @author  Christoph Kappestein <christoph.kappestein@gmail.com>
 * @license http://www.apache.org/licenses/LICENSE-2.0
 * @link    https://www.fusio-project.org/
 */
class UtilChainTest extends UtilTestCase
{
    protected function setUp(): void
    {
        $action = new Action(1, 'a', CallbackAction::class, false, [
            'callback' => function(Response\FactoryInterface $response){
                return $response->build(200, [], ['a' => true]);
            },
        ]);

        $this->getActionRepository()->add($action);

        $action = new Action(2, 'b', CallbackAction::class, false, [
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
        ]);

        $action   = $this->getActionFactory()->factory(UtilChain::class);
        $response = $action->handle($this->getRequest(), $parameters, $this->getContext());

        $this->assertInstanceOf(HttpResponseInterface::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals([], $response->getHeaders());
        $this->assertEquals(['b' => true], $response->getBody());
    }
}
