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

    private function getOfflineDataFile($mapName, LayerLevelInterface $layer)
    {
        $offlineDataFile = $this->getOfflineDataPath()
            . $mapName . '/'
            . sprintf('%s_%s.mbtiles', $mapName, $layer->getName());

        return $offlineDataFile;
    }

    private function getProcess(Map $map)
    {
        $project = $map->getProject();
        $mapset = $map->getName();
        $mapConfig = $this->mapPath . "/{$project}/{$mapset}.yaml";
        $seedConfig = $this->mapPath . "/{$project}/{$mapset}.seed.yaml";
        return new SeedProcess($this->binPath, $mapConfig, $seedConfig);
    }

    private function getTask(Map $map, LayerLevelInterface $layer)
    {
        return new SeedTask($this->getOfflineDataFile($map->getName(), $layer), $this->logDir);
    }

    /**
     * {@inheritdoc}
     */
    public function getState(Map $map, LayerLevelInterface $layer)
    {
        if (!$this->exists($map, $layer)) {
            return self::IS_TODO;
        }
        
        $process = $this->getProcess($map);
        if ($process->isRunning($this->getTask($map, $layer))) {
            return self::IS_RUNNING;
        }
        
        return self::IS_STOPPED;
    }

    /**
     * {@inheritdoc}
     */
    public function getProgress(Map $map, LayerLevelInterface $layer)
    {
        return $this->getTask($map, $layer)->getProgress();
    }

    /**
     * {@inheritdoc}
     */
    public function start(Map $map, LayerLevelInterface $layer)
    {
        $process = $this->getProcess($map);
        $process->start($this->getTask($map, $layer));
    }

    /**
     * {@inheritdoc}
     */
    public function stop(Map $map, LayerLevelInterface $layer)
    {
        $process = $this->getProcess($map);
        $process->stop($this->getTask($map, $layer));
    }

    /**
     * {@inheritdoc}
     */
    public function clear(Map $map, LayerLevelInterface $layer)
    {
        $task = $this->getTask($map, $layer);
        $task->cleanup();
    }

    /**
     * {@inheritdoc}
     */
    public function exists(Map $map, LayerLevelInterface $layer)
    {
        return file_exists($this->getOfflineDataFile($map->getName(), $layer));
    }

    /**
     * {@inheritdoc}
     */
    public function getOfflineFiles(Map $map, LayerLevelInterface $layer)
    {
        return [
            $this->getOfflineDataFile($map->getName(), $layer)
        ];
    }
}
