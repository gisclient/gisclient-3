<?php

namespace GisClient\Author\Offline;

use GisClient\Author\LayerLevelInterface;
use GisClient\Author\Map;
use GisClient\Author\Utils\GCMap;
use GisClient\Author\Utils\TemporaryFileService;
use Symfony\Component\Filesystem\Filesystem;

class MapData implements OfflineDataInterface
{
    private $tmpService;

    public function __construct(TemporaryFileService $tmpService)
    {
        $this->tmpService = $tmpService;
    }

    public function getName()
    {
        return 'map';
    }

    public function getCommand(LayerLevelInterface $layer)
    {
        throw new \RuntimeException('Method not supported');
    }

    public function supports(LayerLevelInterface $layer)
    {
        return $layer instanceof Map;
    }

    public function getState(LayerLevelInterface $layer)
    {
        return self::IS_STOPPED;
    }

    public function getProgress(LayerLevelInterface $layer)
    {
        return null;
    }

    public function exists(LayerLevelInterface $layer)
    {
        return true;
    }

    public function start(LayerLevelInterface $layer, $runInBackground = true)
    {
        throw new \RuntimeException('Method not supported');
    }

    public function stop(LayerLevelInterface $layer)
    {
        throw new \RuntimeException('Method not supported');
    }

    public function clear(LayerLevelInterface $layer)
    {
        throw new \RuntimeException('Method not supported');
    }

    public function getOfflineFiles(LayerLevelInterface $layer)
    {
        $fs = new Filesystem();
        $objMapset = new GCMap($layer->getName(), true);
        $mapConfig = $this->tmpService->create(sprintf('offline_%s', $this->getName()));
        $fs->dumpFile($mapConfig, json_encode($objMapset->mapConfig));
        return [
            [
                'file' => $mapConfig,
                'filename' => 'config.json',
            ],
        ];
    }
}
