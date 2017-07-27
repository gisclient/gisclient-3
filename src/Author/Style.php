<?php

namespace GisClient\Author;

class Style
{
    private $db;
    private $data;

    public function __construct($id = null)
    {
        if ($id) {
            $this->db = new Db();

            $sql = "SELECT * FROM {$this->db->getParams()['schema']}.style WHERE style_id = ?";
            $stmt = $this->db->getDb()->prepare($sql);
            $stmt->execute(array($id));
            $data = $stmt->fetch();
            if (!empty($data)) {
                $this->data = $data;
            } else {
                throw new \Exception("Error: style with id = '$id' not found", 1);
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

    public function getBackgroundColor()
    {
        return $this->get('bgcolor');
    }

    public function getColor()
    {
        return $this->get('color');
    }

    public function getId()
    {
        return $this->get('style_id');
    }

    public function getName()
    {
        return $this->get('style_name');
    }

    public function getOutlineColor()
    {
        return $this->get('outlinecolor');
    }

    public function getPattern()
    {
        return new Pattern($this->get('pattern_id'));
    }

    public function getSize()
    {
        return $this->get('size');
    }
}
