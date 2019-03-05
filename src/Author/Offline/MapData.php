<?php

namespace GisClient\Author\Offline;

use GisClient\Author\LayerGroup;
use GisClient\Author\LayerLevelInterface;
use GisClient\Author\Map;
use GisClient\Author\Utils\GCMap;
use Symfony\Component\Filesystem\Filesystem;

class MapData implements OfflineDataInterface
{
    private $tmpDir;

    public function __construct($tmpDir)
    {
        $this->tmpDir = $tmpDir;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'map';
    }

    /**
     * {@inheritdoc}
     */
    public function getCommand(LayerLevelInterface $layer)
    {
        throw new \RuntimeException('Method not supported');
    }

    /**
     * {@inheritdoc}
     */
    public function supports(LayerLevelInterface $layer)
    {
        return $layer instanceof Map;
    }

    /**
     * {@inheritdoc}
     */
    public function getState(LayerLevelInterface $layer)
    {
        return self::IS_STOPPED;
    }

    /**
     * {@inheritdoc}
     */
    public function getProgress(LayerLevelInterface $layer)
    {
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function exists(LayerLevelInterface $layer)
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function start(LayerLevelInterface $layer)
    {
        throw new \RuntimeException('Method not supported');
    }

    /**
     * {@inheritdoc}
     */
    public function stop(LayerLevelInterface $layer)
    {
        throw new \RuntimeException('Method not supported');
    }

    /**
     * {@inheritdoc}
     */
    public function clear(LayerLevelInterface $layer)
    {
        throw new \RuntimeException('Method not supported');
    }

    /**
     * {@inheritdoc}
     */
    public function getOfflineFiles(LayerLevelInterface $layer)
    {
        $fs = new Filesystem();
        $objMapset = new GCMap($layer->getName(), true);
        $mapConfig = $fs->tempnam($this->tmpDir, sprintf('offline_%s', $this->getName()));
        $fs->dumpFile($mapConfig, $objMapset->mapConfig);
        return [
            ['file' => $mapConfig, 'filename' => 'config.json'],
        ];
    }
}
