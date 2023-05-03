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

namespace Fusio\Adapter\Util\Action;

use Fusio\Engine\ActionAbstract;
use Fusio\Engine\ContextInterface;
use Fusio\Engine\Form\BuilderInterface;
use Fusio\Engine\Form\ElementFactoryInterface;
use Fusio\Engine\ParametersInterface;
use Fusio\Engine\Request\HttpRequest;
use Fusio\Engine\Request\RpcRequest;
use Fusio\Engine\RequestInterface;
use Psr\SimpleCache\CacheInterface;
use PSX\Http\Environment\HttpResponseInterface;
use Symfony\Component\Cache\Adapter\MemcachedAdapter;
use Symfony\Component\Cache\Adapter\RedisAdapter;
use Symfony\Component\Cache\Psr16Cache;

/**
 * UtilCache
 *
 * @author  Christoph Kappestein <christoph.kappestein@gmail.com>
 * @license http://www.gnu.org/licenses/agpl-3.0
 * @link    https://www.fusio-project.org/
 */
class UtilCache extends ActionAbstract
{
    private static ?CacheInterface $handler = null;

    public function getName(): string
    {
        return 'Util-Cache';
    }

    public function handle(RequestInterface $request, ParametersInterface $configuration, ContextInterface $context): HttpResponseInterface
    {
        $key = $this->getCacheKey($request, $configuration);
        $handler  = $this->getCacheHandler($this->connector->getConnection($configuration->get('connection')));
        $response = $handler->get($key);

        if (empty($response)) {
            $response = $this->processor->execute($configuration->get('action'), $request, $context);

            $handler->set($key, $response, (int) $configuration->get('expire'));
        }

        return $response;
    }

    public function configure(BuilderInterface $builder, ElementFactoryInterface $elementFactory): void
    {
        $builder->add($elementFactory->newAction('action', 'Action', 'The response of this action is cached'));
        $builder->add($elementFactory->newInput('expire', 'Expire', 'number', 'Number of seconds when the cache expires. 0 means infinite cache lifetime'));
        $builder->add($elementFactory->newConnection('connection', 'Connection', 'Optional connection to a memcache or redis server otherwise the system cache is used'));
    }

    /**
     * @psalm-suppress UndefinedClass
     */
    private function getCacheHandler(mixed $connection): CacheInterface
    {
        if (self::$handler) {
            return self::$handler;
        }

        $handler = null;
        if ($connection instanceof \Memcached) {
            $handler = new MemcachedAdapter($connection);
        } elseif ($connection instanceof \Redis) {
            $handler = new RedisAdapter($connection);
        }

        if ($handler !== null) {
            return self::$handler = new Psr16Cache($handler);
        } else {
            return self::$handler = $this->cache;
        }
    }

    private function getCacheKey(RequestInterface $request, ParametersInterface $configuration): string
    {
        $requestContext = $request->getContext();
        if ($requestContext instanceof HttpRequest) {
            return md5($configuration->get('action') . $requestContext->getMethod() . json_encode($requestContext->getUriFragments()) . json_encode($requestContext->getParameters()));
        } elseif ($requestContext instanceof RpcRequest) {
            return md5($configuration->get('action') . $requestContext->getMethod());
        } else {
            return md5($configuration->get('action'));
        }
    }
}
