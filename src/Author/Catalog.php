<?php
namespace GisClient\Author;

class Catalog
{
    const LOCAL_FOLDER_CONNECTION = 1;
    const POSTGIS_CONNECTION = 6;
    const WMS_CONNECTION = 7;
    const WFS_CONNECTION = 9;

    private $db;
    private $data;

    public function __construct($id = null)
    {
        if ($id) {
            $this->db = \GCApp::getDB();

            $schema = DB_SCHEMA;
            $sql = "SELECT * FROM {$schema}.catalog WHERE catalog_id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute(array($id));
            $data = $stmt->fetch();
            if (!empty($data)) {
                $this->data = $data;
            } else {
                throw new Exception("Error: catalog with id = '$id' not found", 1);
            }
        }
    }

    public function getConnectionType()
    {
        return $this->data['connection_type'];
    }

    public function getPath()
    {
        return $this->data['catalog_path'];
    }
}
