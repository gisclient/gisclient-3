<?php

namespace GisClient\GDAL\Export\MVT;

use GisClient\Author\Layer;
use GisClient\Author\Catalog;
use GisClient\Author\Db;

class Task implements \GisClient\GDAL\Export\Task
{
    private $logFile;
    private $errFile;
    private $layer;
    private $taskName;
    private $path;

    public function __construct(Layer $layer, $filename, $logDir)
    {
        $this->path = dirname($filename);
        $this->layer = $layer;
        $this->taskName = basename($filename);
        $this->logFile = $logDir . $this->getTaskName() . '.log';
        $this->errFile = $logDir . $this->getTaskName() . '.err';

        if (!is_dir($this->path)) {
            if (!mkdir($this->path, 0700, true)) {
                throw new \Exception("Error: Failed to create {$this->path}", 1);
            }
        }
    }

    public function getTaskName()
    {
        return basename($this->taskName, '.sqlite');
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
            throw new \Exception("Error: File not exists '{$this->logFile}'", 1);
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
        while ($char !== false && $char !== "\n" && $char !== "\r") {
            /**
             * Prepend the new char
             */
            $buffer = $char . $buffer;
            fseek($f, $cursor--, SEEK_END);
            $char = fgetc($f);
        }

        if (preg_match('/.*\.(\d{1,3})/', $buffer, $matches)) {
            $percentage = $matches[1];
        } else {
            $percentage = 0;
        }

        return $percentage;
    }

    public function getSource()
    {
        $db = new Db($this->layer->getCatalog());
        $dbParams = $db->getParams();

        $connectionTpl = 'PG:host=%s port=%s user=%s password=%s dbname=%s schemas=%s';
        $connection = sprintf(
            $connectionTpl,
            $dbParams['db_host'],
            $dbParams['db_port'],
            $dbParams['db_user'],
            $dbParams['db_pass'],
            $dbParams['db_name'],
            $dbParams['schema']
        );

        $table = $this->layer->getTable();
        $fields = $this->layer->getFields();

        $fieldsText = '';
        foreach ($fields as $field) {
            $fieldsText .= $field->getName() . ',';
        }
        $fieldsText .= $this->layer->getGeomColumn();

        $filter = $this->layer->getFilter();
        if (!$filter) {
            $filter = 'true';
        }

        // apply filter on current extent
        $extent = $this->layer->getMap()->getExtent();
        $filter .= sprintf(
            " AND ST_Intersects(%s, ST_MakeEnvelope(%d, %d, %d, %d, %d))",
            $this->layer->getGeomColumn(),
            $extent[0],
            $extent[1],
            $extent[2],
            $extent[3],
            $this->layer->getMap()->getSrid()
        );

        $name = $this->layer->getName();
        
        $sqlTpl = 'SELECT %s FROM %s WHERE %s';
        $sql = sprintf(
            $sqlTpl,
            $fieldsText,
            $table,
            $filter
        );

        $commandLine = [
            $connection,
            '-sql',
            $sql,
            '-nln',
            $name
        ];

        return $commandLine;
    }

    public function getFilePath()
    {
        return $this->path . '/' . $this->taskName;
    }

    public function cleanup()
    {
        if (file_exists($this->logFile)) {
            unlink($this->logFile);
        }
        if (file_exists($this->errFile)) {
            unlink($this->errFile);
        }
        if (file_exists($this->getFilePath())) {
            unlink($this->getFilePath());
        }
    }
}