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
use Fusio\Engine\RequestInterface;
use Fusio\Engine\Response\FactoryInterface as ResponseFactoryInterface;
use Fusio\Engine\Template\FactoryInterface;
use PSX\Http\Http;
use PSX\Json\Parser;

/**
 * UtilStaticResponse
 *
 * @author  Christoph Kappestein <christoph.kappestein@gmail.com>
 * @license http://www.gnu.org/licenses/agpl-3.0
 * @link    http://fusio-project.org
 */
class UtilStaticResponse extends ActionAbstract
{
    public function getName()
    {
        return 'Util-Static-Response';
    }

    public function handle(RequestInterface $request, ParametersInterface $configuration, ContextInterface $context)
    {
        $response = $configuration->get('response');

        if (!empty($response)) {
            $statusCode = $configuration->get('statusCode') ?: 200;

            return $this->response->build($statusCode, [], Parser::decode($response, false));
        } else {
            throw new ConfigurationException('No response defined');
        }
    }

    public function configure(BuilderInterface $builder, ElementFactoryInterface $elementFactory)
    {
        $builder->add($elementFactory->newSelect('statusCode', 'Status-Code', Http::$codes, 'The returned status code'));
        $builder->add($elementFactory->newTextArea('response', 'Response', 'json', 'The response in JSON format. Inside the response it is possible to use a template syntax to add dynamic data. Click <a ng-click="help.showDialog(\'help/template.md\')">here</a> for more informations about the template syntax.'));
    }
}
