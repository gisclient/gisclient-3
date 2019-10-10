<?php

namespace GisClient\Author\Offline;

use GisClient\Author\LayerGroup;
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
        $format = $layerGroup->getFormat();
        $type = $layerGroup->getType();
        return $format === LayerGroup::GEOJSON_FORMAT
            && $type === LayerGroup::TMS_LAYER_TYPE;
    }

    protected function getOfflineDataFile(LayerLevelInterface $layer)
    {
        $layerGroup = $layer->getLayerGroup();
        $offlineDataFile = $this->getOfflineDataPath()
            . $layer->getMap()->getProject() . '/'
            . sprintf('%s_%s.%s.mbtiles', $layer->getMap()->getName(), $layerGroup->getName(), $layer->getName());
        return $offlineDataFile;
    }

    protected function getProcess()
    {
        return new GDALProcess(new MVTDriver());
    }

    protected function getTask(LayerLevelInterface $layer)
    {
        return new MVTTask($layer, $this->getOfflineDataFile($layer), $this->logDir);
    }
}
