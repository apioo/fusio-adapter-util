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

namespace Fusio\Adapter\Util\Validator;

use ArrayIterator;
use IteratorAggregate;

/**
 * ServiceContainer
 *
 * @author  Christoph Kappestein <christoph.kappestein@gmail.com>
 * @license http://www.gnu.org/licenses/agpl-3.0
 * @link    http://fusio-project.org
 */
class ServiceContainer implements IteratorAggregate
{
    protected $container = array();

    public function get($name)
    {
        if (isset($this->container[$name])) {
            return $this->container[$name];
        } else {
            return null;
        }
    }

    public function set($name, $service)
    {
        $this->container[$name] = $service;
    }

    public function remove($name)
    {
        if (isset($this->container[$name])) {
            unset($this->container[$name]);
        }
    }

    public function getIterator()
    {
        return new ArrayIterator($this->container);
    }
}
