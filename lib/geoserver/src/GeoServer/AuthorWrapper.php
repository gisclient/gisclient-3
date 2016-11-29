<?php

namespace GisClient\GeoServer;

class AuthorWrapper
{

    /**
     * return the database connection
     * @return \PDO
     */
    public static function getDb()
    {
        return \GCApp::getDB();
    }

    /**
     * return the gisclient databae schema
     * @return string
     */
    public static function getGcSchema()
    {
        return DB_SCHEMA;
    }

    /**
     * return the gisclient root path
     * @return string
     */
    public static function getRootPath()
    {
        return ROOT_PATH;
    }

    /**
     * return the GeoServert root path (from gisclient configuration)
     * @return string
     */
    public static function getGeoserverRootPath()
    {
        return GEOSERVER_PATH;
    }

    /**
     * Check if the given project exists
     *
     * @param string $projectName               The projet name
     */
    public function assertProjectExists($projectName)
    {

        $db = self::getDb();
        $schema = self::getGcSchema();

        $sql = "SELECT true
                FROM {$schema}.project
                WHERE project_name=:project_name";
        $stmt = $db->prepare($sql);
        $stmt->execute(array(
            'project_name' => $projectName
        ));
        if ($stmt->fetchColumn() !== true) {
            throw new \Exception("Project \"{$projectName}\" doesn't exists");
        }
    }

    /**
     * Check if the given project/mapset exists
     *
     * @param string $projectName               The projet name
     * @param string $mapsetName                The mapset name
     *
     */
    public function assertMapsetExists($projectName, $mapsetName)
    {

        $db = self::getDb();
        $schema = self::getGcSchema();

        $sql = "SELECT true
                FROM {$schema}.mapset
                WHERE project_name=:project_name AND
                      mapset_name=:mapset_name";
        $stmt = $db->prepare($sql);
        $stmt->execute(array(
            'project_name' => $projectName,
            'mapset_name' => $mapsetName
        ));
        if ($stmt->fetchColumn() !== true) {
            throw new \Exception("Mapset \"{$mapsetName}\" doesn't exists for project \"{$projectName}\"");
        }
    }

    /**
     * Return the list of the mapset to sync
     *
     * @param string $projectName               The projet name
     * @param string $mapsetName                The mapset name. If null, all mapsets are re-synchronyzed
     * @return array
     *
     */
    public function getMapsetList($projectName)
    {

        $db = self::getDb();
        $schema = self::getGcSchema();

        $sql = "SELECT mapset_name
                FROM {$schema}.mapset
                WHERE project_name=:project_name
                ORDER BY mapset_name";
        $stmt = $db->prepare($sql);
        $stmt->execute(array(
            'project_name' => $projectName
        ));
        return $stmt->fetchAll();
    }

    /**
     * Return the catalog list (eg database connection)
     *
     * @param string $projectName
     * @return array
     */
    public function getCatalogList($projectName)
    {
        $db = self::getDb();
        $schema = self::getGcSchema();

        $sql = "SELECT catalog_name, catalog_path, catalog_url, catalog_description, files_path, conntype_name
                FROM {$schema}.catalog
                INNER JOIN {$schema}.e_conntype ON connection_type=conntype_id
                WHERE project_name=:project_name
                ORDER BY catalog_name";
        $stmt = $db->prepare($sql);
        $stmt->execute(array(
            'project_name' => $projectName
        ));
        return $stmt->fetchAll();
    }

    /**
     * Return the catalog list (eg database connection)
     *
     * @param string $projectName
     * @param string $mapsetName
     * @return array
     */
    public function getCatalogListForMapset($projectName, $mapsetName)
    {
        $db = self::getDb();
        $schema = self::getGcSchema();

        $sql = "SELECT DISTINCT catalog_name, catalog_path, catalog_url, catalog_description, files_path, conntype_name
                FROM {$schema}.catalog
                INNER JOIN {$schema}.e_conntype ON connection_type=conntype_id
                INNER JOIN gisclient_34.layer USING(catalog_id)
                INNER JOIN gisclient_34.layergroup USING(layergroup_id)
                INNER JOIN gisclient_34.mapset_layergroup USING(layergroup_id)
                WHERE project_name=:project_name AND mapset_name=:mapset_name
                ORDER BY catalog_name";
        $stmt = $db->prepare($sql);
        $stmt->execute(array(
            'project_name' => $projectName,
            'mapset_name' => $mapsetName
        ));
        return $stmt->fetchAll();
    }

    /**
     * Get the layer list for the given project
     *
     * @param type $projectName
     * @return array
     */
    public function getLayerList($projectName)
    {
        $db = self::getDb();
        $schema = self::getGcSchema();
        $sql = "SELECT DISTINCT theme_name, layergroup_name, layer_name, layer_title, data
                    FROM {$schema}.layer
                    INNER JOIN {$schema}.layergroup USING(layergroup_id)
                    INNER JOIN {$schema}.e_layertype USING(layertype_id)
                    INNER JOIN {$schema}.catalog USING(catalog_id)
                    INNER JOIN {$schema}.theme USING(theme_id)
                    WHERE theme.project_name=:project_name AND catalog.project_name=:project_name
                    ORDER BY theme_name, layergroup_name, layer_name";
        $stmt = $db->prepare($sql);
        $stmt->execute(array(
            'project_name' => $projectName
        ));
        return $stmt->fetchAll();
    }

    /**
     * Get the layer list for the given project, filtered for mapset
     *
     * @param string $projectName          the project name
     * @param string|array $mapsetList     one or more mapset
     * @return array
     */
    public function getLayerListForMapset($projectName, $mapsetList)
    {
        $db = self::getDb();
        $schema = self::getGcSchema();
        if (!is_array($mapsetList)) {
            $mapsetList = array($mapsetList);
        }
        $mapsetListQuoted = array();

        foreach ($mapsetList as $mapset) {
            $mapsetListQuoted[] = $db->quote($mapset);
        }
        $mapsetParsed = implode(', ', $mapsetListQuoted);

        $sql = "SELECT DISTINCT catalog_name, conntype_name, theme_name, layergroup_name, layer_name, layer_title, data
                    FROM {$schema}.layer
                    INNER JOIN {$schema}.layergroup USING(layergroup_id)
                    INNER JOIN {$schema}.e_layertype USING(layertype_id)
                    INNER JOIN {$schema}.catalog USING(catalog_id)
                    INNER JOIN {$schema}.e_conntype ON connection_type=conntype_id
                    INNER JOIN {$schema}.theme USING(theme_id)
                    INNER JOIN {$schema}.mapset_layergroup USING(layergroup_id)
                    WHERE theme.project_name=:project_name
                          AND catalog.project_name=:project_name
                          AND mapset_name IN ({$mapsetParsed})
                    ORDER BY catalog_name, conntype_name, theme_name, layergroup_name, layer_name";
        $stmt = $db->prepare($sql);
        $stmt->execute(array(
            'project_name' => $projectName
        ));
        return $stmt->fetchAll();
    }

    /**
     * Return the layer list of the given layergroup/layer
     *
     * @param string $layergroupName
     * @param string $layerName
     * @return array
     */
    public function getLayerData($layergroupName, $layerName)
    {
        $db = self::getDb();
        $schema = self::getGcSchema();

        $sql = "SELECT layer_title, data_type, *
                FROM {$schema}.layer
                INNER JOIN {$schema}.layergroup USING(layergroup_id)
                WHERE layergroup_name=:layergroup_name AND layer_name=:layer_name
                ORDER BY 1";
        $stmt = $db->prepare($sql);
        $stmt->execute(array('layergroup_name' => $layergroupName, 'layer_name' => $layerName));
        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }

    /**
     * Return the class of the layer
     * 
     * @param string $layergroupName
     * @param string $layerName
     * @return array
     */
    public function getLayerClass($layergroupName, $layerName)
    {

        $db = self::getDb();
        $schema = self::getGcSchema();

        $sql = "SELECT layergroup_name, layer_name, layertype_name, class_name, class_id, class_title, data_filter, expression, layer.opacity
                FROM {$schema}.layer
                INNER JOIN {$schema}.layergroup USING(layergroup_id)
                INNER JOIN {$schema}.e_layertype USING(layertype_id)
                INNER JOIN {$schema}.class USING(layer_id)
                WHERE layergroup_name=:layergroup_name AND layer_name=:layer_name
                ORDER BY class_order";
        $stmt = $db->prepare($sql);
        $stmt->execute(array('layergroup_name' => $layergroupName, 'layer_name' => $layerName));
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Return the class of the layer
     *
     * @param string $layergroupName
     * @param string $layerName
     * @return array
     */
    public function getStylesByClassId($classId)
    {

        $db = self::getDb();
        $schema = self::getGcSchema();

        $sql = "SELECT symbol_name, color, outlinecolor, width, size
                FROM {$schema}.style
                WHERE class_id=:class_id
                ORDER BY style_order DESC";
        $stmt = $db->prepare($sql);
        $stmt->execute(array('class_id' => $classId));
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Return the class of the layer
     *
     * @param string $layergroupName
     * @param string $layerName
     * @return array
     */
    public function getSymbolDef($symbolName)
    {

        $db = self::getDb();
        $schema = self::getGcSchema();

        $sql = "SELECT *
                FROM {$schema}.symbol
                INNER JOIN {$schema}.e_symbolcategory USING(symbolcategory_id)
                WHERE symbol_name=:symbol_name";
        $stmt = $db->prepare($sql);
        $stmt->execute(array('symbol_name' => $symbolName));
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        if ($result === false) {
            return null;
        }
        return $result;
    }
}