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

use Fusio\Adapter\Util;
use Fusio\Engine\ActionAbstract;
use Fusio\Engine\ActionInterface;
use Fusio\Engine\ContextInterface;
use Fusio\Engine\Form\BuilderInterface;
use Fusio\Engine\Form\ElementFactoryInterface;
use Fusio\Engine\ParametersInterface;
use Fusio\Engine\ProcessorInterface;
use Fusio\Engine\RequestInterface;
use PSX\Cache\Pool;
use PSX\Data\Validator\Property;
use PSX\Data\Validator\Validator as PSXValidator;
use PSX\Http\Exception as StatusCode;
use Symfony\Component\Yaml\Parser;

/**
 * UtilValidator
 *
 * @author  Christoph Kappestein <christoph.kappestein@gmail.com>
 * @license http://www.gnu.org/licenses/agpl-3.0
 * @link    http://fusio-project.org
 */
class UtilValidator extends ActionAbstract
{
    public function getName()
    {
        return 'Util-Validator';
    }

    public function handle(RequestInterface $request, ParametersInterface $configuration, ContextInterface $context)
    {
        $yaml  = new Parser();
        $rules = $yaml->parse($configuration->get('rules'));

        if (is_array($rules)) {
            $pathRules  = [];
            $queryRules = [];
            $bodyRules  = [];

            foreach ($rules as $key => $value) {
                if (substr($key, 0, 7) == '/~path/') {
                    $pathRules[substr($key, 7)] = $value;
                } elseif (substr($key, 0, 8) == '/~query/') {
                    $queryRules[substr($key, 8)] = $value;
                } else {
                    $bodyRules[$key] = $value;
                }
            }

            $parts = [
                [$pathRules, $request->getUriFragments()],
                [$queryRules, $request->getParameters()],
                [$bodyRules, $request->getBody()],
            ];
            
            foreach ($parts as $part) {
                list($rules, $data) = $part;

                if (!empty($rules)) {
                    $validator = $this->buildValidator($rules);
                    $validator->validate($data);

                    // check whether all required fields are available
                    $fields = $validator->getRequiredNames();
                    if (!empty($fields)) {
                        throw new StatusCode\BadRequestException('Missing required fields: ' . implode(', ', $fields));
                    }
                }
            }
        }

        return $this->processor->execute($configuration->get('action'), $request, $context);
    }

    public function configure(BuilderInterface $builder, ElementFactoryInterface $elementFactory)
    {
        $builder->add($elementFactory->newAction('action', 'Action', 'Action which gets executed if the validation was successful'));
        $builder->add($elementFactory->newTextArea('rules', 'Rules', 'yaml', 'The validation rules in YAML format. Click <a ng-click="help.showDialog(\'help/action/validator.md\')">here</a> for more informations about the format.'));
    }

    protected function buildValidator(array $rules)
    {
        $container = $this->newServiceContainer();
        $fields    = array();

        foreach ($rules as $path => $rule) {
            $message  = null;
            $required = false;
            if (is_string($rule)) {
                $expr     = $rule;
            } elseif (is_array($rule)) {
                $expr     = isset($rule['rule'])     ? $rule['rule']     : null;
                $message  = isset($rule['message'])  ? $rule['message']  : null;
                $required = isset($rule['required']) ? $rule['required'] : false;
            }

            if (!empty($expr)) {
                if (empty($message)) {
                    $message = '%s contains an invalid value';
                }

                $fields[] = new Property(
                    $path,
                    null,
                    [new Util\Validator\ExpressionFilter($container, $this->cacheProvider, $expr, $message)],
                    $required
                );
            }
        }

        return new PSXValidator($fields);
    }
    
    protected function newServiceContainer()
    {
        $container = new Util\Validator\ServiceContainer();
        $container->set('database', new Util\Validator\Service\Database($this->connector));
        $container->set('filter', new Util\Validator\Service\Filter());

        return $container;
    }
}
