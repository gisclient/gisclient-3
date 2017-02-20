<?php

namespace GisClient\GDAL\Export\SQLite;

use GisClient\Author\Layer;
use GisClient\Author\Catalog;

class Task extends \GisClient\GDAL\Export\Task
{
    private $logFile;
    private $errFile;
    private $layer;

    public function __construct(Layer $layer, $logDir)
    {
        $this->layer = $layer;

        if (is_writable($logDir)) {
            $this->logFile = $logDir . $this->getTaskName() . '.log';
            $this->errFile = $logDir . $this->getTaskName() . '.err';
        } else {
            throw new \Exception("Error: Directory not exists or not writable '$logDir'", 1);
        }
    }

    public function getTaskName()
    {
        $name = "{$this->layer->getName()}.{$this->layer->getId()}";
        return $name;
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

        if (preg_match('/(\d{1,3})\.\d{2}%/', $buffer, $matches)) {
            $percentage = $matches[1];
        } else {
            $percentage = 0;
        }

        return $percentage;
    }

    public function getSource()
    {
        $catalog = $this->layer->getCatalog();
        if ($catalog->getConnectionType() == Catalog::POSTGIS_CONNECTION) {
            $dbParams = \GCApp::getDataDBParams($catalog->getPath());

            $dbParams['db_host'] = DB_HOST;
            $dbParams['db_port'] = DB_PORT;
            $dbParams['db_user'] = defined('MAP_USER') ? MAP_USER : DB_USER;
            $dbParams['db_pass'] = defined('MAP_USER') ? MAP_PWD : DB_PWD;

            $connectionTpl = 'PG:"host=%s port=%s user=%s password=%s dbname=%s schemas=%s"';
            $connection = sprintf(
                $connectionTpl,
                $dbParams['db_host'],
                $dbParams['db_port'],
                $dbParams['db_user'],
                $dbParams['db_pass'],
                $dbParams['db_name'],
                $dbParams['schema']
            );
        } else {
            throw new Exception("Connection type not supported", 1);
        }

        $table = $this->layer->getTable();
        $fields = $this->layer->getFields();

        $fieldsText = '';
        foreach ($fields as $field) {
            $fieldsText .= $field->getName() . ',';
        }
        $fieldsText .= $this->layer->getGeomColumn();

        $filter = $this->layer->getFilter();

        $name = $this->layer->getName();
        
        $sqlTpl = '-sql "SELECT %s FROM %s WHERE %s" -nln %s';
        $sql = sprintf(
            $sqlTpl,
            $fieldsText,
            $table,
            $filter,
            $name
        );

        $source = $connection . ' ' . $sql;

        return $source;
    }

    public function getFileName()
    {
        return ROOT_PATH . "tmp/{$this->getTaskName()}.sqlite";
    }
}
