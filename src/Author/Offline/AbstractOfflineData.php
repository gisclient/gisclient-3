<?php

namespace GisClient\Author\Offline;

use GisClient\Author\LayerLevelInterface;
use GisClient\Author\Map;

abstract class AbstractOfflineData implements OfflineDataInterface
{
    private $offlineDataPath = ROOT_PATH . 'var/offline/';

    /**
     * Return the process to generate the offline data
     *
     * @return OfflineProcessInterface
     */
    abstract protected function getProcess();

    /**
     * Return the task with information to generate the offline data
     *
     * @return OfflineTaskInterface
     */
    abstract protected function getTask(Map $map, LayerLevelInterface $layer);

    /**
     * Return the offline filename
     *
     * @return string
     */
    abstract protected function getOfflineDataFile($mapName, LayerLevelInterface $layer);

    /**
     * Return the path containing offline data
     *
     * @return string
     */
    public function getOfflineDataPath()
    {
        return $this->offlineDataPath;
    }

    /**
     * {@inheritdoc}
     */
    public function getCommand(Map $map, LayerLevelInterface $layer)
    {
        $process = $this->getProcess($map);
        return $process->getCommand($this->getTask($map, $layer), false, true);
    }

    /**
     * {@inheritdoc}
     */
    public function start(Map $map, LayerLevelInterface $layer)
    {
        $process = $this->getProcess();
        $process->start($this->getTask($map, $layer));
    }

    /**
     * {@inheritdoc}
     */
    public function stop(Map $map, LayerLevelInterface $layer)
    {
        $process = $this->getProcess();
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
    public function getState(Map $map, LayerLevelInterface $layer)
    {
        if (!$this->exists($map, $layer)) {
            return self::IS_TODO;
        }
        
        $process = $this->getProcess();
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
        return null;
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
