<?php

namespace GisClient\Author\Offline;

use GisClient\Author\LayerGroup;
use GisClient\Author\Layer;
use GisClient\Author\Map;
use GisClient\Author\Theme;
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
    public function supports(Theme $theme)
    {
        foreach ($theme->getLayerGroups() as $layerGroup) {
            if ($layerGroup->getType() == LayerGroup::WFS_LAYER_TYPE) {
                return true;
            }
        }

        return false;
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
    public function getState(Map $map, Theme $theme)
    {
        if (!$this->exists($map, $theme)) {
            return self::IS_TODO;
        }

        $process = $this->getProcess();
        foreach ($theme->getLayerGroups() as $layerGroup) {
            if ($layerGroup->getType() == LayerGroup::WFS_LAYER_TYPE) {
                foreach ($layerGroup->getLayers() as $layer) {
                    if ($process->isRunning($this->getTask($map, $layer))) {
                        return self::IS_RUNNING;
                    }
                }
            }
        }
        
        return self::IS_STOPPED;
    }

    /**
     * {@inheritdoc}
     */
    public function getProgress(Map $map, Theme $theme)
    {
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function start(Map $map, Theme $theme)
    {
        $process = $this->getProcess($map);
        foreach ($theme->getLayerGroups() as $layerGroup) {
            if ($layerGroup->getType() == LayerGroup::WFS_LAYER_TYPE) {
                foreach ($layerGroup->getLayers() as $layer) {
                    $process->start($this->getTask($map, $layer));
                }
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function stop(Map $map, Theme $theme)
    {
        $process = $this->getProcess($map);
        foreach ($theme->getLayerGroups() as $layerGroup) {
            if ($layerGroup->getType() == LayerGroup::WFS_LAYER_TYPE) {
                foreach ($layerGroup->getLayers() as $layer) {
                    $process->stop($this->getTask($map, $layer));
                }
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function clear(Map $map, Theme $theme)
    {
        foreach ($theme->getLayerGroups() as $layerGroup) {
            if ($layerGroup->getType() == LayerGroup::WFS_LAYER_TYPE) {
                foreach ($layerGroup->getLayers() as $layer) {
                    $task = $this->getTask($map, $layer);
                    $task->cleanup();
                }
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function exists(Map $map, Theme $theme)
    {
        $exists = true;
        foreach ($theme->getLayerGroups() as $layerGroup) {
            if ($layerGroup->getType() == LayerGroup::WFS_LAYER_TYPE) {
                $layers = $layerGroup->getLayers();
                foreach ($layers as $layer) {
                    if (!file_exists($this->getOfflineDataFile($map->getName(), $layer))) {
                        $exists = false;
                        break;
                    }
                }
            }
        }
        return $exists;
    }

    /**
     * {@inheritdoc}
     */
    public function getOfflineFiles(Map $map, Theme $theme)
    {
        $files = [];
        foreach ($theme->getLayerGroups() as $layerGroup) {
            if ($layerGroup->getType() == LayerGroup::WFS_LAYER_TYPE) {
                foreach ($layerGroup->getLayers() as $layer) {
                    $files[] = $this->getOfflineDataFile($map->getName(), $layer);
                }
            }
        }
        return $files;
    }
}
