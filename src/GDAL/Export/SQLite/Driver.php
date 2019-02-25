<?php

namespace GisClient\GDAL\Export\SQLite;

use GisClient\GDAL\Export\AbstractDriver;

class Driver extends AbstractDriver
{
    public function getName()
    {
        return 'SQLite';
    }

    public function getCmdArguments()
    {
        return null;
    }
}
