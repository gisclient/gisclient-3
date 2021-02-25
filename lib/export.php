<?php

class GCExport
{
    protected $type;
    protected $db;
    protected $exportPath;
    protected $exportUrl;
    protected $exportExtensions = array(
        'shp'=>array('shp','dbf','shx','prj','cpg')
    );
    
    public function __construct($db, $type, array $options = array())
    {
        $this->type = $type;
        $this->db = $db;
        $this->exportPath = ROOT_PATH . 'public/services/tmp/export/';
        $this->exportUrl = PUBLIC_URL . 'services/tmp/export/';
        $this->errorPath = DEBUG_DIR;
    }
    
    public function getExportUrl()
    {
        return $this->exportUrl;
    }
    
    public function export($tables, array $options = array())
    {
        $defaultOptions = array(
            'name' => 'export',
            'extent' => null,
            'srid' => null,
            'add_to_zip' => null,
            'return_url' => true
        );
        $options = array_merge($defaultOptions, $options);
        
        $files = array();
        
        if ($this->type == 'shp') {
            foreach ($tables as $tableSpec) {
                $exportOptions = array();
                if (!empty($options['fields'])) {
                    $exportOptions['fields'] = $options['fields'];
                }
                if (!empty($tableSpec['name'])) {
                    $exportOptions['name'] = $tableSpec['name'];
                }
                $layer = $this->_exportShp($tableSpec, $exportOptions);
                foreach ($layer as $niceName => $realName) {
                    $files[$niceName] = $realName;
                }
            }
        } else if ($this->type == 'dxf') {
            $exportGml = new GCExportGml($this->db, $options['extent'], $options['srid']);
            $gmlFile = $this->_getFileName($options['name']) . '.gml';

            foreach ($tables as $tableSpec) {
                if (empty($tableSpec['name'])) {
                    $tableSpec['name'] = $tableSpec['table'];
                }
                $exportGml->addLayer($tableSpec, $options['layer']->getPrimaryColumn(), $options['layer']->getGeomColumn());
            }

            $exportGml->export($this->exportPath . $gmlFile);
            $dxfFile = $this->_getFileName($options['name']) . '.dxf';
            $this->_exportDxf($gmlFile, $dxfFile);
            $files[$options['name'] . '.dxf'] = $this->exportPath . $dxfFile;
        } else if ($this->type == 'xls') {
            foreach ($tables as $tableSpec) {
                $exportOptions = array();
                if (!empty($options['fields'])) {
                    $exportOptions['fields'] = $options['fields'];
                }
                if (!empty($tableSpec['name'])) {
                    $exportOptions['name'] = $tableSpec['name'];
                }

                $file = $this->_exportXls($tableSpec, $exportOptions);
                $files[$exportOptions['name'] . '.xls'] = $file;
            }
        } else if ($this->type == 'kml') {
            foreach ($tables as $tableSpec) {
                $exportOptions = array();
                if (!empty($options['fields'])) {
                    $exportOptions['fields'] = $options['fields'];
                }
                if (!empty($tableSpec['name'])) {
                    $exportOptions['name'] = $tableSpec['name'];
                }

                $kml = new GCExportKml($this->db, $options['extent'], $options['srid']);
                foreach ($tables as $tableSpec) {
                    if (empty($tableSpec['name'])) {
                        $tableSpec['name'] = $tableSpec['table'];
                    }
                    $kml->addLayer($options['layer'], $tableSpec['schema'], $tableSpec['table'], $exportOptions);
                }

                $kmlFile = $this->exportPath . $this->_getFileName($options['name']) . '.kml';
                $kml->export($kmlFile);

                $files[$exportOptions['name'] . '.kml'] = $kmlFile;
            }
        }
                
        $zip = new ZipArchive;
        
        if ($options['add_to_zip']) {
            $zipName = $options['add_to_zip'];
            $openZipFlag = ZIPARCHIVE::CHECKCONS;
        } else {
            $zipName = $this->_getFileName($options['name']) . '.zip';
            $openZipFlag = ZIPARCHIVE::CREATE;
        }
        $options['add_to_zip'] = $zipName;
        $zipPath = $this->exportPath.$zipName;
        if ($zip->open($zipPath, $openZipFlag) !== true) {
            throw new Exception('Error creating zip file');
        }
        foreach ($files as $niceName => $realName) {
            if (!$zip->addFile($realName, $niceName)) {
                throw new Exception('Error adding file ' . $realName . ' to zip file');
            }
        }
        if (!$zip->close()) {
            throw new Exception('Error closing zip file');
        }
        foreach ($files as $niceName => $realName) {
            unlink($realName);
        }
        
        $return = $options['return_url'] ? $this->exportUrl.$zipName : $zipName;
        return $return;
    }
    
    protected function _exportShp(array $config, array $options = array())
    {
        $defaultOptions = array(
            'name' => $config['table']
        );
        $options = array_merge($defaultOptions, $options);
        
        $fileName = $this->_getFileName($options['name']);
        $filePath = $this->exportPath.$fileName;
        $errorFile = $this->errorPath.$fileName . '.err';

        $select = '';
        if (isset($options['fields'])) {
            $columns = array();
            if (isset($options['layer'])) {
                array_push($columns, $options['layer']->getGeomColumn());
            } else {
                array_push($columns, "the_geom");
            }

            foreach ($options['fields'] as $field) {
                array_push($columns, "{$field['field_name']} AS " . preg_replace('/[\W]/', '_', $field['title']));
            }
            $select = implode(', ', $columns);
        } else {
            $select = '*';
        }

        $exportDir = dirname($filePath);
        if (!file_exists($exportDir)) {
            throw new Exception(sprintf('The directory %s does not exists', $exportDir));
        }

        if (!is_writable($exportDir)) {
            throw new Exception(sprintf('The directory %s is not writable', $exportDir));
        }
        
        $cmd = 'pgsql2shp -f ' . escapeshellarg($filePath . '.shp')
            . ' -h ' . DB_HOST . ' -p ' . DB_PORT . ' -u ' . DB_USER . ' -P ' . DB_PWD
            . ' '.escapeshellarg($config['db'])
            . " \"SELECT {$select} FROM {$config['schema']}.{$config['table']}\""
            . ' 2> ' . escapeshellarg($errorFile);

        $pgsql2shpOutput = array();
        $retVal = -1;
        
        exec($cmd, $pgsql2shpOutput, $retVal);
        if ($retVal != 0) {
            $error = file_get_contents($errorFile);
            file_put_contents($errorFile, $cmd, FILE_APPEND);
            throw new Exception('Postgres to SHP error: '.$error);
        }
        // charset related operations
        if (($dbfFile = fopen($filePath . '.dbf', "r+")) === false) {
            throw new Exception('Unable to edit dbf encoding');
        }
        if (fseek($dbfFile, 29) === -1) {
            throw new Exception('Malformed dbf');
        }
        if (($ldid = fread($dbfFile, 1)) === false) {
            throw new Exception('Malformed dbf');
        }
        if ($ldid != chr(0)) {
            if (fseek($dbfFile, 29) === -1) {
                throw new Exception("Malformed dbf");
            }
            if (fwrite($dbfFile, chr(0)) === false) {
                throw new Exception("Malformed dbf");
            }
        }
        fclose($dbfFile);
        file_put_contents($filePath . '.cpg', 'UTF-8');
        
        $files = array();
        foreach ($this->exportExtensions['shp'] as $ext) {
            if (file_exists($filePath . '.' . $ext)) {
                $files[$options['name'] . '.' . $ext] = $filePath.'.'.$ext;
            }
        }
        return $files;
    }
    
    protected function _exportDxf($gmlFile, $dxfFile)
    {
        chdir('/usr/local/kabeja/');
        //$cmd = "java -Xmx512m -jar launcher.jar -main org.kabeja.gml.Main -template /data/sites/gc/author-giussano/config/prova.dxf ".
        $cmd = 'java -Xmx512m -jar launcher.jar -main org.kabeja.gml.Main ';
        if (defined('GC_DBT_CAD_TPL')) {
            $cmd .= ' -template ' . escapeshellarg(GC_DBT_CAD_TPL) . ' ';
        }
        $cmd .= escapeshellarg($this->exportPath . $gmlFile) . " " . escapeshellarg($this->exportPath . $dxfFile);
        exec($cmd, $output, $retval);
        if ($retval != 0) {
            throw new Exception("Could not convert GML to DXF: [return value: $retval]\n command was: [$cmd]\n" . var_export($output, true));
        }
    }

    protected function _exportXls($config, array $options = array())
    {
        require_once('include/php-excel.class.php');

        $defaultOptions = array(
            'name' => $config['table']
        );
        $options = array_merge($defaultOptions, $options);
        
        $fileName = $this->_getFileName($options['name']);
        $filePath = $this->exportPath.$fileName;

        $excel = new Excel_XML();

        $select = '';
        if (isset($options['fields'])) {
            $fieldsNames = array_map(function ($element) {
                return $element['field_name'];
            }, $options['fields']);
            $select = implode(', ', $fieldsNames);
        } else {
            $select = '*';
        }

        $sql = "SELECT {$select} FROM {$config['schema']}.{$config['table']}";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        
        $headers = null;
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            if (!isset($headers)) {
                $headers = $row;

                if (isset($options['fields'])) {
                    foreach ($options['fields'] as $field) {
                        $headers[$field['field_name']] = $field['title'];
                    }
                } else {
                    foreach ($headers as $key => $value) {
                        $headers[$key] = $key;
                    }
                }

                $excel->addRow($headers);
            }
            $excel->addRow($row);
        }

        $content = $excel->generateXML();

        file_put_contents($filePath, $content);

        return $filePath;
    }
    
    protected function _deleteOldFiles()
    {
        $files = glob($this->exportPath.'*');
        foreach ($files as $file) {
            $isold = (time() - filectime($file)) > 5 * 60 * 60;
            if (is_file($file) && $isold) {
                @unlink($file);
            }
        }
    }
    
    protected function _getFileName($customPart)
    {
        return $customPart . '_' . date('YmdHis') . '_' . rand(0, 9999);
    }
}


class GCExportGml
{
    protected $gmlLayers = array();
    protected $extent;
    protected $srid;
    protected $db;
    //private $log = ROOT_PATH.'config/debug/export.txt';
    
    public function __construct($db, $extent, $srid)
    {
        $this->extent = $extent;
        $this->srid = $srid;
        $this->db = $db;
    }
    
    public function export($file)
    {
        $content = $this->_getHeader() . implode(' ', $this->gmlLayers) . $this->_getFooter();
        file_put_contents($file, $content);
    }
    
    public function addLayer($layer, $gid = 'gid', $geom = 'the_geom')
    {
        $gml = '<layer name="' . $layer['name'] . '">';
        $sql = "SELECT {$gid} as gml_object_id, st_asgml(3, st_force2d({$geom})) as gml_geom "
            . " FROM {$layer['schema']}.{$layer['table']}";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $gml .= "<r3sg:feature gml:id=\"{$layer['name']}:{$row['gml_object_id']}\">{$row['gml_geom']}'</r3sg:feature>\n";
        }
        $gml .= "</layer>\n";
        array_push($this->gmlLayers, $gml);
    }
    
    protected function _getHeader()
    {
        return '<?xml version="1.0" encoding="UTF-8" ?>
            <gml:FeatureCollection xmlns:gml="http://www.opengis.net/gml"
            xmlns:xlink="http://www.w3.org/1999/xlink"
            xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
            xsi:schemaLocation="simple-geometry.xsd"
            xmlns:r3sg="http://www.r3-gis.com/schemas"
            gml:id="exportr3">
            <gml:name>GML export Test</gml:name>
            <gml:boundedBy srsName="EPSG:' . $this->srid . '">
            <gml:Envelope srsName="EPSG:' . $this->srid . '">
            <gml:lowerCorner>' . $this->extent[0] . ' ' . $this->extent[1] . '</gml:lowerCorner>
            <gml:upperCorner>' . $this->extent[2] . ' ' . $this->extent[3] . '</gml:upperCorner>
            </gml:Envelope>
            </gml:boundedBy>
            <r3sg:geometry>
            <gml:featureMembers>';
    }
    
    protected function _getFooter()
    {
        return '</gml:featureMembers></r3sg:geometry></gml:FeatureCollection>';
    }
}

class GCExportKml
{
    protected $db;
    protected $extent;
    protected $srid;
    protected $layers = array();
    protected $styles = array();

    public function __construct($db, $extent, $srid)
    {
        $this->db = $db;
        $this->extent = $extent;
        $this->srid = $srid;
    }

    protected function _checkStyleExpression($exp, $values)
    {
        $res = null;
        foreach ($values as $key => $value) {
            $exp = str_replace("[$key]", $value, $exp);
        }
        $exp = str_ireplace("or", '||', $exp);
        $exp = str_ireplace("eq", '==', $exp);
        
        eval('$res = ' . $exp . ';');
        return $res;
    }

    protected function _getCamera()
    {
        $centerX = ($this->extent[0] + $this->extent[2]) / 2;
        $centerY = ($this->extent[1] + $this->extent[3]) / 2;

        $sql = "SELECT ST_X(point) AS x, ST_Y(point) AS y"
            . " FROM ("
            . " SELECT ST_Transform(ST_SetSRID(ST_MakePoint({$centerX}, {$centerY}), {$this->srid}), 4326) AS point"
            . " ) AS foo";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $camera = $stmt->fetch(PDO::FETCH_ASSOC);

        return "<Camera>"
            . "<longitude>{$camera['x']}</longitude>"
            . "<latitude>{$camera['y']}</latitude>"
            . "<altitude>10000</altitude><altitudeMode>relativeToGround</altitudeMode>"
            . "<tilt>0</tilt>"
            . "</Camera>";
    }

    protected function _getData()
    {
        $kmlData = '';
        foreach ($this->layers as $layerConf) {
            $layer = $layerConf['layer'];

            $headers = array();
            if (isset($layerConf['options']['fields'])) {
                foreach ($layerConf['options']['fields'] as $field) {
                    $headers[$field['field_name']] = $field['title'];
                }
            } else {
                $fields = $layer->getFields();
                foreach ($fields as $field) {
                    $headers[$field->getName()] = $field->getTitle();
                }
            }

            $sql = "SELECT *,"
                . " st_askml(st_affine(st_force_3d(st_geometryn({$layer->getGeomColumn()}, 1)), 1, 0, 0, 0, 1, 0, 0, 0, 1, 0, 0, 0)) AS kml_geom"
                . " FROM {$layerConf['schema']}.{$layerConf['table']}";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $kmlData .= "<Placemark>";
                
                if ($layer->getLabelItem()) {
                    $label = $row[$layer->getLabelItem()];
                    $kmlData .= "<name>{$label}</name>";
                }
                
                $geomExtraAttribute = '<altitudeMode>clampToGround</altitudeMode>';
                $kmlData .= preg_replace('/>/', '>' . $geomExtraAttribute, $row['kml_geom'], 1);

                foreach ($this->styles as $styleName => $exp) {
                    if ($this->_checkStyleExpression($exp, $row)) {
                        $kmlData .= "<styleUrl>#{$styleName}</styleUrl>";
                        break; //kml supports only one style
                    }
                }

                $kmlData .= '<ExtendedData>';
                foreach ($row as $key => $value) {
                    if ($key == $layer->getGeomColumn() || $key == 'kml_geom') {
                        continue;
                    }
                    if (is_numeric($value)) {
                        $value = round($value, 2);
                    }
                    
                    $kmlData .= "<Data name=\"{$key}\">";
                    if (isset($headers[$key])) {
                        $kmlData .= "<displayName>{$headers[$key]}</displayName>";
                    }
                    $kmlData .= "<value>{$value}</value></Data>";
                }
                $kmlData .= '</ExtendedData>';
                $kmlData .= '</Placemark>';
            }
        }
        return $kmlData;
    }

    protected function _getHeader()
    {
        return '<?xml version="1.0" encoding="UTF-8"?><kml xmlns="http://www.opengis.net/kml/2.2"><Document>';
    }

    protected function _getKMLColor($string)
    {
        $colorArr = array_reverse(
            array_map(
                'dechex',
                explode(' ', $string)
            )
        );

        foreach ($colorArr as $key => $hex) {
            $colorArr[$key] = sprintf("%02s", $hex);
        }

        return implode($colorArr);
    }

    protected function _getStyles()
    {
        $kmlStyle = '';
        foreach ($this->layers as $layerConf) {
            $layer = $layerConf['layer'];
            $opacity = 255;
            if ($layer->getOpacity()) {
                $opacity = ($opacity * $layer->getOpacity()) / 100;
            } else if ($layer->getLayerGroup()->getOpacity()) {
                $opacity = ($opacity * $layer->getLayerGroup()->getOpacity()) / 100;
            }
            foreach ($layer->getStyleClasses() as $class) {
                $styleName = $layer->getName() . '.' . $class->getName();
                foreach ($class->getStyles() as $style) {
                    $color = $style->getColor()? $this->_getKMLColor($style->getColor() . ' ' . $opacity) : 'ff000000';
                    $backgroundColor = $style->getBackgroundColor()? $this->_getKMLColor($style->getBackgroundColor() . ' ' . $opacity) : '';
                    $outlineColor = $style->getOutlineColor()? $this->_getKMLColor($style->getOutlineColor() . ' ' . $opacity) : '';
                    $size = $style->getSize()? $style->getSize() : 1;

                    $bgColor = $backgroundColor? $backgroundColor : $color;
                    $olColor = $outlineColor? $outlineColor : $color;

                    $kmlStyle .= "<Style id=\"{$styleName}\">"
                        . "<LineStyle><color>{$olColor}</color><width>{$size}</width></LineStyle>"
                        . "<PolyStyle><color>{$bgColor}</color><fill>1</fill><outline>1</outline></PolyStyle>"
                        . "</Style>";

                    $this->styles[$styleName] = $class->getExpression();
                    break; //Kml support only one style so take only first
                }
            }
        }

        return $kmlStyle;
    }
    
    protected function _getFooter()
    {
        return '</Document></kml>';
    }

    public function addLayer($layer, $schema, $table, $options)
    {
        array_push($this->layers, array(
            'layer' => $layer,
            'schema' => $schema,
            'table' => $table,
            'options' => $options
        ));
    }

    public function export($file)
    {
        $content = $this->_getHeader() . $this->_getCamera() . $this->_getStyles() . $this->_getData() . $this->_getFooter();
        file_put_contents($file, $content);
    }
}
