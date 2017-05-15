<?php

namespace GisClient\Author;

class FieldType
{
    private $db;
    private $data;

    public function __construct($id = null)
    {
        if ($id) {
            $this->db = new Db();

            $sql = "SELECT * FROM {$this->db->getParams()['schema']}.e_fieldtype WHERE fieldtype_id = ?";
            $stmt = $this->db->getDb()->prepare($sql);
            $stmt->execute(array($id));
            $data = $stmt->fetch();
            if (!empty($data)) {
                $this->data = $data;
            } else {
                throw new \Exception("Error: field with id = '$id' not found", 1);
            }
        }
    }

    private function get($value)
    {
        if (!empty($this->data)) {
            return $this->data[$value];
        } else {
            throw new \Exception("Error: property '$value' not found", 1);
        }
    }

    public function getName()
    {
        return $this->get('fieldtype_name');
    }
}
