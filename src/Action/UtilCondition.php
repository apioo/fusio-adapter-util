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

namespace Fusio\Adapter\Util\Action;

use Fusio\Engine\ActionAbstract;
use Fusio\Engine\ActionInterface;
use Fusio\Engine\ContextInterface;
use Fusio\Engine\Exception\ConfigurationException;
use Fusio\Engine\Form\BuilderInterface;
use Fusio\Engine\Form\ElementFactoryInterface;
use Fusio\Engine\ParametersInterface;
use Fusio\Engine\ProcessorInterface;
use Fusio\Engine\RequestInterface;
use Psr\Cache\CacheItemPoolInterface;
use PSX\Data\Accessor;
use PSX\Validate\Validate;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;
use Symfony\Component\ExpressionLanguage\ParsedExpression;
use Symfony\Component\ExpressionLanguage\ParserCache\ParserCacheInterface;

/**
 * UtilCondition
 *
 * @author  Christoph Kappestein <christoph.kappestein@gmail.com>
 * @license http://www.gnu.org/licenses/agpl-3.0
 * @link    http://fusio-project.org
 */
class UtilCondition extends ActionAbstract implements ParserCacheInterface
{
    use ContainerAwareTrait;

    public function getName()
    {
        return 'Util-Condition';
    }

    public function handle(RequestInterface $request, ParametersInterface $configuration, ContextInterface $context)
    {
        $condition = $configuration->get('condition');
        $language  = new ExpressionLanguage($this);
        $values    = array(
            'app'          => $context->getApp(),
            'user'         => $context->getUser(),
            'routeId'      => $context->getRouteId(),
            'uriFragments' => $request->getUriFragments(),
            'parameters'   => $request->getParameters(),
            'body'         => new Accessor(new Validate(), $request->getBody()),
        );

        if (!empty($condition) && $language->evaluate($condition, $values)) {
            return $this->processor->execute($configuration->get('true'), $request, $context);
        } else {
            return $this->processor->execute($configuration->get('false'), $request, $context);
        }
    }

    public function configure(BuilderInterface $builder, ElementFactoryInterface $elementFactory)
    {
        $builder->add($elementFactory->newInput('condition', 'Condition', 'text', 'The condition which gets evaluated. You can access parameters from the context with i.e. <code>parameters.get("foo") == "bar"</code>. Click <a ng-click="help.showDialog(\'help/action/condition.md\')">here</a> for more informations about the syntax.'));
        $builder->add($elementFactory->newAction('true', 'True', 'Executed if the condition evaluates to true'));
        $builder->add($elementFactory->newAction('false', 'False', 'Executed if the condition evaluates to false'));
    }

    public function save($key, ParsedExpression $expression)
    {
        $this->cacheProvider->save($key, $expression);
    }

    public function fetch($key)
    {
        return $this->cacheProvider->contains($key) ? $this->cacheProvider->fetch($key) : null;
    }
}
