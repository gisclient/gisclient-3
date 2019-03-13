<?php

namespace GisClient\GDAL\Export\MVT;

use GisClient\GDAL\Export\AbstractDriver;

class Driver extends AbstractDriver
{
    public function getName()
    {
        return 'MVT';
    }

    public function getCmdArguments()
    {
        return [
            '-dsco',
            'MINZOOM=13',
            '-dsco',
            'MAXZOOM=15',
            '-dsco',
            'SIMPLIFICATION_MAX_ZOOM=0',
        ];
    }
}
