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

use Fusio\Adapter\Util\Action\UtilCondition;
use Fusio\Adapter\Util\Tests\UtilTestCase;
use Fusio\Engine\Model\Action;
use Fusio\Engine\Response;
use Fusio\Engine\Test\CallbackAction;
use PSX\Http\Environment\HttpResponseInterface;
use PSX\Record\Record;

/**
 * UtilConditionTest
 *
 * @author  Christoph Kappestein <christoph.kappestein@gmail.com>
 * @license http://www.apache.org/licenses/LICENSE-2.0
 * @link    https://www.fusio-project.org/
 */
class UtilConditionTest extends UtilTestCase
{
    protected function setUp(): void
    {
        $action = new Action(1, 'true', CallbackAction::class, false, [
            'callback' => function(Response\FactoryInterface $response){
                return $response->build(200, [], ['a' => true]);
            },
        ]);

        $this->getActionRepository()->add($action);

        $action = new Action(2, 'false', CallbackAction::class, false, [
            'callback' => function(Response\FactoryInterface $response){
                return $response->build(200, [], ['b' => true]);
            },
        ]);

        $this->getActionRepository()->add($action);
    }

    /**
     * @dataProvider conditionProvider
     */
    public function testHandle(string $condition, array $expect)
    {
        $parameters = $this->getParameters([
            'condition' => $condition,
            'true' => 1,
            'false' => 2,
        ]);

        $action   = $this->getActionFactory()->factory(UtilCondition::class);
        $response = $action->handle($this->getRequest(uriFragments: ['id' => '1'], parameters: ['param' => 'foo'], parsedBody: Record::from(['foo' => 'bar'])), $parameters, $this->getContext());

        $this->assertInstanceOf(HttpResponseInterface::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals([], $response->getHeaders());
        $this->assertEquals($expect, $response->getBody());
    }

    public static function conditionProvider(): array
    {
        return [
            ['request.get("id") == 1', ['a' => true]],
            ['request.get("id") == 0', ['b' => true]],
            ['request.get("param") == "foo"', ['a' => true]],
            ['request.get("param") == "bar"', ['b' => true]],
            ['request.getPayload().foo == "bar"', ['a' => true]],
            ['request.getPayload().foo == "foo"', ['b' => true]],
        ];
    }
}
