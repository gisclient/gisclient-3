<?php

namespace GisClient\Author;

class Layer
{
    private $db;
    private $data;

    public function __construct($id = null)
    {
        if ($id) {
            $this->db = \GCApp::getDB();

            $schema = DB_SCHEMA;
            $sql = "SELECT * FROM {$schema}.layer WHERE layer_id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute(array($id));
            $data = $stmt->fetch();
            if (!empty($data)) {
                $this->data = $data;
            } else {
                throw new Exception("Error: layer with id = '$id' not found", 1);
            }
        }
    }

    public function getCatalog()
    {
        if (!empty($this->data)) {
            return new Catalog($this->data['catalog_id']);
        }
    }
}
