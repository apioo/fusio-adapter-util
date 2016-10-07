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

use Fusio\Adapter\Util\Action\UtilProcessor;
use Fusio\Engine\Form\Builder;
use Fusio\Engine\Form\Container;
use Fusio\Engine\Response;
use Fusio\Engine\ResponseInterface;
use Fusio\Engine\Test\EngineTestCaseTrait;
use PSX\Framework\Test\Environment;
use PSX\Record\Record;

/**
 * UtilProcessorTest
 *
 * @author  Christoph Kappestein <christoph.kappestein@gmail.com>
 * @license http://www.gnu.org/licenses/agpl-3.0
 * @link    http://fusio-project.org
 */
class UtilProcessorTest extends \PHPUnit_Framework_TestCase
{
    use EngineTestCaseTrait;

    public function testHandle()
    {
        $parameters = $this->getParameters([
            'process' => $this->getProcess(),
        ]);

        $body = Record::fromArray([
            'news_id' => 2,
            'title'   => 'foo',
        ]);

        $action   = $this->getActionFactory()->factory(UtilProcessor::class);
        $response = $action->handle($this->getRequest(null, [], [], [], $body), $parameters, $this->getContext());

        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals([], $response->getHeaders());
        $this->assertEquals(['id' => 2, 'title' => 'bar', 'content' => 'foo', 'date' => '2015-02-27 19:59:15'], $response->getBody());
    }

    public function testGetForm()
    {
        $action  = $this->getActionFactory()->factory(UtilProcessor::class);
        $builder = new Builder();
        $factory = $this->getFormElementFactory();

        $action->configure($builder, $factory);

        $this->assertInstanceOf(Container::class, $builder->getForm());
    }

    protected function getProcess()
    {
        return <<<'YAML'
Fusio\Engine\Test\CallbackAction:
    "id": callback
    "callback": Fusio\Adapter\Util\Tests\Action\UtilProcessorTest::actionCallback

YAML;
    }

    public static function actionCallback(Response\FactoryInterface $response)
    {
        return $response->build(200, [], ['id' => 2, 'title' => 'bar', 'content' => 'foo', 'date' => '2015-02-27 19:59:15']);
    }
}
