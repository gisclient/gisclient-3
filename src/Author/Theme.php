<?php

namespace GisClient\Author;

class Theme
{
    private $db;
    private $mapName;
    private $data;

    public function __construct($id = null, $mapName = null)
    {
        if ($id) {
            $this->db = new Db();

            $sql = "SELECT * FROM {$this->db->getParams()['schema']}.theme WHERE theme_id = ?";
            $stmt = $this->db->getDb()->prepare($sql);
            $stmt->execute(array($id));
            $data = $stmt->fetch();
            if (!empty($data)) {
                $this->data = $data;
            } else {
                throw new \Exception("Error: theme with id = '$id' not found", 1);
            }
        }
        
        $this->mapName = $mapName;
    }

    private function get($value)
    {
        if (empty($this->data)) {
            throw new \Exception("Error: failed initialization", 1);
        }

        if (isset($this->data[$value])) {
            return $this->data[$value];
        } else {
            return null;
            //throw new \Exception("Error: property '$value' not found", 1);
        }
    }

    public function getLayerGroups()
    {
        $layerGroups = array();

        $sql = "SELECT l.layergroup_id FROM {$this->db->getParams()['schema']}.layergroup l ";
        if (isset($this->mapName)) {
            $sql .= "INNER JOIN gisclient_34.mapset_layergroup m ";
            $sql .= "ON (l.layergroup_id = m.layergroup_id AND mapset_name = " . $this->db->getDb()->quote($this->mapName);
            $sql .= ") ";
        }
        $sql .= "WHERE theme_id = ?";

        $stmt = $this->db->getDb()->prepare($sql);
        $stmt->execute(array($this->data['theme_id']));
        while ($layergroup_id = $stmt->fetchColumn(0)) {
            $layerGroups[] = new LayerGroup($layergroup_id);
        }

        return $layerGroups;
    }

    public function getName()
    {
        return $this->get('theme_name');
    }

    public function getSymbolName()
    {
        return $this->get('symbol_name');
    }

    public function getTitle()
    {
        return $this->get('theme_title');
    }
}
