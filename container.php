<?php

use Symfony\Component\Config\ConfigCache;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Dumper\PhpDumper;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\Yaml\Yaml;

$file = __DIR__ .'/var/container.php';

$containerConfigCache = new ConfigCache($file, DEBUG === 1);

if (!$containerConfigCache->isFresh()) {
    $containerBuilder = new ContainerBuilder();
    $containerBuilder->setParameter('debug_dir', DEBUG_DIR);
    $containerBuilder->setParameter('project_dir', ROOT_PATH);
    $containerBuilder->setParameter('mapproxy_dir', MAPPROXY_PATH);
    $containerBuilder->setParameter('tmp_dir', $containerBuilder->getParameter('project_dir') . 'tmp');
    $loader = new YamlFileLoader($containerBuilder, new FileLocator(__DIR__ . '/config'));
    $loader->load('container.yml');

    // read command-list from /config/commands.yml
    $yamlString = file_get_contents(__DIR__."/config/extensions.yml");
    $extensions = Yaml::parse($yamlString)["extensions"];
    if ($extensions !== null
        && (is_array($extensions) || $extensions instanceof \Traversable)
    ) {
        foreach ($extensions as $extensionClassName) {
            $extension = new $extensionClassName();
            $containerBuilder->registerExtension($extension);
            $containerBuilder->loadFromExtension($extension->getAlias());
        }
    }

    $containerBuilder->compile();

    $dumper = new PhpDumper($containerBuilder);
    $containerConfigCache->write(
        $dumper->dump(['class' => 'GisclientCachedContainer']),
        $containerBuilder->getResources()
    );
}

require_once $file;
return new GisclientCachedContainer();
