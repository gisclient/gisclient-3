<?php

namespace GisClient\SQLite\Export;

use GisClient\Author\Layer;
use GisClient\Author\Catalog;

class Process
{
    private $logDir;
    //ogr2ogr -f SQLite db.sqlite  PG:"dbname=milano host=127.0.0.1 port=5432 user=milano password=milanox" -sql "SELECT di_id, di_label, the_geom FROM district_area WHERE do_id = 1" -nln district_area
    public function __construct($logDir)
    {
        if (is_writable($logDir)) {
            $this->logDir = $logDir;
        } else {
            throw new \Exception("Error: Directory not exists or not writable '$logDir'", 1);
        }
    }

    public function start(Layer $layer)
    {
        $catalog = $layer->getCatalog();
        if ($catalog->getConnectionType() == Catalog::POSTGIS_CONNECTION) {
            $dbParams = \GCApp::getDataDBParams($catalog->getPath());

            $dbParams['db_host'] = DB_HOST;
            $dbParams['db_port'] = DB_PORT;
            $dbParams['db_user'] = defined('MAP_USER') ? MAP_USER : DB_USER;
            $dbParams['db_pass'] = defined('MAP_USER') ? MAP_PWD : DB_PWD;
            $dbParams['db_host'] = DB_HOST;

            $connectionTpl = 'PG:"host=%s port=%s user=%s password=%s dbname=%s schema=%s"';
            $connection = sprintf(
                $connectionTpl,
                $dbParams['db_host'],
                $dbParams['db_port'],
                $dbParams['db_user'],
                $dbParams['db_password'],
                $dbParams['db_name'],
                $dbParams['schema']
            );
            print_r($a);
        } else {
            throw new Exception("Connection type not supported", 1);
        }

        //$layer->table
        //$layer->fields
        //$layer->filter

        //using gdal 1.X
        $cmdTpl = "ogr2ogr -f SQLite -update %s %s";
        $cmd = sprintf($cmdTpl, $filename, $source);
    }
}
