<?php

namespace GisClient\Author;

class Theme
{
    private $db;
    private $data;

    public function __construct($id = null)
    {
        if ($id) {
            $this->db = \GCApp::getDB();

            $schema = DB_SCHEMA;
            $sql = "SELECT * FROM {$schema}.theme WHERE theme_id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute(array($id));
            $data = $stmt->fetch();
            if (!empty($data)) {
                $this->data = $data;
            } else {
                throw new Exception("Error: theme with id = '$id' not found", 1);
            }
        }
    }

    private function get($value)
    {
        if (empty($this->data)) {
            throw new Exception("Error: failed initialization", 1);
        }

        if (isset($this->data[$value])) {
            return $this->data[$value];
        } else {
            throw new Exception("Error: property '$value' not found", 1);
        }
    }

    public function getName()
    {
        return $this->get('theme_name');
    }
}
