<?php

namespace GisClient\GDAL\Export;

use Symfony\Component\Process\Process as SymfonyProcess;

/**
 * Abstract class for gdal driver
 */
abstract class AbstractDriver implements Driver
{
    /**
     * {@inheritdoc}
     */
    public function isAvailable()
    {
        $process = new SymfonyProcess(sprintf("ogrinfo --formats | grep %s", $this->getName()));
        return $process->run() === 0;
    }
}
