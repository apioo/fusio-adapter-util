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
use Fusio\Engine\RequestInterface;
use PSX\Http\Environment\HttpResponseInterface;

/**
 * UtilChain
 *
 * @author  Christoph Kappestein <christoph.kappestein@gmail.com>
 * @license http://www.gnu.org/licenses/agpl-3.0
 * @link    https://www.fusio-project.org/
 */
class UtilChain extends ActionAbstract
{
    public function getName(): string
    {
        return 'Util-Chain';
    }

    public function handle(RequestInterface $request, ParametersInterface $configuration, ContextInterface $context): HttpResponseInterface
    {
        $actions = [
            'a',
            'b',
            'c',
            'd',
        ];

        $response = null;
        foreach ($actions as $action) {
            $actionId = $configuration->get($action);
            if (empty($actionId)) {
                continue;
            }

            $response = $this->processor->execute($actionId, $request, $context);
        }

        return $response;
    }

    public function configure(BuilderInterface $builder, ElementFactoryInterface $elementFactory): void
    {
        $builder->add($elementFactory->newAction('a', 'Action A', 'Executes this action if provided, the response of the last action is returned'));
        $builder->add($elementFactory->newAction('b', 'Action B', 'Executes this action if provided, the response of the last action is returned'));
        $builder->add($elementFactory->newAction('c', 'Action C', 'Executes this action if provided, the response of the last action is returned'));
        $builder->add($elementFactory->newAction('d', 'Action D', 'Executes this action if provided, the response of the last action is returned'));
    }
}
