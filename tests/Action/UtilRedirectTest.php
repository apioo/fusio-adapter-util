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

use Fusio\Adapter\Util\Action\UtilRedirect;
use Fusio\Adapter\Util\Tests\UtilTestCase;
use Fusio\Engine\Exception\ConfigurationException;
use Fusio\Engine\Form\Builder;
use Fusio\Engine\Form\Container;
use PSX\Http\Environment\HttpResponseInterface;

/**
 * UtilRedirectTest
 *
 * @author  Christoph Kappestein <christoph.kappestein@gmail.com>
 * @license http://www.apache.org/licenses/LICENSE-2.0
 * @link    https://www.fusio-project.org/
 */
class UtilRedirectTest extends UtilTestCase
{
    public function testHandle()
    {
        $parameters = $this->getParameters([
            'location' => 'https://google.com',
        ]);

        $action   = $this->getActionFactory()->factory(UtilRedirect::class);
        $response = $action->handle($this->getRequest(), $parameters, $this->getContext());

        $this->assertInstanceOf(HttpResponseInterface::class, $response);
        $this->assertEquals(301, $response->getStatusCode());
        $this->assertEquals(['location' => 'https://google.com'], $response->getHeaders());
        $this->assertEquals(null, $response->getBody());
    }

    public function testHandleInvalidLocation()
    {
        $this->expectException(ConfigurationException::class);

        $parameters = $this->getParameters([
            'location' => '',
        ]);

        $action = $this->getActionFactory()->factory(UtilRedirect::class);
        $action->handle($this->getRequest(), $parameters, $this->getContext());
    }

    public function testGetForm()
    {
        $action  = $this->getActionFactory()->factory(UtilRedirect::class);
        $builder = new Builder();
        $factory = $this->getFormElementFactory();

        $action->configure($builder, $factory);

        $this->assertInstanceOf(Container::class, $builder->getForm());
    }
}
