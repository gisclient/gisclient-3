<?php

namespace GisClient\Author\Offline;

use GisClient\Author\LayerGroup;
use GisClient\Author\Map;
use GisClient\Author\LayerLevelInterface;
use GisClient\Author\Layer;
use GisClient\GDAL\Export\Process as GDALProcess;
use GisClient\GDAL\Export\MVT\Task as MVTTask;
use GisClient\GDAL\Export\MVT\Driver as MVTDriver;

class MVTData extends AbstractOfflineData
{
    private $logDir;

    public function __construct($logDir)
    {
        $this->logDir = $logDir;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'mvt';
    }

    /**
     * {@inheritdoc}
     */
    public function supports(LayerLevelInterface $layer)
    {
        if (!($layer instanceof Layer)) {
            return false;
        }
        $layerGroup = $layer->getLayerGroup();
        return $layerGroup->getType() === LayerGroup::WFS_LAYER_TYPE;
    }

    private function getOfflineDataFile($mapName, Layer $layer)
    {
        $layerGroup = $layer->getLayerGroup();
        $offlineDataFile = $this->getOfflineDataPath()
            . $mapName . '/'
            . sprintf('%s_%s.%s.mbtiles', $mapName, $layerGroup->getName(), $layer->getName());
        return $offlineDataFile;
    }

    private function getProcess()
    {
        return new GDALProcess(new MVTDriver());
    }

    private function getTask(Map $map, Layer $layer)
    {
        return new MVTTask($layer, $this->getOfflineDataFile($map->getName(), $layer), $this->logDir);
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
