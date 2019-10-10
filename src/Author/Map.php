<?php

namespace GisClient\Author;

class Map implements LayerLevelInterface
{
    private $db;
    private $data;

    public function __construct($projectName, $mapName)
    {
        $this->db = new Db();

        $sql = "SELECT COUNT(*) FROM {$this->db->getParams()['schema']}.project WHERE project_name = ?";
        $stmt = $this->db->getDb()->prepare($sql);
        $stmt->execute(array($projectName));
        if ($stmt->fetchColumn(0) !== 1) {
            throw new \Exception("Error: project '$projectName' not found", 1);
        }

        $sql = "SELECT * FROM {$this->db->getParams()['schema']}.mapset WHERE project_name = ? AND mapset_name = ?";
        $stmt = $this->db->getDb()->prepare($sql);
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

    public function getTitle()
    {
        return $this->get('mapset_title');
    }

    /**
     * Return srid of the map
     *
     * @return integer
     */
    public function getSrid()
    {
        return $this->get('mapset_srid');
    }

    /**
     * Return extent of the map
     *
     * @return array
     */
    public function getExtent()
    {
        return explode(' ', $this->get('mapset_extent'));
    }

    /**
     * {@inheritdoc}
     */
    public function getChildren()
    {
        return $this->getThemes();
    }

    /**
     * {@inheritdoc}
     */
    public function getMap()
    {
        return self;
    }

    public function getLayerGroups()
    {
        $layerGroups = array();

        $sql = "SELECT layergroup_id FROM {$this->db->getParams()['schema']}.mapset_layergroup WHERE mapset_name = ?";
        $stmt = $this->db->getDb()->prepare($sql);
        $stmt->execute(array($this->data['mapset_name']));
        while ($layergroup_id = $stmt->fetchColumn(0)) {
            $layerGroup = new LayerGroup($layergroup_id);
            $layerGroup->setMap(self);
            $layerGroups[] = $layerGroup;
        }

        return $layerGroups;
    }

    public function getThemes()
    {
        $themes = array();

        $sql = "SELECT DISTINCT theme_id "
            . " FROM {$this->db->getParams()['schema']}.theme "
            . " INNER JOIN {$this->db->getParams()['schema']}.layergroup USING(theme_id) "
            . " WHERE layergroup_id IN ("
            . "     SELECT layergroup_id "
            . "     FROM {$this->db->getParams()['schema']}.mapset_layergroup "
            . "     WHERE mapset_name = ? "
            . ") "
            . " AND project_name = ?";
        $stmt = $this->db->getDb()->prepare($sql);
        $stmt->execute(array($this->data['mapset_name'], $this->data['project_name']));
        while ($theme_id = $stmt->fetchColumn(0)) {
            $theme = new Theme($theme_id, $this->data['mapset_name']);
            $theme->setMap($this);
            $themes[] = $theme;
        }

        return $themes;
    }
}
