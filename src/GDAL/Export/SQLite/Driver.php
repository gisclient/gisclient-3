<?php

namespace GisClient\GDAL\Export\SQLite;

class Driver extends \GisClient\GDAL\Export\Driver
{
    public function getName()
    {
        return 'SQLite';
    }
}
