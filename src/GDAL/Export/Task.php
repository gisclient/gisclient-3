<?php

namespace GisClient\GDAL\Export;

use GisClient\Author\Offline\OfflineTaskInterface;

interface Task extends OfflineTaskInterface
{
    public function getTaskName();

    public function getLogFile();

    public function getErrFile();

    public function getErrors();

    public function getSource();

    public function getFilePath();

    public function cleanup();
}
