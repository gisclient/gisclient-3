<?php

namespace GisClient\SQLite\Export;

use GisClient\Author\Layer;
use GisClient\Author\Catalog;

class Process
{
    private $logDir;
    //ogr2ogr -f SQLite db.sqlite  PG:"dbname=milano host=127.0.0.1 port=5432 user=milano password=milanox"
    //-sql "SELECT di_id, di_label, the_geom FROM district_area WHERE do_id = 1" -nln district_area
    public function __construct($logDir)
    {
        if (is_writable($logDir)) {
            $this->logDir = $logDir;
        } else {
            throw new \Exception("Error: Directory not exists or not writable '$logDir'", 1);
        }
    }

    private function getComand(Layer $layer)
    {
        $catalog = $layer->getCatalog();
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

        $table = $layer->getTable();
        $fields = $layer->getFields();

        $fieldsText = '';
        foreach ($fields as $field) {
            $fieldsText .= $field->getName() . ',';
        }
        $fieldsText .= $layer->getGeomColumn();

        $filter = $layer->getFilter();

        $name = $layer->getName();
        
        $sqlTpl = '-sql "SELECT %s FROM %s WHERE %s" -nln %s';
        $sql = sprintf(
            $sqlTpl,
            $fieldsText,
            $table,
            $filter,
            $name
        );

        $source = $connection . ' ' . $sql;

        $id = $layer->getId();
        $filename = ROOT_PATH . "tmp/{$name}.{$id}.sqlite";
        
        //using gdal 1.X
        $cmdTpl = "ogr2ogr -f SQLite %s %s -overwrite > %s 2> %s & echo $!";
        $cmd = sprintf(
            $cmdTpl,
            $filename,
            $source,
            $this->logDir . "{$name}.{$id}.log",
            $this->logDir . "{$name}.{$id}.err"
        );
        
        return $cmd;
    }

    public function start(Layer $layer)
    {
        $pid = shell_exec($this->getComand($layer));

        return $pid;
    }
}
