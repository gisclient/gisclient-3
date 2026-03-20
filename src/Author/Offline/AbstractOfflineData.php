<?php

namespace GisClient\Author\Offline;

use GisClient\Author\LayerLevelInterface;

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
    abstract protected function getTask(LayerLevelInterface $layer);

    /**
     * Return the offline filename
     *
     * @return string
     */
    abstract protected function getOfflineDataFile(LayerLevelInterface $layer);

    /**
     * Return the path containing offline data
     *
     * @return string
     */
    public function getOfflineDataPath()
    {
        return $this->offlineDataPath;
    }

    public function getCommand(LayerLevelInterface $layer)
    {
        $process = $this->getProcess();
        return $process->getCommand($this->getTask($layer), false, true);
    }

    public function start(LayerLevelInterface $layer, $runInBackground = true)
    {
        $process = $this->getProcess();
        $process->start($this->getTask($layer), $runInBackground);
    }

    public function stop(LayerLevelInterface $layer)
    {
        $process = $this->getProcess();
        $process->stop($this->getTask($layer));
    }

    public function clear(LayerLevelInterface $layer)
    {
        $task = $this->getTask($layer);
        $task->cleanup();
    }

    public function getState(LayerLevelInterface $layer)
    {
        if (!$this->exists($layer)) {
            return self::IS_TODO;
        }
        
        $process = $this->getProcess();
        if ($process->isRunning($this->getTask($layer))) {
            return self::IS_RUNNING;
        }
        
        return self::IS_STOPPED;
    }

    public function getProgress(LayerLevelInterface $layer)
    {
        return null;
    }

    public function exists(LayerLevelInterface $layer)
    {
        return file_exists($this->getOfflineDataFile($layer));
    }

    public function getOfflineFiles(LayerLevelInterface $layer)
    {
        return [
            $this->getOfflineDataFile($layer),
        ];
    }
}
