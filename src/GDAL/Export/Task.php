<?php

namespace GisClient\GDAL\Export;

abstract class Task
{
    abstract public function getTaskName();

    abstract public function getLogFile();

    abstract public function getErrFile();

    abstract public function getErrors();

    abstract public function getSource();

    abstract public function getFileName();
}
