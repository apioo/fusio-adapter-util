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

use Fusio\Engine\Cache\ProviderInterface;
use PSX\Validate\FilterAbstract;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;
use Symfony\Component\ExpressionLanguage\ParsedExpression;
use Symfony\Component\ExpressionLanguage\ParserCache\ParserCacheInterface;

/**
 * ExpressionFilter
 *
 * @author  Christoph Kappestein <christoph.kappestein@gmail.com>
 * @license http://www.gnu.org/licenses/agpl-3.0
 * @link    http://fusio-project.org
 */
class ExpressionFilter extends FilterAbstract implements ParserCacheInterface
{
    /**
     * @var \Fusio\Adapter\Util\Validator\ServiceContainer
     */
    protected $container;

    /**
     * @var \Fusio\Engine\Cache\ProviderInterface
     */
    protected $cache;

    /**
     * @var string
     */
    protected $expression;

    /**
     * @var string
     */
    protected $message;

    /**
     * @param \Fusio\Adapter\Util\Validator\ServiceContainer $container
     * @param \Fusio\Engine\Cache\ProviderInterface $cache
     * @param string $expression
     * @param string $message
     */
    public function __construct(ServiceContainer $container, ProviderInterface $cache, $expression, $message)
    {
        $this->container  = $container;
        $this->cache      = $cache;
        $this->expression = $expression;
        $this->message    = $message;
    }

    public function apply($value)
    {
        $language = new ExpressionLanguage($this);
        $values   = array();

        foreach ($this->container as $name => $service) {
            $values[$name] = $service;
        }

        $values['value'] = $value;

        return $language->evaluate($this->expression, $values);
    }

    public function getErrorMessage()
    {
        return $this->message;
    }

    public function save($key, ParsedExpression $expression)
    {
        $this->cache->save($key, $expression);
    }

    public function fetch($key)
    {
        return $this->cache->contains($key) ? $this->cache->fetch($key) : null;
    }
}
