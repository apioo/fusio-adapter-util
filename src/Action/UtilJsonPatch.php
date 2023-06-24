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
use Fusio\Engine\Request;
use Fusio\Engine\RequestInterface;
use PSX\Http\Environment\HttpResponse;
use PSX\Http\Environment\HttpResponseInterface;
use PSX\Json\Patch;
use PSX\Record\RecordInterface;

/**
 * UtilJsonPatch
 *
 * @author  Christoph Kappestein <christoph.kappestein@gmail.com>
 * @license http://www.gnu.org/licenses/agpl-3.0
 * @link    https://www.fusio-project.org/
 */
class UtilJsonPatch extends ActionAbstract
{
    public function getName(): string
    {
        return 'Util-JSON-Patch';
    }

    public function handle(RequestInterface $request, ParametersInterface $configuration, ContextInterface $context): HttpResponseInterface
    {
        $requestPatch = $this->getPatch($configuration->get('patch'));
        if ($requestPatch instanceof Patch && $request instanceof Request) {
            $request = $request->withPayload($this->transform($requestPatch, $request->getPayload()));
        }

        $response = $this->processor->execute($configuration->get('action'), $request, $context);

        $responsePatch = $this->getPatch($configuration->get('response_patch'));
        if ($responsePatch instanceof Patch) {
            if ($response instanceof HttpResponse) {
                $response = $response->withBody($this->transform($responsePatch, $response->getBody()));
            } else {
                $response = $this->transform($responsePatch, $response);
            }
        }

        return $response;
    }

    public function configure(BuilderInterface $builder, ElementFactoryInterface $elementFactory): void
    {
        $builder->add($elementFactory->newAction('action', 'Action', 'This action receives the transformed JSON data'));
        $builder->add($elementFactory->newTextArea('patch', 'Request-Patch', 'json', 'JSON patch operations to transform the request, more information about JSON patch at https://tools.ietf.org/html/rfc6902'));
        $builder->add($elementFactory->newTextArea('response_patch', 'Response-Patch', 'json', 'JSON patch operations to transform the response, more information about JSON patch at https://tools.ietf.org/html/rfc6902'));
    }

    private function getPatch(?string $patch): ?Patch
    {
        if (empty($patch)) {
            return null;
        }

        $operations = json_decode($patch);
        if (!is_array($operations)) {
            throw new ConfigurationException('JSON patch operations must be an array');
        }

        return new Patch($operations);
    }

    private function transform(Patch $patch, mixed $body): mixed
    {
        if (is_array($body) || $body instanceof \stdClass || $body instanceof RecordInterface) {
            return $patch->patch($body);
        } else {
            return $body;
        }
    }
}
