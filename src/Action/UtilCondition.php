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
use Fusio\Engine\RequestInterface;
use Symfony\Component\Cache\Adapter\Psr16Adapter;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;

/**
 * UtilCondition
 *
 * @author  Christoph Kappestein <christoph.kappestein@gmail.com>
 * @license http://www.apache.org/licenses/LICENSE-2.0
 * @link    https://www.fusio-project.org/
 */
class UtilCondition extends ActionAbstract
{
    public function getName(): string
    {
        return 'Util-Condition';
    }

    public function handle(RequestInterface $request, ParametersInterface $configuration, ContextInterface $context): mixed
    {
        $condition = $configuration->get('condition');
        if (empty($condition)) {
            throw new ConfigurationException('No condition provided');
        }

        $actionTrue = $configuration->get('true');
        if (empty($actionTrue)) {
            throw new ConfigurationException('No true action provided');
        }

        $actionFalse = $configuration->get('false');
        if (empty($actionFalse)) {
            throw new ConfigurationException('No false action provided');
        }

        $expressionLanguage = new ExpressionLanguage(new Psr16Adapter($this->cache));

        $result = $expressionLanguage->evaluate(
            $condition,
            [
                'request' => $request,
                'context' => $context,
            ]
        );

        if ($result) {
            $response = $this->processor->execute($actionTrue, $request, $context);
        } else {
            $response = $this->processor->execute($actionFalse, $request, $context);
        }

        return $response;
    }

    public function configure(BuilderInterface $builder, ElementFactoryInterface $elementFactory): void
    {
        $builder->add($elementFactory->newInput('condition', 'Condition', 'A condition which gets evaluated'));
        $builder->add($elementFactory->newAction('true', 'True', 'Executes this action if the provided condition is true'));
        $builder->add($elementFactory->newAction('false', 'False', 'Executes this action if the provided condition is false'));
    }
}
