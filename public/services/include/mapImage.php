<?php
include_once('printDocument.php');

class mapImage {

	protected $wmsMergeUrl = 'services/gcWMSMerge.php';
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
	protected $scale;
	
	public static $vectorTypes = array(
        'MultiPolygon'=>array('db_type'=>'MULTIPOLYGON', 'db_field'=>'multipolygon_geom', 'ms_type'=>MS_LAYER_POLYGON),
        'Polygon'=>array('db_type'=>'POLYGON', 'db_field'=>'polygon_geom', 'ms_type'=>MS_LAYER_POLYGON),
        'Point'=>array('db_type'=>'POINT', 'db_field'=>'point_geom', 'ms_type'=>MS_LAYER_POINT),
        'MultiPoint'=>array('db_type'=>'MULTIPOINT', 'db_field'=>'multipoint_geom', 'ms_type'=>MS_LAYER_POINT),
        'LineString'=>array('db_type'=>'LINESTRING', 'db_field'=>'linestring_geom', 'ms_type'=>MS_LAYER_LINE),
        'MultiLineString'=>array('db_type'=>'MULTILINESTRING', 'db_field'=>'multilinestring_geom', 'ms_type'=>MS_LAYER_LINE),
    );
    
    
	function __construct($tiles, array $imageSize, $srid, array $options) {
		$defaultOptions = array(
			'scale_mode'=>'auto', //'auto' calculate extent from bbox, if 'user', calculate extent from center/scale
			'extent'=>array(),
			'center'=>array(),
			'vectors'=>null,
			'image_format'=>'png', // or gtiff
			'auth_name'=>'EPSG',
			'scalebar'=>true,
			'request_type'=>'get-map',
			'TMP_PATH' => GC_WEB_TMP_DIR,
			'TMP_URL' => GC_WEB_TMP_URL,
			'dpi' => 72
		);
		$this->options = array_merge($defaultOptions, $options);
		
		$this->tiles = $tiles;
		$this->imageSize = $imageSize;
		$this->db = GCApp::getDB();
		$this->srid = $srid;
		$this->wmsMergeUrl = printDocument::addPrefixToRelativeUrl(PUBLIC_URL.$this->wmsMergeUrl);
		
		if($this->options['scale_mode'] == 'user') {
			if(empty($this->options['center']) || empty($this->options['scale'])) {
				throw new Exception('Missing center or scale');
			}
			$this->extent = $this->calculateExtent($this->options['center'], $imageSize, $this->options['dpi'], $this->options['scale']);
			$this->scale = $this->options['scale'];
		} else {
			if(empty($this->options['extent'])) {
				throw new Exception('Missing extent');
			}
			$this->extent = $this->adaptExtentToSize($this->options['extent'], $this->imageSize);
			$paperSize = $this->paperSize($imageSize, $this->options['dpi']);
			$this->scale = $this->extent[0] / $paperSize[0];
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
		if(empty($this->imageFileName)) {
			$this->getMapImage();
		}
		return $this->options['TMP_URL'].$this->imageFileName;
	}
	
	public function getImageFileName() {
		if(empty($this->imageFileName)) {
			$this->getMapImage();
		}
		return $this->imageFileName;
	}
	
	public function getScale() {
		return $this->scale;
	}
	
	protected function buildWmsList() {
		foreach($this->tiles as $key => $tile) {
			$url = trim($tile['url'], '?');
			$url = printDocument::addPrefixToRelativeUrl($url);
			if (!empty($tile['service'])) {
				$service = $tile['service'];
			} else {
				$service = 'WMS';
			}
            
            $parameters = array();
			if (isset($tile['parameters'])) {
				foreach($tile['parameters'] as $key => $val) {
					$parameters[strtoupper($key)] = $val;
				}
			}
            
            // nell'url può esserci un PROJECT e MAP diverso da quello dei parametri, vince quello dell'url
            // ???????????????????????????? MAH ???????????????????
            $parsedUrl = parse_url($url);
            if(!empty($parsedUrl['query'])) {
                $urlParams = array();
                parse_str($parsedUrl['query'], $urlParams);
                foreach($urlParams as $key => $val) {
                    unset($urlParams[$key]);
                    $urlParams[strtoupper($key)] = $val;
                }
                //if(!empty($urlParams['PROJECT']) && !empty($parameters['PROJECT'])) unset($parameters['PROJECT']);
                //if(!empty($urlParams['MAP']) && !empty($parameters['MAP'])) unset($parameters['MAP']);
            }
            
            if(!empty($tile['opacity'])) $parameters['OPACITY'] = $tile['opacity'];
            
			$request = array('URL'=>$url, 'SERVICE'=>$service, 'PARAMETERS'=>$parameters);
			if ($service === 'WMTS') {
				if (isset($tile['layer'])) {
					$request['LAYER'] = $tile['layer'];
				} else {
					throw new Exception("layer name is required to print WMTS layer");
				}
				if (isset($tile['project'])) {
					$request['PROJECT'] = $tile['project'];
				} else {
					throw new Exception("project name is required to print WMTS layer");
				}
			}
			
			array_push($this->wmsList, $request);
		}
        
        if(!empty($this->vectorId)) {
            $url = PUBLIC_URL.'services/vectors.php';
            $url = printDocument::addPrefixToRelativeUrl($url);
            $parameters = array(
                'LAYERS'=>$this->vectorId,
                'VERSION'=>'1.1.1',
                'FORMAT'=>'image/png'
            );
            array_push($this->wmsList, array('URL'=>$url, 'SERVICE'=>'WMS', 'PARAMETERS'=>$parameters));
        }
	}
	
	protected function getMapImage() {
		$extension = 'png';
		if($this->options['image_format'] == 'gtiff') $extension = 'tif';
		if($this->options['image_format'] == 'jpeg') $extension = 'jpg';

		$this->imageFileName = GCApp::getUniqueRandomTmpFilename($this->options['TMP_PATH'], 'gc_mapimage', $extension);

		if(isset($this->options['save_image'])) {
			$saveImage = $this->options['save_image'];
		} else if ($this->options['image_format'] == 'gtiff'){
			$saveImage = true;
		} else {
			$saveImage = true;
		}

		$requestParameters = json_encode(array(
			'layers'=>$this->wmsList,
			'size'=>$this->imageSize,
			'extent'=>$this->extent,
			'srs'=>$this->options['auth_name'].':'.$this->srid,
			'scalebar' => $this->options['scalebar'],
			'save_image'=> $saveImage,
			'resolution'=>$this->options['dpi'],
			'file_name'=>$this->options['TMP_PATH'].$this->imageFileName,
			'format'=>$this->options['image_format'],
			'GC_SESSION_ID' => session_id()
		));
		session_write_close();


		if (false === ($ch = curl_init())) {
			throw new Exception("Could not init curl");
		}
		curl_setopt($ch, CURLOPT_URL, $this->wmsMergeUrl);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_BINARYTRANSFER, 1);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, array('options'=>$requestParameters));

		if (false === ($mapImage = curl_exec($ch))) {
			throw new Exception("Could not curl_exec" . curl_error($ch));
		}

        if (200 != ($httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE))) {
            throw new RuntimeException("Call to {$this->wmsMergeUrl} return HTTP code $httpCode and body ".$mapImage);
        }
		if(!$saveImage) {
			$filename = $this->options['TMP_PATH'].$this->imageFileName;
			if (false === file_put_contents($filename, $mapImage)) {
				throw new Exception("Could not save map image to $filename");
			}
		}
		curl_close($ch);
	}
	
	protected function adaptExtentToSize(array $extent, array $imageSize) {
		$extentCenter = array(
			0.5 * ($extent[0] + $extent[2]),
			0.5 * ($extent[1] + $extent[3]),
		);
		
		$extentSize = array(
			$extent[2] - $extent[0],
			$extent[3] - $extent[1],
		);
		
		$widthRatio = $extentSize[0] / $imageSize[0];
		$heightRatio = $extentSize[1] / $imageSize[1];
		
		if($widthRatio >= $heightRatio) {
			$extentSize[1] *= ($widthRatio/$heightRatio);
		} else {
			$extentSize[0] *= ($heightRatio/$widthRatio);
		}
		
		$adaptedExtend = array(
			$extentCenter[0] - 0.5 * $extentSize[0],
			$extentCenter[1] - 0.5 * $extentSize[1],
			$extentCenter[0] + 0.5 * $extentSize[0],
			$extentCenter[1] + 0.5 * $extentSize[1],
		);
		
		return $adaptedExtend;
	}
	
	protected function paperSize(array $imageSize, $dpi) {
		return array(
			$imageSize[0] / ($dpi * 100/2.54),
			$imageSize[1] / ($dpi * 100/2.54),
		);
	}
	
	protected function calculateExtent(array $center, array $imageSize, $dpi, $scale) {
		$paperSize = $this->paperSize($imageSize, $dpi);
		
		$extentWidth = $scale *  $paperSize[0];
		$extentHeight = $scale * $paperSize[1];

		$extent = array(
			$center[0] - 0.5 * $extentWidth,
			$center[1] - 0.5 * $extentHeight,
			$center[0] + 0.5 * $extentWidth,
			$center[1] + 0.5 * $extentHeight,
		);
		return $extent;
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
