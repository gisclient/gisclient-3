<?php
include_once('printDocument.php');
class mapImage {

	protected $WMSMergeUrl = 'services/gcWMSMerge.php';
	protected $tiles = array();
	protected $extent = array();
	protected $wmsList = array();
	protected $imageSize = array();
	protected $mapSize = array();
	protected $db = null;
	protected $vectorId = null;
	protected $options = array();
	protected $imageFileName = null;
	protected $srid = null;
    //public static $vectorTypes = array('POINT', 'MULTIPOINT', 'LINESTRING', 'MULTILINESTRING', 'POLYGON', 'MULTIPOLYGON');
	public static $vectorTypes = array(
        'MultiPolygon'=>array('db_type'=>'MULTIPOLYGON', 'db_field'=>'multipolygon_geom', 'ms_type'=>MS_LAYER_POLYGON),
        'Polygon'=>array('db_type'=>'POLYGON', 'db_field'=>'polygon_geom', 'ms_type'=>MS_LAYER_POLYGON),
        'Point'=>array('db_type'=>'POINT', 'db_field'=>'point_geom', 'ms_type'=>MS_LAYER_POINT),
        'MultiPoint'=>array('db_type'=>'MULTIPOINT', 'db_field'=>'multipoint_geom', 'ms_type'=>MS_LAYER_POINT),
        'LineString'=>array('db_type'=>'LINESTRING', 'db_field'=>'linestring_geom', 'ms_type'=>MS_LAYER_LINE),
        'MultiLineString'=>array('db_type'=>'MULTILINESTRING', 'db_field'=>'multilinestring_geom', 'ms_type'=>MS_LAYER_LINE),
    );
    
    
	function __construct($tiles, $imageSize, $srid, $options) {
		$defaultOptions = array(
			'scale_mode'=>'auto', //'auto' calculate extent from bbox, if 'user', calculate extent from center/scale (requires pixels_distance)
			'extent'=>array(),
			'center'=>array(),
            'vectors'=>null,
			'image_format'=>'png', // or gtiff
			'pixels_distance'=>null, //define the scale (how many meters are represented in the current viewport)
			'auth_name'=>'EPSG',
			'scalebar'=>true,
			'request_type'=>'get-map',
			'fixed_size'=>$imageSize,
            'TMP_PATH' => GC_WEB_TMP_DIR,
			'TMP_URL' => GC_WEB_TMP_URL,
			'dpi' => 72
		);
		$this->options = array_merge($defaultOptions, $options);
		
		$this->tiles = $tiles;
		$this->imageSize = $imageSize;
		$this->db = GCApp::getDB();
		$this->srid = $srid;
		$this->WMSMergeUrl = printDocument::addPrefixToRelativeUrl(PUBLIC_URL.$this->WMSMergeUrl);
		
		if($this->options['scale_mode'] == 'user') {
			if(empty($this->options['center']) || empty($this->options['pixels_distance'])) {
				throw new Exception('Missing center or pixels_distance');
			}
			$this->extent = $this->calculateExtent($this->options['center'], $this->options['pixels_distance']);
		} else {
			if(empty($this->options['extent'])) throw new Exception('Missing extent');
			$this->extent = $this->adaptExtentToSize($this->options['extent']);
		}
        
        if(!empty($this->options['vectors'])) {
            $this->vectorId = $this->importVectors();
        }
        
		if($this->options['request_type'] == 'get-map') {
			$this->buildWmsList();
		}
	}
	
	public function getWmsList() {
		return $this->wmsList;
	}
	
	public function getExtent() {
		return $this->extent;
	}
	
	public function getImageUrl() {
		if(empty($this->imageFileName)) $this->getMapImage();
		return $this->options['TMP_URL'].$this->imageFileName;
	}
	
	public function getImageFileName() {
		if(empty($this->imageFileName)) $this->getMapImage();
		return $this->imageFileName;
	}
	
	public function getScale($imageWidth) { //imageWidth in meters, get the ratio between the represented meters and the image meters in the horizontal dimension
		return round($this->mapSize[0] / $imageWidth);
	}
	
	protected function buildWmsList() {
		foreach($this->tiles as $key => $tile) {
            $url = trim($tile['url'], '?');
            $url = printDocument::addPrefixToRelativeUrl($url);
            
            $parameters = array();
            foreach($tile['parameters'] as $key => $val) {
                $parameters[strtoupper($key)] = $val;
            }
            
            // nell'url può esserci un PROJECT e MAP diverso da quello dei parametri, vince quello dell'url
            $parsedUrl = parse_url($url);
            if(!empty($parsedUrl['query'])) {
                $urlParams = array();
                parse_str($parsedUrl['query'], $urlParams);
                foreach($urlParams as $key => $val) {
                    unset($urlParams[$key]);
                    $urlParams[strtoupper($key)] = $val;
                }
                if(!empty($urlParams['PROJECT']) && !empty($parameters['PROJECT'])) unset($parameters['PROJECT']);
                if(!empty($urlParams['MAP']) && !empty($parameters['MAP'])) unset($parameters['MAP']);
            }
            
            if(!empty($tile['opacity'])) $parameters['OPACITY'] = $tile['opacity'];
            
            array_push($this->wmsList, array('URL'=>$url, 'PARAMETERS'=>$parameters));
		}
        
        if(!empty($this->vectorId)) {
            $url = PUBLIC_URL.'services/vectors.php';
            $url = printDocument::addPrefixToRelativeUrl($url);
            $parameters = array(
                'LAYERS'=>$this->vectorId,
                'VERSION'=>'1.1.1',
                'FORMAT'=>'image/png'
            );
            array_push($this->wmsList, array('URL'=>$url, 'PARAMETERS'=>$parameters));
        }
	}
	
	protected function getMapImage() {
		try {
			$extension = 'png';
			if($this->options['image_format'] == 'gtiff') $extension = 'tif';
			
			$this->imageFileName = GCApp::getUniqueRandomTmpFilename($this->options['TMP_PATH'], 'gc_mapimage', $extension);
            
            $saveImage = true;
            if(empty($this->options['save_image']) && $this->options['image_format'] == 'gtiff') $saveImage = false;
			
			$requestParameters = json_encode(array(
				'layers'=>$this->wmsList,
				'size'=>$this->imageSize,
				'extent'=>$this->extent,
				'srs'=>$this->options['auth_name'].':'.$this->srid,
				'scalebar' => $this->options['scalebar'],
				'save_image'=>($this->options['image_format'] != 'gtiff'),
				'resolution'=>$this->options['dpi'],
				'file_name'=>$this->options['TMP_PATH'].$this->imageFileName,
				'format'=>$this->options['image_format'],
				GC_SESSION_NAME => session_id()
			));
			session_write_close();
			
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $this->WMSMergeUrl);
			curl_setopt($ch, CURLOPT_HEADER, 0);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_BINARYTRANSFER, 1);
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_POSTFIELDS, array('options'=>$requestParameters));
			$mapImage = curl_exec($ch);
			if(!$requestParameters['save_image']) {
				file_put_contents($this->options['TMP_PATH'].$this->imageFileName, $mapImage);
			}
			curl_close($ch);	
		} catch(Exception $e) {
			throw $e;
		}
	}
	
	protected function adaptExtentToSize($extent) {
		$leftBottom = "st_geomfromtext('POINT(".$extent[0]." ".$extent[1].")', ".$this->srid.")";
		$rightBottom = "st_geomfromtext('POINT(".$extent[2]." ".$extent[1].")', ".$this->srid.")";
		$rightTop = "st_geomfromtext('POINT(".$extent[2]." ".$extent[3].")', ".$this->srid.")";
		$leftTop = "st_geomfromtext('POINT(".$extent[0]." ".$extent[3].")', ".$this->srid.")";
		$sql = "select st_length(st_makeline($leftBottom, $rightBottom)) as w, st_length(st_makeline($leftBottom, $leftTop)) as h";
		$measures = $this->db->query($sql)->fetch(PDO::FETCH_ASSOC);
		
		if($measures['w'] > $measures['h']) {
			$longEdge = 'w';
			$height = ($measures['w']/$this->imageSize[0])*$this->imageSize[1];
			$buffer = ($height - $measures['h'])/2;
			$width = $measures['w'];
		} else {
			$longEdge = 'h';
			$width = ($measures['h']/$this->imageSize[1])*$this->imageSize[0];
			$buffer = ($width - $measures['w'])/2;
		}
		$this->mapSize = array($width, $height);

		$extentPolygon = "st_polygon(st_makeline(ARRAY[$leftBottom, $rightBottom, $rightTop, $leftTop, $leftBottom]), ".$this->srid.")";
		
		$sql = "select box2d(st_buffer((".$extentPolygon."), $buffer))";
		$box = $this->db->query($sql)->fetchColumn(0);
		$box = GCUtils::parseBox($box);
		
		if($longEdge == 'w') {
			return array($extent[0], $box[1], $extent[2], $box[3]);
		} else {
			return array($box[0], $extent[1], $box[2], $extent[3]);
		}
	}
	
	protected function calculateExtent($center, $pixelDistance) {
		if($this->options['fixed_size'][0] > $this->options['fixed_size'][1]) {
			$longEdge = 'w';
			$shortEdgeDividend = $this->options['fixed_size'][1];
			$longEdgeDividend = $this->options['fixed_size'][0];
		} else {
			$longEdge = 'h';
			$shortEdgeDividend = $this->options['fixed_size'][0];
			$longEdgeDividend = $this->options['fixed_size'][1];
		}

		$shortBuffer = ($shortEdgeDividend / $pixelDistance)/2;
		$longBuffer = ($longEdgeDividend / $pixelDistance)/2;
		$center = "st_geomfromtext('POINT(".$center[0]." ".$center[1].")', ".$this->srid.")";
		$sql = "select box2d(st_buffer($center, $shortBuffer)) as short, box2d(st_buffer($center, $longBuffer)) as long";
		$boxes = $this->db->query($sql)->fetch(PDO::FETCH_ASSOC);
		$boxes = $this->parseBoxes($boxes);
		
		if($longEdge == 'w') {
			return array($boxes['long'][0], $boxes['short'][1], $boxes['long'][2], $boxes['short'][3]);
		} else {
			return array($boxes['short'][0], $boxes['long'][1], $boxes['short'][2], $boxes['long'][3]);
		}
	}
	
	protected function parseBoxes($boxes) {
		$parsedBoxes = array();
		foreach(array('long', 'short') as $type) {
			$parsedBoxes[$type] = GCUtils::parseBox($boxes[$type]);
		}
		return $parsedBoxes;
	}
    

    protected function importVectors() {
        if(!defined('PRINT_VECTORS_TABLE')) throw new Exception('Undefined PRINT_VECTORS_TABLE');
        if(!defined('PRINT_VECTORS_SRID')) throw new Exception('Undefined PRINT_VECTORS_SRID');
        
        $tableName = PRINT_VECTORS_TABLE;
        $schema = defined('PRINT_VECTORS_SCHEMA') ? PRINT_VECTORS_SCHEMA : 'public';
        
        $db = GCApp::getDB();
        
        if(!GCApp::tableExists($db, $schema, $tableName)) {
            $sql = 'create sequence '.$schema.'.'.$tableName.'_print_id_seq ';
            $db->exec($sql);
            $sql = 'create table '.$schema.'.'.$tableName.' (gid serial, print_id integer, insert_time timestamp without time zone not null default now()) WITH (OIDS=FALSE)';
            $db->exec($sql);
            $sql = 'select addgeometrycolumn(:schema, :table, :column, :srid, :type, 2)';
            $addGeometryColumn = $db->prepare($sql);
            foreach(self::$vectorTypes as $key => $type) {
                $addGeometryColumn->execute(array(
                    'schema'=>$schema,
                    'table'=>$tableName,
                    'srid'=>PRINT_VECTORS_SRID,
                    'column'=>$type['db_field'],
                    'type'=>$type['db_type']
                ));
            }
            $sql = 'GRANT SELECT ON TABLE '.$schema.'.'.$tableName.' TO '.MAP_USER;
            $db->exec($sql);
        }
        
        $sql = "select nextval('".$schema.".".$tableName."_print_id_seq')";
        $printId = $db->query($sql)->fetchColumn(0);
        
        $vectors = array();
        foreach($this->options['vectors'] as $vector) {
            $type = $vector['type'];
            if(!isset(self::$vectorTypes[$type])) continue;
            if(!isset($vectors[$type])) $vectors[$type] = array();
            array_push($vectors[$type], $vector);
        }
        
        foreach($vectors as $type => $features) {
            $field = self::$vectorTypes[$type]['db_field'];
            $sql = 'insert into '.$schema.'.'.$tableName.' (print_id, '.$field.') values (:print_id, st_geomfromtext(:geom, :srid))';
            $stmt = $db->prepare($sql);
            foreach($features as $feature) {
                $stmt->execute(array(
                    'print_id'=>$printId,
                    'geom'=>$feature['geometry'],
                    'srid'=>PRINT_VECTORS_SRID
                ));
            }
        }
        
        $this->cleanVectors();
        
        return $printId;
    }
    
    protected function cleanVectors() {
        if(!defined('PRINT_VECTORS_TABLE')) throw new Exception('Undefined PRINT_VECTORS_TABLE');
        if(!defined('PRINT_VECTORS_SRID')) throw new Exception('Undefined PRINT_VECTORS_SRID');
        
        $tableName = PRINT_VECTORS_TABLE;
        $schema = defined('PRINT_VECTORS_SCHEMA') ? PRINT_VECTORS_SCHEMA : 'public';
        
        $db = GCApp::getDB();
        
        $sql = 'delete from '.$schema.'.'.$tableName." where (insert_time + interval '1 day') < NOW()";
        $db->exec($sql);
    }
}
