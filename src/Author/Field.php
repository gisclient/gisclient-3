<?php

namespace GisClient\Author;

class Field
{
    private $db;
    private $data;

    public function __construct($id = null)
    {
        if ($id) {
            $this->db = new Db();
            
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
}
