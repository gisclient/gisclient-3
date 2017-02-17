<?php

namespace GisClient\Author;

class LayerGroup
{
    private $db;
    private $data;

    public function __construct($id = null)
    {
        if ($id) {
            $this->db = \GCApp::getDB();

            $schema = DB_SCHEMA;
            $sql = "SELECT * FROM {$schema}.layergroup WHERE layergroup_id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute(array($id));
            $data = $stmt->fetch();
            if (!empty($data)) {
                $this->data = $data;
            } else {
                throw new Exception("Error: layergroup with id = '$id' not found", 1);
            }
        }
    }

    public function getLayers()
    {
        $layers = null;
        if (!empty($this->data)) {
            $layers = array();

            $schema = DB_SCHEMA;
            $sql = "SELECT layer_id FROM {$schema}.layer WHERE layergroup_id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute(array($this->data['layergroup_id']));
            while ($layer_id = $stmt->fetchColumn(0)) {
                $layers[] = new Layer($layer_id);
            }
        }

        return $layers;
    }
}
