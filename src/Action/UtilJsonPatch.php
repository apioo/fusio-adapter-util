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
use PSX\Json\Patch;

/**
 * UtilJsonPatch
 *
 * @author  Christoph Kappestein <christoph.kappestein@gmail.com>
 * @license http://www.gnu.org/licenses/agpl-3.0
 * @link    http://fusio-project.org
 */
class UtilJsonPatch extends ActionAbstract
{
    public function getName()
    {
        return 'Util-JSON-Patch';
    }

    public function handle(RequestInterface $request, ParametersInterface $configuration, ContextInterface $context)
    {
        $body = $request->getBody();

        $operations = json_decode($configuration->get('patch'));
        if (!is_array($operations)) {
            throw new \RuntimeException('JSON patch operations must be an array');
        }

        $patch = new Patch($operations);
        $body  = $patch->patch($body);

        return $this->processor->execute($configuration->get('action'), $request->withBody($body), $context);
    }

    public function configure(BuilderInterface $builder, ElementFactoryInterface $elementFactory)
    {
        $builder->add($elementFactory->newAction('action', 'Action', 'This action receives the transformed JSON data'));
        $builder->add($elementFactory->newTextArea('patch', 'Patch', 'json', 'Contains an array of JSON patch operations'));
    }

    /**
     * @param mixed $connection
     * @return \Doctrine\Common\Cache\CacheProvider
     */
    protected function getCacheHandler($connection): ?Cache\CacheProvider
    {
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
            return null;
        }

        return $handler;
    }
}
