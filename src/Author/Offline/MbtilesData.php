<?php

namespace GisClient\Author\Offline;

use GisClient\Author\Map;
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
    public function supports(Theme $theme)
    {
        return true;
    }

    private function getOfflineDataFile($mapName, Theme $theme)
    {
        $offlineDataFile = $this->getOfflineDataPath()
            . $mapName . '/'
            . sprintf('%s_%s.mbtiles', $mapName, $theme->getName());

        return $offlineDataFile;
    }

    private function getProcess(Map $map)
    {
        $project = $map->getProject();
        $mapset = $map->getName();
        $mapConfig = $this->mapPath . "{$project}/{$mapset}.yaml";
        $seedConfig = $this->mapPath . "{$project}/{$mapset}.seed.yaml";
        return new SeedProcess($this->binPath, $mapConfig, $seedConfig);
    }

    private function getTask(Map $map, Theme $theme)
    {
        return new SeedTask($this->getOfflineDataFile($map->getName(), $theme), $this->logDir);
    }

    /**
     * {@inheritdoc}
     */
    public function getState(Map $map, Theme $theme)
    {
        if (!$this->exists($map, $theme)) {
            return self::IS_TODO;
        }
        
        $process = $this->getProcess($map);
        if ($process->isRunning($this->getTask($map, $theme))) {
            return self::IS_RUNNING;
        }
        
        return self::IS_STOPPED;
    }

    /**
     * {@inheritdoc}
     */
    public function getProgress(Map $map, Theme $theme)
    {
        return $this->getTask($map, $theme)->getProgress();
    }

    /**
     * {@inheritdoc}
     */
    public function start(Map $map, Theme $theme)
    {
        $process = $this->getProcess($map);
        $process->start($this->getTask($map, $theme));
    }

    /**
     * {@inheritdoc}
     */
    public function stop(Map $map, Theme $theme)
    {
        $process = $this->getProcess($map);
        $process->stop($this->getTask($map, $theme));
    }

    /**
     * {@inheritdoc}
     */
    public function clear(Map $map, Theme $theme)
    {
        $task = $this->getTask($map, $theme);
        $task->cleanup();
    }

    /**
     * {@inheritdoc}
     */
    public function exists(Map $map, Theme $theme)
    {
        return file_exists($this->getOfflineDataFile($map->getName(), $theme));
    }

    /**
     * {@inheritdoc}
     */
    public function getOfflineFiles(Map $map, Theme $theme)
    {
        return [
            $this->getOfflineDataFile($map->getName(), $theme)
        ];
    }
}
