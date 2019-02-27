<?php

namespace GisClient\Author\Offline;

use GisClient\Author\Map;
use GisClient\Author\LayerLevelInterface;
use GisClient\Author\Theme;
use GisClient\MapProxy\Seed\Process as SeedProcess;
use GisClient\MapProxy\Seed\Task as SeedTask;

class MbtilesData extends AbstractOfflineData
{
    private $binPath;

    private $mapPath;

    private $logDir;

    public function __construct($binPath, $mapPath, $logDir)
    {
        $this->binPath = $binPath;
        $this->mapPath = $mapPath;
        $this->logDir = $logDir;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'mbtiles';
    }

    /**
     * {@inheritdoc}
     */
    public function supports(LayerLevelInterface $layer)
    {
        return ($layer instanceof Theme);
    }

    protected function getOfflineDataFile($mapName, LayerLevelInterface $layer)
    {
        $offlineDataFile = $this->getOfflineDataPath()
            . $mapName . '/'
            . sprintf('%s_%s.mbtiles', $mapName, $layer->getName());

        return $offlineDataFile;
    }

    protected function getProcess()
    {
        return new SeedProcess($this->binPath);
    }

    protected function getTask(Map $map, LayerLevelInterface $layer)
    {
        return new SeedTask($map, $this->mapPath, $this->getOfflineDataFile($map->getName(), $layer), $this->logDir);
    }

    /**
     * {@inheritdoc}
     */
    public function getProgress(Map $map, LayerLevelInterface $layer)
    {
        return $this->getTask($map, $layer)->getProgress();
    }
}
