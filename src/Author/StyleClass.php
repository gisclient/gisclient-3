<?php

namespace GisClient\Author;

class StyleClass
{
    private $db;
    private $data;

    public function __construct($id = null)
    {
        if ($id) {
            $this->db = new Db();

            $sql = "SELECT * FROM {$this->db->getParams()['schema']}.class WHERE class_id = ?";
            $stmt = $this->db->getDb()->prepare($sql);
            $stmt->execute(array($id));
            $data = $stmt->fetch();
            if (!empty($data)) {
                $this->data = $data;
            } else {
                throw new \Exception("Error: style class with id = '$id' not found", 1);
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

    public function getExpression()
    {
        return $this->get('expression');
    }

    public function getId()
    {
        return $this->get('class_id');
    }

    public function getName()
    {
        return $this->get('class_name');
    }

    public function getStyles()
    {
        $styles = null;
        if (!empty($this->data)) {
            $styles = array();

            $sql = "SELECT style_id FROM {$this->db->getParams()['schema']}.style WHERE class_id = ?";
            $stmt = $this->db->getDb()->prepare($sql);
            $stmt->execute(array($this->get('class_id')));
            while ($style_id = $stmt->fetchColumn(0)) {
                $styles[] = new Style($style_id);
            }
        }

        return $styles;
    }
}
