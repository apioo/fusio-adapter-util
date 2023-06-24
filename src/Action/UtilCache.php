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
use Fusio\Engine\Exception\ConfigurationException;
use Fusio\Engine\Form\BuilderInterface;
use Fusio\Engine\Form\ElementFactoryInterface;
use Fusio\Engine\ParametersInterface;
use Fusio\Engine\Request\HttpRequestContext;
use Fusio\Engine\Request\RpcRequestContext;
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
        $action = $configuration->get('action');
        if (empty($action)) {
            throw new ConfigurationException('No action provided');
        }

        $key = $this->getCacheKey($request, $action);
        $handler = $this->getCacheHandler($configuration->get('connection'));
        $response = $handler->get($key);

        if (empty($response)) {
            $response = $this->processor->execute($action, $request, $context);

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
    private function getCacheHandler(?string $connection): CacheInterface
    {
        if (self::$handler) {
            return self::$handler;
        }

        if (empty($connection)) {
            return self::$handler = $this->cache;
        }

        $instance = $this->connector->getConnection($connection);
        if ($instance instanceof \Memcached) {
            $handler = new MemcachedAdapter($instance);
        } elseif ($instance instanceof \Redis) {
            $handler = new RedisAdapter($instance);
        } else {
            return self::$handler = $this->cache;
        }

        return self::$handler = new Psr16Cache($handler);
    }

    private function getCacheKey(RequestInterface $request, string $action): string
    {
        $requestContext = $request->getContext();
        if ($requestContext instanceof HttpRequestContext) {
            return md5($action . $requestContext->getRequest()->getMethod() . json_encode($requestContext->getParameters()) . json_encode($requestContext->getRequest()->getUri()->getParameters()));
        } elseif ($requestContext instanceof RpcRequestContext) {
            return md5($action . $requestContext->getMethod());
        } else {
            return md5($action);
        }
    }
}
