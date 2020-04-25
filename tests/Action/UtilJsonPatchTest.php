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

use Fusio\Adapter\Util\Action\UtilJsonPatch;
use Fusio\Engine\Form\Builder;
use Fusio\Engine\Form\Container;
use Fusio\Engine\Model\Action;
use Fusio\Engine\Response;
use Fusio\Engine\Test\CallbackAction;
use Fusio\Engine\Test\EngineTestCaseTrait;
use PHPUnit\Framework\TestCase;
use PSX\Http\Environment\HttpResponseInterface;
use PSX\Record\Record;

/**
 * UtilJsonPatchTest
 *
 * @author  Christoph Kappestein <christoph.kappestein@gmail.com>
 * @license http://www.gnu.org/licenses/agpl-3.0
 * @link    http://fusio-project.org
 */
class UtilJsonPatchTest extends TestCase
{
    use EngineTestCaseTrait;

    protected function setUp()
    {
        $action = new Action();
        $action->setId(1);
        $action->setName('foo');
        $action->setClass(CallbackAction::class);
        $action->setConfig([
            'callback' => function(Response\FactoryInterface $response, $request){
                return $response->build(200, [], $request->getBody());
            },
        ]);

        $this->getActionRepository()->add($action);
    }

    public function testHandle()
    {
        $body = <<<JSON
{
  "baz": "qux",
  "foo": "bar"
}
JSON;

        $patch = <<<JSON
[
  { "op": "replace", "path": "/baz", "value": "boo" },
  { "op": "add", "path": "/hello", "value": ["world"] },
  { "op": "remove", "path": "/foo" }
]
JSON;

        $expect = <<<JSON
{
  "baz": "boo",
  "hello": ["world"]
}
JSON;

        $parameters = $this->getParameters([
            'action' => 1,
            'patch' => $patch,
        ]);

        $request = $this->getRequest();
        $request = $request->withBody(Record::fromStdClass(json_decode($body)));

        $action   = $this->getActionFactory()->factory(UtilJsonPatch::class);
        $response = $action->handle($request, $parameters, $this->getContext());

        $this->assertInstanceOf(HttpResponseInterface::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals([], $response->getHeaders());
        $this->assertJsonStringEqualsJsonString($expect, json_encode($response->getBody()));

    }

    public function testGetForm()
    {
        $action  = $this->getActionFactory()->factory(UtilJsonPatch::class);
        $builder = new Builder();
        $factory = $this->getFormElementFactory();

        $action->configure($builder, $factory);

        $this->assertInstanceOf(Container::class, $builder->getForm());
    }
}
