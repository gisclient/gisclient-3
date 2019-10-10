<?php

namespace GisClient\MapProxy\Seed;

use GisClient\Author\Map;
use GisClient\Author\Offline\OfflineTaskInterface;

class Task implements OfflineTaskInterface
{
    private $mapConfig;
    private $seedConfig;
    private $logFile;
    private $errFile;
    private $path;
    private $task;

    public function __construct(Map $map, $mapPath, $filename, $logDir)
    {
        $this->path = dirname($filename);
        $this->task = basename($filename);

        $project = $map->getProject();
        $mapset = $map->getName();
        $this->mapConfig = $mapPath . "/{$project}/{$mapset}.yaml";
        $this->seedConfig = $mapPath . "/{$project}/{$mapset}.seed.yaml";

        $this->logFile = $logDir . $this->getTaskName() . '-seed.log';
        $this->errFile = $logDir . $this->getTaskName() . '-seed.err';
    }

    public function getTaskName()
    {
        return basename($this->task, '.mbtiles');
    }

    public function getMapConfig()
    {
        return $this->mapConfig;
    }

    public function getSeedConfig()
    {
        return $this->seedConfig;
    }

    public function getLogFile()
    {
        return $this->logFile;
    }

    public function getErrFile()
    {
        return $this->errFile;
    }

    public function getErrors()
    {
        if (file_exists($this->errFile)) {
            clearstatcache(true, $this->errFile);
            if (filesize($this->errFile) !== 0) {
                return file_get_contents($this->errFile);
            }
        }

        return false;
    }

    public function getProgress()
    {
        if ($this->getErrors() !== false) {
            //return -1;
        }

        // parse process progression
        if (!file_exists($this->logFile)) {
            return '';
            //throw new \Exception("Error: File not exists '{$this->logFile}'", 1);
        }
        $f = fopen($this->logFile, 'r');
        $cursor = -1;

        fseek($f, $cursor, SEEK_END);
        $char = fgetc($f);

        /**
         * Trim trailing newline chars of the file
         */
        while ($char === "\n" || $char === "\r") {
            fseek($f, $cursor--, SEEK_END);
            $char = fgetc($f);
        }

        /**
         * Read until the start of file or first newline char
         */
        $buffer = '';
        while ($char !== false && $char !== "\n" && $char !== "\r") {
            /**
             * Prepend the new char
             */
            $buffer = $char . $buffer;
            fseek($f, $cursor--, SEEK_END);
            $char = fgetc($f);
        }

        if (preg_match('/(\d{1,3})\.\d{2}%/', $buffer, $matches)) {
            $percentage = $matches[1];
        } else {
            $percentage = 0;
        }

        return $percentage;
    }

    public function cleanup()
    {
        unlink($this->logFile);
        unlink($this->errFile);
        unlink($this->getFilePath());
    }

    public function getFilePath()
    {
        return $this->path . '/' . $this->task;
    }
}
