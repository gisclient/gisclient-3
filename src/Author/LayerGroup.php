<?php

namespace GisClient\Author;

class LayerGroup extends AbstractLayerLevel
{
    private $db;
    private $data;

    const WMS_LAYER_TYPE = 1;
    const WMTS_LAYER_TYPE = 2;
    const WMS_CACHE_LAYER_TYPE = 3;
    const VMAP_LAYER_TYPE = 3;
    const YMAP_LAYER_TYPE = 4;
    const OSM_LAYER_TYPE = 5;
    const TMS_LAYER_TYPE = 6;
    const GMAP_LAYER_TYPE = 7;
    const BING_LAYER_TYPE = 8;
    const XYZ_LAYER_TYPE = 9;
    const WFS_LAYER_TYPE = 10;

    const PNG24_FORMAT = 1;
    const PNG8_FORMAT = 7;
    const PNG_FORMAT = 9;
    const JPEG_FORMAT = 3;
    const GEOJSON_FORMAT = 10;

    public function __construct($id = null)
    {
        if ($id) {
            $this->db = new Db();

            $sql = "SELECT * FROM {$this->db->getParams()['schema']}.layergroup WHERE layergroup_id = ?";
            $stmt = $this->db->getDb()->prepare($sql);
            $stmt->execute(array($id));
            $data = $stmt->fetch();
            if (!empty($data)) {
                $this->data = $data;
            } else {
                throw new \Exception("Error: layergroup with id = '$id' not found", 1);
            }
        }
    }

    private function get($value)
    {
        if (empty($this->data)) {
            throw new \Exception("Error: failed initialization", 1);
        }

        if (isset($this->data[$value])) {
            return $this->data[$value];
        } else {
            throw new \Exception("Error: property '$value' not found", 1);
        }
    }

    public function getType()
    {
        return $this->get('owstype_id');
    }

    public function getFormat()
    {
        return $this->get('outputformat_id');
    }

    /**
     * {@inheritdoc}
     */
    public function getChildren()
    {
        return $this->getLayers();
    }

    public function getLayers()
    {
        $layers = null;
        if (!empty($this->data)) {
            $layers = array();

            $sql = "SELECT layer_id FROM {$this->db->getParams()['schema']}.layer WHERE layergroup_id = ?";
            $stmt = $this->db->getDb()->prepare($sql);
            $stmt->execute(array($this->data['layergroup_id']));
            while ($layer_id = $stmt->fetchColumn(0)) {
                $layer = new Layer($layer_id);
                $layer->setMap($this->getMap());
                $layers[] = $layer;
            }
        }

        return $layers;
    }

    public function getName()
    {
        return $this->get('layergroup_name');
    }

    public function getTitle()
    {
        return $this->get('layergroup_title');
    }

    public function getOpacity()
    {
        return $this->get('opacity');
    }
}
