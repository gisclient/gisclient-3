<?php

namespace GisClient\Author;

class Layer implements LayerLevelInterface
{
    private $db;
    private $data;

    public function __construct($id = null)
    {
        if ($id) {
            $this->db = new Db();

            $sql = "SELECT * FROM {$this->db->getParams()['schema']}.layer WHERE layer_id = ?";
            $stmt = $this->db->getDb()->prepare($sql);
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

    public function getFields()
    {
        $fields = null;
        if (!empty($this->data)) {
            $fields = array();

            $sql = "SELECT field_id FROM {$this->db->getParams()['schema']}.field WHERE layer_id = ?";
            $stmt = $this->db->getDb()->prepare($sql);
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

    public function getGeomSrid()
    {
        return $this->get('data_srid');
    }

    public function getId()
    {
        return $this->get('layer_id');
    }

    public function getLabelItem()
    {
        return $this->get('labelitem');
    }

    public function getLayerGroup()
    {
        return new LayerGroup($this->get('layergroup_id'));
    }

    public function getLinks()
    {
        $links = array();
        $sql = "SELECT link_id FROM {$this->db->getParams()['schema']}.layer_link WHERE layer_id = ?";

        $stmt = $this->db->getDb()->prepare($sql);
        $stmt->execute(array($this->data['layer_id']));
        while ($link_id = $stmt->fetchColumn(0)) {
            $links[] = new Link($link_id);
        }

        return $links;
    }

    /**
     * {@inheritdoc}
     */
    public function getChildren()
    {
        return [];
    }

    public function getName()
    {
        return $this->get('layer_name');
    }

    public function getTitle()
    {
        return $this->get('layer_title');
    }

    public function getOpacity()
    {
        return $this->get('opacity');
    }

    public function getPrimaryColumn()
    {
        return $this->get('data_unique');
    }

    public function getStyleClasses()
    {
        $classes = null;
        if (!empty($this->data)) {
            $classes = array();

            $sql = "SELECT class_id FROM {$this->db->getParams()['schema']}.class WHERE layer_id = ?";
            $stmt = $this->db->getDb()->prepare($sql);
            $stmt->execute(array($this->get('layer_id')));
            while ($class_id = $stmt->fetchColumn(0)) {
                $classes[] = new StyleClass($class_id);
            }
        }

        return $classes;
    }

    public function getTable()
    {
        return $this->get('data');
    }
}
