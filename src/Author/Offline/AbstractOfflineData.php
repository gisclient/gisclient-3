<?php

namespace GisClient\Author\Offline;

use GisClient\Author\LayerGroup;
use GisClient\Author\Theme;

abstract class AbstractOfflineData implements OfflineDataInterface
{
    private $offlineDataPath = ROOT_PATH . 'var/offline/';

    public function getOfflineDataPath()
    {
        return $this->offlineDataPath;
    }
}
