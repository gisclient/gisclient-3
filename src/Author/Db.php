<?php

namespace GisClient\Author;

class Db
{
    private $catalog;

    public function __construct(Catalog $catalog = null)
    {
        $this->catalog = $catalog;
    }

    public function getParams()
    {
        if ($this->catalog) {
            if ($this->catalog->getConnectionType() == Catalog::POSTGIS_CONNECTION) {
                $dbParams = \GCApp::getDataDBParams($this->catalog->getPath());

                $dbParams['db_host'] = DB_HOST;
                $dbParams['db_port'] = DB_PORT;
                $dbParams['db_user'] = defined('MAP_USER') ? MAP_USER : DB_USER;
                $dbParams['db_pass'] = defined('MAP_USER') ? MAP_PWD : DB_PWD;
            } else {
                throw new \Exception("Connection type not supported", 1);
            }
        } else {
            $dbParams['db_host'] = DB_HOST;
            $dbParams['db_port'] = DB_PORT;
            $dbParams['db_user'] = DB_USER;
            $dbParams['db_pass'] = DB_PWD;
            $dbParams['schema'] = DB_SCHEMA;
        }

        return $dbParams;
    }

    public function getDb()
    {
        if ($this->catalog) {
            return \GCApp::getDataDB($this->catalog->getPath());
        } else {
            return \GCApp::getDB();
        }
    }
}
