<?php

namespace GisClient\Author;

class Field
{
    private $db;
    private $data;

    public function __construct($id = null)
    {
        $this->db = new Db();
        if ($id) {
            $sql = "SELECT * FROM {$this->db->getParams()['schema']}.field WHERE field_id = ?";
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

    public function getDataTypeId()
    {
        return $this->get('datatype_id');
    }

    public function getFormat()
    {
        return $this->get('field_format');
    }

    public function getLookupId()
    {
        return $this->get('lookup_id');
    }

    public function getLookupName()
    {
        return $this->get('lookup_name');
    }

    public function getLookupTable()
    {
        return $this->get('lookup_table');
    }

    public function getName()
    {
        return $this->get('field_name');
    }

    public function getType()
    {
        $fieldType = new FieldType($this->get('fieldtype_id'));
        
        return $fieldType->getName();
    }

    public function save(array $values)
    {
        if (array_key_exists('field_id', $values)) {
            //todo update
            throw new \Exception("not implemented", 1);
        } else {
            //insert
            $fields = implode(',', array_keys($values));
            $params = implode(',', array_values(array_fill(0, count($values), '?')));
            $values = array_values($values);

            $insertSql = "INSERT INTO {$this->db->getParams()['schema']}.field (field_id, $fields)"
                . " VALUES ("
                . " (SELECT MAX(field_id) + 1 FROM {$this->db->getParams()['schema']}.field as foo), $params"
                . " ) RETURNING *;";

            $stmt = $this->db->getDb()->prepare($insertSql);
            $stmt->execute($values);
            $data = $stmt->fetch();
            if (!empty($data)) {
                $this->data = $data;
            } else {
                throw new \Exception("Error: failed to insert", 1);
            }
        }
    }
}
