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
use PSX\Record\Record;
use Twig\Environment;
use Twig\Loader\ArrayLoader;

/**
 * UtilTemplate
 *
 * @author  Christoph Kappestein <christoph.kappestein@gmail.com>
 * @license http://www.apache.org/licenses/LICENSE-2.0
 * @link    https://www.fusio-project.org/
 */
class UtilTemplate extends ActionAbstract
{
    public function getName(): string
    {
        return 'Util-Template';
    }

    public function handle(RequestInterface $request, ParametersInterface $configuration, ContextInterface $context): HttpResponseInterface
    {
        $template = $configuration->get('template');
        if (empty($template)) {
            throw new ConfigurationException('No template provided');
        }

        $headers = [];
        $contentType = $configuration->get('content_type');
        if (!empty($contentType)) {
            $headers['Content-Type'] = $contentType;
        } else {
            $headers['Content-Type'] = 'text/html';
        }

        $body = $this->render($template, $this->getTemplateContext($request, $configuration, $context));

        return $this->response->build(200, $headers, $body);
    }

    public function configure(BuilderInterface $builder, ElementFactoryInterface $elementFactory): void
    {
        $builder->add($elementFactory->newAction('context', 'Context', 'The action to populate the context'));
        $builder->add($elementFactory->newInput('content_type', 'Content-Type', 'text', 'Optional a specific Content-Type by default it is text/html'));
        $builder->add($elementFactory->newTextArea('template', 'Template', 'html', 'The twig template'));
    }

    private function getTemplateContext(RequestInterface $request, ParametersInterface $configuration, ContextInterface $context): ?Record
    {
        $contextAction = $configuration->get('context');
        if (empty($contextAction)) {
            return null;
        }

        $response = $this->processor->execute($contextAction, $request, $context);

        return $this->parseResponse($response);
    }

    private function render(string $template, ?Record $templateContext): string
    {
        $loader = new ArrayLoader(['template' => $template]);
        $twig = new Environment($loader, []);

        return $twig->render('template', $templateContext?->getAll() ?? []);
    }

    private function parseResponse(mixed $data): ?Record
    {
        if ($data instanceof HttpResponseInterface) {
            return $this->parseResponse($data->getBody());
        } elseif (is_array($data) || is_object($data)) {
            return Record::from($data);
        } else {
            return null;
        }
    }
}
