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
                throw new \Exception("Error: layer with id = '$id' not found", 1);
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

    public function getCatalog()
    {
        return new Catalog($this->get('catalog_id'));
    }

    public function getCatalogId()
    {
        return $this->get('catalog_id');
    }

    public function getTable()
    {
        return $this->get('data');
    }

    public function getFields()
    {
        $fields = null;
        if (!empty($this->data)) {
            $fields = array();

            $schema = DB_SCHEMA;
            $sql = "SELECT field_id FROM {$schema}.field WHERE layer_id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute(array($this->get('layer_id')));
            while ($field_id = $stmt->fetchColumn(0)) {
                $fields[] = new Field($field_id);
            }
        }

        return $fields;
    }

    public function getFilter()
    {
        return $this->get('data_filter');
    }

    public function getGeomColumn()
    {
        return $this->get('data_geom');
    }

    public function getId()
    {
        return $this->get('layer_id');
    }

    public function getLinks()
    {
        $links = array();
        $schema = DB_SCHEMA;
        $sql = "SELECT link_id FROM {$schema}.layer_link WHERE layer_id = ?";

        $stmt = $this->db->prepare($sql);
        $stmt->execute(array($this->data['layer_id']));
        while ($link_id = $stmt->fetchColumn(0)) {
            $links[] = new Link($link_id);
        }

        return $links;
    }

    public function getName()
    {
        return $this->get('layer_name');
    }
}
