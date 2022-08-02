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
use PSX\Http\Environment\HttpResponseInterface;

/**
 * UtilDispatchEvent
 *
 * @author  Christoph Kappestein <christoph.kappestein@gmail.com>
 * @license http://www.gnu.org/licenses/agpl-3.0
 * @link    https://www.fusio-project.org/
 */
class UtilDispatchEvent extends ActionAbstract
{
    public function getName(): string
    {
        return 'Util-Dispatch-Event';
    }

    public function handle(RequestInterface $request, ParametersInterface $configuration, ContextInterface $context): HttpResponseInterface
    {
        $eventName = $configuration->get('event');
        if (empty($eventName)) {
            throw new ConfigurationException('No event defined');
        }

        $this->dispatcher->dispatch($eventName, $request->getPayload());

        return $this->response->build(202, [], [
            'success' => true,
            'message' => 'Event successfully dispatched'
        ]);
    }

    public function configure(BuilderInterface $builder, ElementFactoryInterface $elementFactory): void
    {
        $builder->add($elementFactory->newInput('event', 'Event', 'text', 'The event which gets dispatched'));
    }
}
