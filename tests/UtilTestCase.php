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

namespace Fusio\Adapter\Util\Tests;

use Fusio\Adapter\Util\Action\UtilABTest;
use Fusio\Adapter\Util\Action\UtilCache;
use Fusio\Adapter\Util\Action\UtilChain;
use Fusio\Adapter\Util\Action\UtilDispatchEvent;
use Fusio\Adapter\Util\Action\UtilJsonPatch;
use Fusio\Adapter\Util\Action\UtilRedirect;
use Fusio\Adapter\Util\Action\UtilStaticResponse;
use Fusio\Engine\Action\Runtime;
use Fusio\Engine\Test\EngineTestCaseTrait;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\Container;

/**
 * UtilTestCase
 *
 * @author  Christoph Kappestein <christoph.kappestein@gmail.com>
 * @license http://www.gnu.org/licenses/agpl-3.0
 * @link    https://www.fusio-project.org/
 */
class UtilTestCase extends TestCase
{
    use EngineTestCaseTrait;

    protected function configure(Runtime $runtime, Container $container): void
    {
        $container->set(UtilABTest::class, new UtilABTest($runtime));
        $container->set(UtilCache::class, new UtilCache($runtime));
        $container->set(UtilChain::class, new UtilChain($runtime));
        $container->set(UtilDispatchEvent::class, new UtilDispatchEvent($runtime));
        $container->set(UtilJsonPatch::class, new UtilJsonPatch($runtime));
        $container->set(UtilRedirect::class, new UtilRedirect($runtime));
        $container->set(UtilStaticResponse::class, new UtilStaticResponse($runtime));
    }
}
