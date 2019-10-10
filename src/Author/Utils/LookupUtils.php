<?php

namespace GisClient\Author\Utils;

class LookupUtils
{
    /**
     * @var \PDO
     */
    private $database;

    public function __construct()
    {
        $this->database = \GCApp::getDB();
    }
    
    /**
     * Get the symbol image
     *
     * @param integer $catalogId
     * @param string $table
     * @param string $columnForValue
     * @param string $columnForLabel
     * @return array
     */
    public function getList($catalogId, $table, $columnForValue, $columnForLabel)
    {
        $sql = 'select catalog_path from '.DB_SCHEMA.'.catalog where catalog_id=?';
        $stmt = $this->database->prepare($sql);
        $stmt->execute(array($catalogId));
        $catalogPath = $stmt->fetchColumn(0);
        list($connStr,$schema)=connAdminInfofromPath($catalogPath);
        $dataDb = \GCApp::getDataDB($catalogPath);
        
        $sql = 'select table_name from information_schema.tables where table_schema=:schema and table_name=:table';
        $stmt = $dataDb->prepare($sql);
        $stmt->execute(array(':schema'=>$schema, ':table'=>$table));
        $dbTableName = $stmt->fetchColumn(0);
        if ($dbTableName != $table) {
            throw new \Exception(sprintf('Table "%s" does not exists', $table));
        }
        
        $sql = 'select column_name from information_schema.columns
            where table_schema=:schema and table_name=:table and column_name=:column';
        $stmt = $dataDb->prepare($sql);
        $stmt->execute(array(':schema'=>$schema, ':table'=>$table, ':column'=>$columnForValue));
        $dbColumnName = $stmt->fetchColumn(0);
        if ($dbColumnName != $columnForValue) {
            throw new \Exception(sprintf('Column "%s" does not exists', $columnForValue));
        }
        
        $sql = 'select column_name from information_schema.columns
            where table_schema=:schema and table_name=:table and column_name=:column';
        $stmt = $dataDb->prepare($sql);
        $stmt->execute(array(':schema'=>$schema, ':table'=>$table, ':column'=>$columnForLabel));
        $dbColumnName = $stmt->fetchColumn(0);
        if ($dbColumnName != $columnForLabel) {
            throw new \Exception(sprintf('Column "%s" does not exists', $columnForLabel));
        }
        
        $sql = 'select '.$columnForValue.' as id, '.$columnForLabel.' as name
            from '.$schema.'.'.$table.' order by '.$columnForLabel;
        $rows = $dataDb->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
        $data = array();
        foreach ($rows as $row) {
            $data[$row['id']] = $row['name'];
        }

        return $data;
    }
}
