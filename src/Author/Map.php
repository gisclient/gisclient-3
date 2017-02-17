<?php

namespace GisClient\Author;

class Map
{
    private $db;
    private $data;

    public function __construct($projectName, $mapName)
    {
        $this->db = \GCApp::getDB();

        $schema = DB_SCHEMA;
        $sql = "SELECT COUNT(*) FROM {$schema}.project WHERE project_name = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(array($projectName));
        if ($stmt->fetchColumn(0) !== 1) {
            throw new Exception("Error: '$projectName' not found", 1);
        }

        $schema = DB_SCHEMA;
        $sql = "SELECT * FROM {$schema}.mapset WHERE project_name = ? AND mapset_name = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(array($projectName, $mapName));
        $data = $stmt->fetch();
        if (!empty($data)) {
            $this->data = $data;
        } else {
            throw new Exception("Error: '$mapName' not found in project '$projectName'", 1);
        }
    }

    public function getLayerGroups()
    {
        $layerGroups = array();

        $schema = DB_SCHEMA;
        $sql = "SELECT layergroup_id FROM {$schema}.mapset_layergroup WHERE mapset_name = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(array($this->data['mapset_name']));
        while ($layergroup_id = $stmt->fetchColumn(0)) {
            $layerGroups[] = new LayerGroup($layergroup_id);
        }

        return $layerGroups;
    }
}
