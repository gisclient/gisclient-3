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

    /**
     * {@inheritdoc}
     */
    public function getCommand(LayerLevelInterface $layer)
    {
        $process = $this->getProcess();
        return $process->getCommand($this->getTask($layer), false, true);
    }

    /**
     * {@inheritdoc}
     */
    public function start(LayerLevelInterface $layer)
    {
        $process = $this->getProcess();
        $process->start($this->getTask($layer));
    }

    /**
     * {@inheritdoc}
     */
    public function stop(LayerLevelInterface $layer)
    {
        $process = $this->getProcess();
        $process->stop($this->getTask($layer));
    }

    /**
     * {@inheritdoc}
     */
    public function clear(LayerLevelInterface $layer)
    {
        $task = $this->getTask($layer);
        $task->cleanup();
    }

    /**
     * {@inheritdoc}
     */
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
        return file_exists($this->getOfflineDataFile($layer));
    }

    /**
     * {@inheritdoc}
     */
    public function getOfflineFiles(LayerLevelInterface $layer)
    {
        return [
            $this->getOfflineDataFile($layer)
        ];
    }
}
