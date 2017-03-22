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
            throw new \Exception("Error: project '$projectName' not found", 1);
        }

        $sql = "SELECT * FROM {$schema}.mapset WHERE project_name = ? AND mapset_name = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(array($projectName, $mapName));
        $data = $stmt->fetch();
        if (!empty($data)) {
            $this->data = $data;
        } else {
            throw new \Exception("Error: map '$mapName' not found in project '$projectName'", 1);
        }
    }

    private function get($value)
    {
        if (empty($this->data)) {
            throw new \Exception("Error: failed initialization", 1);
        }

        if (isset($this->data[$value])) {
            return $this->data[$value];
        } else {
            throw new \Exception("Error: property '$value' not found", 1);
        }
    }

    public function getProject()
    {
        return $this->get('project_name');
    }

    public function getName()
    {
        return $this->get('mapset_name');
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

    public function getThemes()
    {
        $themes = array();

        $schema = DB_SCHEMA;
        $sql = "SELECT DISTINCT theme_id "
            . " FROM {$schema}.theme "
            . " INNER JOIN {$schema}.layergroup USING(theme_id) "
            . " WHERE layergroup_id IN ("
            . "     SELECT layergroup_id "
            . "     FROM {$schema}.mapset_layergroup "
            . "     WHERE mapset_name = ? "
            . ") "
            . " AND project_name = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(array($this->data['mapset_name'], $this->data['project_name']));
        while ($theme_id = $stmt->fetchColumn(0)) {
            $themes[] = new Theme($theme_id, $this->data['mapset_name']);
        }

        return $themes;
    }
}
