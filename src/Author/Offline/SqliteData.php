<?php

namespace GisClient\Author\Offline;

use GisClient\Author\LayerGroup;
use GisClient\Author\Layer;
use GisClient\Author\LayerLevelInterface;
use GisClient\GDAL\Export\Process as GDALProcess;
use GisClient\GDAL\Export\SQLite\Task as SQLiteTask;
use GisClient\GDAL\Export\SQLite\Driver as SQLiteDriver;

class SqliteData extends AbstractOfflineData
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
        return 'sqlite';
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

    protected function getOfflineDataFile(LayerLevelInterface $layer)
    {
        $layerGroup = $layer->getLayerGroup();
        $offlineDataFile = $this->getOfflineDataPath()
            . $layer->getMap()->getProject() . '/'
            . sprintf('%s_%s.%s.sqlite', $layer->getMap()->getName(), $layerGroup->getName(), $layer->getName());
        return $offlineDataFile;
    }

    protected function getProcess()
    {
        return new GDALProcess(new SQLiteDriver());
    }

    protected function getTask(LayerLevelInterface $layer)
    {
        return new SQLiteTask($layer, $this->getOfflineDataFile($layer), $this->logDir);
    }
}
