<?php

use Symfony\Component\Config\ConfigCache;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Dumper\PhpDumper;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\Config\FileLocator;

$file = __DIR__ .'/var/container.php';

$containerConfigCache = new ConfigCache($file, DEBUG === 1);

if (!$containerConfigCache->isFresh()) {
    $containerBuilder = new ContainerBuilder();
    $containerBuilder->setParameter('debug_dir', DEBUG_DIR);
    $containerBuilder->setParameter('project_dir', ROOT_PATH);
    $containerBuilder->setParameter('mapproxy_dir', MAPPROXY_PATH);
    $loader = new YamlFileLoader($containerBuilder, new FileLocator(__DIR__ . '/config'));
    $loader->load('container.yml');
    $containerBuilder->compile();

    $dumper = new PhpDumper($containerBuilder);
    $containerConfigCache->write(
        $dumper->dump(['class' => 'GisclientCachedContainer']),
        $containerBuilder->getResources()
    );
}

require_once $file;
return new GisclientCachedContainer();
