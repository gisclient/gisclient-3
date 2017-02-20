<?php

namespace GisClient\GDAL\Export\SQLite;

class Driver implements \GisClient\GDAL\Export\Driver
{
    public function getName()
    {
        return 'SQLite';
    }
}
