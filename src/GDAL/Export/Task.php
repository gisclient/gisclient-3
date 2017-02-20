<?php

namespace GisClient\GDAL\Export;

interface Task
{
    public function getTaskName();

    public function getLogFile();

    public function getErrFile();

    public function getErrors();

    public function getSource();

    public function getFileName();
}
