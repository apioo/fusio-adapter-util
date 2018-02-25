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

namespace Fusio\Adapter\Util\Action;

use Doctrine\Common\Cache;
use Fusio\Engine\ActionAbstract;
use Fusio\Engine\ContextInterface;
use Fusio\Engine\Form\BuilderInterface;
use Fusio\Engine\Form\ElementFactoryInterface;
use Fusio\Engine\ParametersInterface;
use Fusio\Engine\RequestInterface;

/**
 * UtilCache
 *
 * @author  Christoph Kappestein <christoph.kappestein@gmail.com>
 * @license http://www.gnu.org/licenses/agpl-3.0
 * @link    http://fusio-project.org
 */
class UtilCache extends ActionAbstract
{
    /**
     * @var \Doctrine\Common\Cache\CacheProvider
     */
    protected static $handler;

    public function getName()
    {
        return 'Util-Cache';
    }

    public function handle(RequestInterface $request, ParametersInterface $configuration, ContextInterface $context)
    {
        $key = md5($configuration->get('action') . json_encode($request->getUriFragments()) . json_encode($request->getParameters()));

        $handler  = $this->getCacheHandler($this->connector->getConnection($configuration->get('connection')));
        $response = $handler->fetch($key);

        if ($response === false) {
            $response = $this->processor->execute($configuration->get('action'), $request, $context);

            $handler->save($key, $response, $configuration->get('expire'));
        }

        return $response;
    }

    public function configure(BuilderInterface $builder, ElementFactoryInterface $elementFactory)
    {
        $builder->add($elementFactory->newConnection('connection', 'Connection', 'Connection to a memcache or redis server'));
        $builder->add($elementFactory->newAction('action', 'Action', 'The response of this action is cached'));
        $builder->add($elementFactory->newInput('expire', 'Expire', 'number', 'Number of seconds when the cache expires. 0 means infinite cache lifetime'));
    }

    /**
     * @param mixed $connection
     * @return \Doctrine\Common\Cache\CacheProvider
     */
    protected function getCacheHandler($connection)
    {
        if (self::$handler) {
            return self::$handler;
        }

        if ($connection instanceof \Memcache) {
            $handler = new Cache\MemcacheCache();
            $handler->setMemcache($connection);
        } elseif ($connection instanceof \Memcached) {
            $handler = new Cache\MemcachedCache();
            $handler->setMemcached($connection);
        } elseif ($connection instanceof \Redis) {
            $handler = new Cache\RedisCache();
            $handler->setRedis($connection);
        } else {
            $handler = new Cache\ArrayCache();
        }

        return self::$handler = $handler;
    }
}
