<?php

namespace GisClient\Author\Offline;

abstract class AbstractOfflineData implements OfflineDataInterface
{
    private $offlineDataPath = ROOT_PATH . 'var/offline/';

    public function getOfflineDataPath()
    {
        return $this->offlineDataPath;
    }
}
