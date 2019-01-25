<?php

namespace GisClient\MapProxy\Seed;

class Task
{
    private $logFile;
    private $errFile;
    private $project;
    private $task;

    public function __construct($project, $taskName, $logDir)
    {
        $this->project = $project;
        $this->task = $taskName;
        $this->logFile = $logDir . $this->task . '-seed.log';
        $this->errFile = $logDir . $this->task . '-seed.err';
    }

    public function getTaskName()
    {
        return $this->task;
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
        return MAPPROXY_CACHE_PATH . "{$this->project}/{$this->task}.mbtiles";
    }
}
