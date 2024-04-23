<?php

use Fusio\Adapter\Util\Action\UtilABTest;
use Fusio\Adapter\Util\Action\UtilCache;
use Fusio\Adapter\Util\Action\UtilChain;
use Fusio\Adapter\Util\Action\UtilCondition;
use Fusio\Adapter\Util\Action\UtilDispatchEvent;
use Fusio\Adapter\Util\Action\UtilJsonPatch;
use Fusio\Adapter\Util\Action\UtilRedirect;
use Fusio\Adapter\Util\Action\UtilStaticResponse;
use Fusio\Engine\Adapter\ServiceBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $container) {
    $services = ServiceBuilder::build($container);
    $services->set(UtilABTest::class);
    $services->set(UtilCache::class);
    $services->set(UtilChain::class);
    $services->set(UtilCondition::class);
    $services->set(UtilDispatchEvent::class);
    $services->set(UtilJsonPatch::class);
    $services->set(UtilRedirect::class);
    $services->set(UtilStaticResponse::class);
};
