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
use Fusio\Engine\Model\Action;
use Fusio\Engine\ParametersInterface;
use Fusio\Engine\ProcessorInterface;
use Fusio\Engine\Repository;
use Fusio\Engine\RequestInterface;
use Symfony\Component\Yaml\Parser;

/**
 * UtilProcessor
 *
 * @author  Christoph Kappestein <christoph.kappestein@gmail.com>
 * @license http://www.gnu.org/licenses/agpl-3.0
 * @link    http://fusio-project.org
 */
class UtilProcessor extends ActionAbstract
{
    public function getName()
    {
        return 'Util-Processor';
    }

    public function handle(RequestInterface $request, ParametersInterface $configuration, ContextInterface $context)
    {
        $yaml       = new Parser();
        $process    = $yaml->parse($configuration->get('process'));
        $repository = new Repository\ActionMemory();
        $id         = 1;

        if (is_array($process)) {
            foreach ($process as $class => $config) {
                if (is_array($config)) {
                    $config = array_map('strval', $config);

                    if (isset($config['id'])) {
                        $name = $config['id'];
                        unset($config['id']);
                    } else {
                        $name = 'action-' . $id;
                    }

                    $action = new Action();
                    $action->setId($id);
                    $action->setName($name);
                    $action->setClass($class);
                    $action->setConfig($config);
                    $action->setDate(date('Y-m-d H:i:s'));

                    $repository->add($action);

                    $id++;
                }
            }
        }

        if ($id === 1) {
            throw new ConfigurationException('No process defined');
        }

        $this->processor->push($repository);

        try {
            $return = $this->processor->execute(1, $request, $context);

            $this->processor->pop();

            return $return;
        } catch (\Exception $e) {
            $this->processor->pop();

            throw $e;
        }
    }

    public function configure(BuilderInterface $builder, ElementFactoryInterface $elementFactory)
    {
        $builder->add($elementFactory->newTextArea('process', 'Process', 'yaml', 'The process description in the YAML format. Click <a ng-click="help.showDialog(\'help/action/processor.md\')">here</a> for more informations about the format.'));
    }
}
