<?php

namespace GisClient\GDAL\Export;

interface Driver
{
    /**
     * Name of the format used by ogr2ogr
     *
     * @return string
     */
    public function getName();

    /**
     * Get additional command arguments
     *
     * @return string
     */
    public function getCmdArguments();

    /**
     * Return true, if the driver is available in ogr2ogr command
     *
     * @return boolean
     */
    public function isAvailable();
}
