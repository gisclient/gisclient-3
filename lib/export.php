<?php

class GCExport {
    protected $type;
    protected $db;
    protected $exportPath;
    protected $exportUrl;
    protected $exportExtensions = array(
        'shp'=>array('shp','dbf','shx','prj','cpg')
    );
    
    function __construct($db, $type, array $options = array()) {
        $this->type = $type;
        $this->db = $db;
        $this->exportPath = ROOT_PATH.'public/services/tmp/export/';
        $this->exportUrl = PUBLIC_URL.'services/tmp/export/';
        $this->errorPath = DEBUG_DIR;
    }
    
    public function getExportUrl() {
        return $this->exportUrl;
    }
    
    public function export($tables, array $options = array()) {
        $defaultOptions = array(
            'name'=>'export',
            'extent'=>null,
            'srid'=>null,
            'add_to_zip'=>null,
            'return_url'=>true
        );
        $options = array_merge($defaultOptions, $options);
        
        $files = array();
        
        if($this->type == 'shp') {
            foreach($tables as $tableSpec) {
                $exportOptions = array();
                if(!empty($tableSpec['name'])) $exportOptions['name'] = $tableSpec['name'];
                $layer = $this->_exportShp($tableSpec['db'], $tableSpec['table'], $tableSpec['schema'], $exportOptions);
                foreach($layer as $niceName => $realName) {
                    $files[$niceName] = $realName;
                }
            }
        } else if($this->type == 'dxf') {
            $exportGml = new GCExportGml($this->db, $options['extent'], $options['srid']);
            $gmlFile = $this->_getFileName($options['name']).'.gml';
            foreach($tables as $tableSpec) {
                if(empty($tableSpec['name'])) $tableSpec['name'] = $tableSpec['table'];
                $exportGml->addLayer($tableSpec);
            }
            $exportGml->export($this->exportPath.$gmlFile);
            $dxfFile = $this->_getFileName($options['name']).'.dxf';
            $this->_exportDxf($gmlFile, $dxfFile);
            $files[$options['name'].'.dxf'] = $this->exportPath.$dxfFile;
        }
        		
		$zip = new ZipArchive;
		
        if($options['add_to_zip']) {
            $zipName = $options['add_to_zip'];
            $openZipFlag = ZIPARCHIVE::CHECKCONS;
        } else {
            $zipName = $this->_getFileName($options['name']).'.zip';
            $openZipFlag = ZIPARCHIVE::CREATE;
        }
        $zipPath = $this->exportPath.$zipName;
        
		if(!$zip->open($zipPath, $openZipFlag)) throw new Exception('Error creating zip file');
        foreach($files as $niceName => $realName) {
			if(!$zip->addFile($realName, $niceName)) throw new Exception('Error adding file '.$realName.' to zip file');
		}
		if(!$zip->close()) throw new Exception('Error closing zip file');
        
        $return = $options['return_url'] ? $this->exportUrl.$zipName : $zipName;
        return $return;
    }
    
    protected function _exportShp($dbName, $table, $schema = null, array $options = array()) {
        $defaultOptions = array(
            'name'=>$table
        );
        $options = array_merge($defaultOptions, $options);
        
        $fileName = $this->_getFileName($options['name']);
        $filePath = $this->exportPath.$fileName;
        $errorFile = $this->errorPath.$fileName.'.err';
		
		$cmd = 'pgsql2shp -f '.escapeshellarg($filePath.'.shp').' -h '.DB_HOST.' -p '.DB_PORT.' -u '.DB_USER.' -P '.DB_PWD.
			' '.escapeshellarg($dbName).' '.escapeshellarg($schema.'.'.$table).
			' 2> '.escapeshellarg($errorFile);

		$pgsql2shpOutput = array();
		$retVal = -1;
		
		exec($cmd, $pgsql2shpOutput, $retVal);
		if($retVal != 0) {
			file_put_contents($errorFile, $cmd, FILE_APPEND);
            throw new Exception('Postgres to SHP error: ');
		}
		// charset related operations
        if(($dbfFile = fopen($filePath . '.dbf', "r+")) === FALSE) throw new Exception('Unable to edit dbf encoding');
        if(fseek($dbfFile, 29) === -1) throw new Exception('Malformed dbf');
        if(($ldid = fread($dbfFile, 1)) === FALSE) throw new Exception('Malformed dbf');
        if ($ldid != chr(0)) {
            if(fseek($dbfFile, 29) === -1) throw new Exception("Malformed dbf");
            if(fwrite($dbfFile, chr(0)) === FALSE) throw new Exception("Malformed dbf");
        }
        fclose($dbfFile);
        file_put_contents($filePath . '.cpg', 'UTF-8');
        
        $files = array();
        foreach($this->exportExtensions['shp'] as $ext) {
            if(file_exists($filePath.'.'.$ext)) $files[$options['name'].'.'.$ext] = $filePath.'.'.$ext;
        }
        return $files;
    }
    
    protected function _exportDxf($gmlFile, $dxfFile) {
        chdir('/usr/local/kabeja/');
        //$cmd = "java -Xmx512m -jar launcher.jar -main org.kabeja.gml.Main -template /data/sites/gc/author-giussano/config/prova.dxf ".
        $cmd = 'java -Xmx512m -jar launcher.jar -main org.kabeja.gml.Main ';
        if(defined('GC_DBT_CAD_TPL')) $cmd .= ' -template '.escapeshellarg(GC_DBT_CAD_TPL).' ';
        $cmd .= escapeshellarg($this->exportPath.$gmlFile)." ".escapeshellarg($this->exportPath.$dxfFile);
        exec($cmd, $output, $retval);
        if ($retval != 0){
        	throw new Exception("Could not convert GML to DXF: [return value: $retval]\n command was: [$cmd]\n".var_export($output, true));
        }
    }
    
    protected function _deleteOldFiles() {
        $files = glob($this->exportPath.'*');
		foreach($files as $file) {
			$isold = (time() - filectime($file)) > 5 * 60 * 60;
			if (is_file($file) && $isold) {
				@unlink($file);
			}
		}
    }
    
    protected function _getFileName($customPart) {
        return $customPart.'_'.date('YmdHis').'_'.rand(0,9999);
    }
}


class GCExportGml {
    protected $gmlLayers = array();
    protected $extent;
    protected $srid;
    protected $db;
    //private $log = ROOT_PATH.'config/debug/export.txt';
    
    function __construct($db, $extent, $srid) {
        $this->extent = $extent;
        $this->srid = $srid;
        $this->db = $db;
    }
    
    public function export($file) {
        $content = $this->_getHeader().implode(' ', $this->gmlLayers).$this->_getFooter();
        file_put_contents($file, $content);
    }
    
    public function addLayer($layer) {
		$gml = '<layer name="'.$layer['name'].'">';
		$sql = 'select gid as gml_object_id, st_asgml(3, st_force_2d(the_geom)) as gml_geom from '.
			$layer['schema'].'.'.$layer['table'];
		$stmt = $this->db->prepare($sql);
		$stmt->execute();
		while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
			$gml .= '<r3sg:feature gml:id="'.$layer['name'].':'.$row['gml_object_id'].'">'.$row['gml_geom'].'</r3sg:feature>
			';
		}
		$gml .= '</layer>
		';
        array_push($this->gmlLayers, $gml);
    }
    
    protected function _getHeader() {

        return '<?xml version="1.0" encoding="UTF-8" ?>
            <gml:FeatureCollection xmlns:gml="http://www.opengis.net/gml"
            xmlns:xlink="http://www.w3.org/1999/xlink"
            xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
            xsi:schemaLocation="simple-geometry.xsd"
            xmlns:r3sg="http://www.r3-gis.com/schemas"
            gml:id="exportr3">
            <gml:name>GML export Test</gml:name>
            <gml:boundedBy srsName="EPSG:'.$this->srid.'">
            <gml:Envelope srsName="EPSG:'.$this->srid.'">
            <gml:lowerCorner>'.$this->extent[0].' '.$this->extent[1].'</gml:lowerCorner>
            <gml:upperCorner>'.$this->extent[2].' '.$this->extent[3].'</gml:upperCorner>
            </gml:Envelope>
            </gml:boundedBy>
            <r3sg:geometry>
            <gml:featureMembers>';
    }
    
    protected function _getFooter() {
        return '</gml:featureMembers></r3sg:geometry></gml:FeatureCollection>';
    }
}
