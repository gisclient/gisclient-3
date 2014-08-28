<?php

class printDocument {

    protected $options;

    protected $tiles = array();
	protected $extent = array();
	protected $dimensions = array(
		'vertical'=>array(
			'A4'=>array('w'=>17,'h'=>22.5),
			'A3'=>array('w'=>25.8,'h'=>35),
			'A2'=>array('w'=>38,'h'=>52),
			'A1'=>array('w'=>55,'h'=>76),
			'A0'=>array('w'=>80,'h'=>111)
		),
		'horizontal'=>array(
			'A4' => array('w'=>25.8,'h'=>14),
			'A3' => array('w'=>38,'h'=>22.5),
			'A2' => array('w'=>55,'h'=>34),
			'A1' => array('w'=>80,'h'=>52),
			'A0' => array('w'=>115,'h'=>77)
		)
	);
	protected $WMSMergeUrl = 'services/gcWMSMerge.php';
	protected $wmsList = array();
	protected $imageSize = array();
	protected $documentSize = array();
	protected $documentElements = array();
	protected $imageFileName = '';
	protected $legendArray = array();
	protected $vectors = array();
	protected $db = null;
	protected $getLegendGraphicWmsList = array();
	protected $nullLogo = 'null.png';

    public function __construct() {
	
        $defaultOptions = array(
            'format' => 'A4',
            'dpi' => 72,
            'direction' => 'vertical',
            'TMP_PATH' => GC_WEB_TMP_DIR,
			'TMP_URL' => GC_WEB_TMP_URL,
			'legend' => null,
			'scale_mode' => 'auto',
			'image_format'=>'png',
			'extent' => null,
			'viewport_size' => array(),
			'center' => array(),
			'srid' => null,
			'auth_name'=>'EPSG',
			'pixels_distance' => null
        );
		
		$options = array();

		if(!empty($_REQUEST['tiles']) && is_array($_REQUEST['tiles'])) {
			$this->tiles = $_REQUEST['tiles'];
		} else {
			if(empty($_REQUEST['request_type']) || $_REQUEST['request_type'] != 'get-box') {
				throw new Exception('No tiles');
			}
		}

		if (!empty($_REQUEST['extent']))
			$options['extent'] = explode(',', $_REQUEST['extent']);
		if (!empty($_REQUEST['scale_mode']))
			$options['scale_mode'] = $_REQUEST['scale_mode'];
		if (!empty($_REQUEST['legend']))
			$options['legend'] = $_REQUEST['legend'];
		if (!empty($_REQUEST['current_scale'])) // non serve
			$options['current_scale'] = $_REQUEST['current_scale'];
		if (!empty($_REQUEST['printFormat']))
			$options['format'] = $_REQUEST['printFormat'];
		if (!empty($_REQUEST['direction']) && in_array($_REQUEST['direction'], array('horizontal', 'vertical')))
			$options['direction'] = $_REQUEST['direction'];
		if (!empty($_REQUEST['dpi']) && is_numeric($_REQUEST['dpi']))
			$options['dpi'] = (int) $_REQUEST['dpi'];
		if (!empty($_REQUEST['srid'])) {
			$options['srid'] = $_REQUEST['srid'];
			if (strpos($_REQUEST['srid'], ':') !== false) {
				list($options['auth_name'], $options['srid']) = explode(':', $_REQUEST['srid']);
			}
		}
		if (!empty($_REQUEST['pixels_distance']))
			$options['pixels_distance'] = $_REQUEST['pixels_distance'];
		if (!empty($_REQUEST['viewport_size'])) // non serve
			$options['viewport_size'] = $_REQUEST['viewport_size'];
		if (!empty($_REQUEST['center']))
			$options['center'] = $_REQUEST['center'];
		if(!empty($_REQUEST['vectors'])) { // non serve
			$this->vectors = $_REQUEST['vectors'];
		}

        $this->options = array_merge($defaultOptions, $options);
		
		if(substr($this->options['TMP_PATH'], -1) != '/') $this->options['TMP_PATH'] .= '/';
		if(!is_dir($this->options['TMP_PATH']) || !is_writeable($this->options['TMP_PATH'])) {
			throw new Exception('unexisting or not writeable print tmp directory '.$this->options['TMP_PATH']);
		}
		if(substr($this->options['TMP_URL'], -1) != '/') $this->options['TMP_URL'] .= '/';
		
		if(defined('GC_PRINT_IMAGE_SIZE_INI') && file_exists(GC_PRINT_IMAGE_SIZE_INI)) {
			$this->dimensions = parse_ini_file(GC_PRINT_IMAGE_SIZE_INI, true);
		}
		
		if(!isset($this->dimensions[$this->options['direction']])) throw new Exception('Invalid direction');
		if(!isset($this->dimensions[$this->options['direction']][$this->options['format']])) throw new Exception('Invalid print format');
		
		if($options['scale_mode'] == 'user') {
			if(empty($options['extent']) || count($options['extent']) != 4)
				throw new Exception('For user-defined scale mode, an array of bottom, left, top, right coordinates must be provided');
		} else {
			if(empty($options['pixels_distance']) || empty($options['viewport_size']) || empty($options['center']))
				throw new Exception('For auto scale mode, pixels_distance, viewport_size and center must be provided');
		}
		$this->WMSMergeUrl = PUBLIC_URL.$this->WMSMergeUrl;
		
		$this->db = GCApp::getDB();
		
		if (!empty($_REQUEST['text']))
			$this->documentElements['map-text'] = $_REQUEST['text'];
		if (!empty($_REQUEST['scale']))
			$this->documentElements['map-scale'] = $_REQUEST['scale'];
		if (!empty($_REQUEST['date']))
			$this->documentElements['map-date'] = $_REQUEST['date'];
		if(!empty($_REQUEST['northArrow']) && $_REQUEST['northArrow'] != 'null') {
			$this->documentElements['north-arrow'] = $_REQUEST['northArrow'];
		}
		if(!empty($_REQUEST['copyrightString']) && $_REQUEST['copyrightString'] != 'null') {
			$this->documentElements['copyright-string'] = $_REQUEST['copyrightString'];
		}
		
		$this->documentElements['gisclient-folder'] = GC_PRINT_TPL_URL;
		$this->documentElements['map-logo-sx'] = GC_PRINT_TPL_URL.$this->nullLogo;
		$this->documentElements['map-logo-dx'] = GC_PRINT_TPL_URL.$this->nullLogo;
	}
	
	public function setLang($lang) {
		$this->documentElements['map-lang'] = $lang;
	}
	
	public function setLogo($logo, $position = 'sx') {
		$this->documentElements['map-logo-'.$position] = $logo;
	}
	
	public function printMapHTML() {
		$xslFile = GC_PRINT_TPL_DIR.'print_map_html.xsl';
		if(!file_exists($xslFile)) throw new Exception('XSL file ('.$xslFile.') not found');
		
		try {
			$dom = $this->buildDOM();
			$tmpdoc = new DOMDocument();
			$xsl = new XSLTProcessor();

			$tmpdoc->load($xslFile);
			$xsl->importStyleSheet($tmpdoc);

			$content = $xsl->transformToXML($dom);
			$filename = 'printmap_'.rand(0,99999999).'.html';
			file_put_contents($this->options['TMP_PATH'].$filename, $content);
			$this->deleteOldTmpFiles();
		} catch(Exception $e) {
			throw $e;
		}
		return $this->options['TMP_URL'].$filename;
	}
	
	public function printMapPDF() {
		$xslFile = GC_PRINT_TPL_DIR.'print_map.xsl';
		if(!file_exists($xslFile)) throw new Exception('XSL file not found');
		
		try {
			$dom = $this->buildDOM(true);
			$xml = $dom->saveXML();

			$pdfFile = runFOP($dom, $xslFile, array('tmp_path'=>$this->options['TMP_PATH'], 'prefix'=>'GCPrintMap-', 'out_name'=>$this->options['TMP_PATH'].'PrintMap-'.date('Ymd-His').'.pdf'));
			$pdfFile = str_replace($this->options['TMP_PATH'], $this->options['TMP_URL'], $pdfFile);
		} catch (Exception $e) {
			throw $e;
		}
		return $pdfFile;
	}
	
	public function getBox() {
		$this->calculateSizes();
		
		$options = array_merge($this->options, array('request_type'=>'get-box'));
		
		$mapImage = new mapImage($this->tiles, $this->imageSize, $this->options['srid'], $this->options);
		return $mapImage->getExtent();
	}
	
	protected function buildLegendGraphicWmsList() {
		foreach($this->wmsList as $wms) {
            if(empty($wms['PARAMETERS']['MAP']) ||
                $wms['PARAMETERS']['MAP'] == 'REDLINE' || 
                //empty($wms['PARAMETERS']['PROJECT'])) continue;
                empty($wms['PARAMETERS'])) continue;
			$legendGraphicRequest = array_merge($wms['PARAMETERS'], array(
				'url'=>(!empty($wms['URL'])?$wms['URL']:$wms['baseURL']),
				//'PROJECT'=>$wms['PARAMETERS']['PROJECT'],
				'REQUEST' => 'GetLegendGraphic',
				'ICONW' => 24,
				'ICONH' => 16,
				'GCLEGENDTEXT' => 0
			));
			$this->getLegendGraphicWmsList[$wms['PARAMETERS']['MAP']] = $legendGraphicRequest;
			$this->getLegendGraphicRequest = $legendGraphicRequest;
			/*
			$layers = explode(',',$wms['PARAMETERS']['LAYERS']);
			foreach($layers as $layer) {
				$this->getLegendGraphicWmsList[$wms] = $legendGraphicRequest;
			}*/
		}
	}
    
    protected function getLegendsFromMapfile() {
        $layers = array();
        $project = $mapset = null;
        $themes = array();
        
        foreach($this->wmsList as $wms) {
            if(!empty($wms['PARAMETERS']['PROJECT']) && empty($project)) $project = $wms['PARAMETERS']['PROJECT'];
            if(!empty($wms['PARAMETERS']['MAP']) && empty($mapset)) $mapset = $wms['PARAMETERS']['MAP'];
            
            foreach($wms['PARAMETERS']['LAYERS'] as $layerName) {
                if(isset($wms['options']['theme_id'])) {
                    if(!isset($themes[$wms['options']['theme_id']])) {
                        $themes[$wms['options']['theme_id']] = array(
                            'id'=>$wms['options']['theme_id'],
                            'title'=>$wms['options']['theme_title'],
                            'layers'=>array()
                        );
                    }
                    $themes[$wms['options']['theme_id']]['layers'][] = $layerName;
                }
            }
        }

        //if(!empty($project) && !empty($mapset)) {
        if(!empty($mapset)) {
            $oMap = ms_newMapobj(ROOT_PATH.'map/'.$mapset.'.map');
            foreach($themes as &$theme) {
                $theme['groups'] = array();
                foreach($theme['layers'] as $layergroupName) {
                    $layerIndexes = $oMap->getLayersIndexByGroup($layergroupName);
                    foreach($layerIndexes as $index) {
                        $oLayer = $oMap->getLayer($index);
                        $layerName = $oLayer->name;
                        $group = array(
                            'id'=>$layerName,
                            'title'=>$oLayer->getMetaData('ows_title'),
                            'layers'=>array()
                        );
                        for($n = 0; $n < $oLayer->numclasses; $n++) {
                            $oClass = $oLayer->getClass($n);
                            $exclude = $oClass->getMetaData('gc_no_image');
                            if(!empty($exclude)) continue;
                            array_push($group['layers'], array(
                                'url'=>$layerName.'-'.$n,
                                'title'=>$oClass->title
                            ));
                        }
                        array_push($theme['groups'], $group);
                    }
                }
            }
            unset($theme);
        }
        return array('themes'=>$themes);
    }
	
	protected function buildLegendArray() {
		if(empty($this->options['legend'])) return null;
		$this->buildLegendGraphicWmsList();
		try {
			$legendImages = array();
            if(!is_array($this->options['legend'])) {
                $this->options['legend'] = $this->getLegendsFromMapfile();
            }
            //var_export($this->options['legend']);
			foreach($this->options['legend']['themes'] as $theme) {
				if(empty($theme['groups'])) continue;
				$themeArray = array('id'=>$theme['id'],'title'=>$theme['title'],'groups'=>array());
				foreach($theme['groups'] as $group) {
					$groupArray = array('id'=>$group['id'],'title'=>$group['title'],'layers'=>array());
					if(empty($group['layers'])) continue;
					foreach($group['layers'] as $key => $layer) {
						$tmpFileId = $theme['id'].'-'.$group['id'];
						if(!isset($legendImages[$layer['url']])) {
							if(isset($group['sld'])) $sld = $group['sld'];
							else $sld = null;
							$legendImages[$layer['url']] = $this->getLegendImageWMS($group['id'], $group['id'], $tmpFileId, $sld);
						}
						if(!$legendImages[$layer['url']]) continue;
						$source = @imagecreatefrompng($legendImages[$layer['url']]);
                        if(!$source) continue;
						$dest = imagecreatetruecolor(35, 28);
                        imagecolortransparent($dest, imagecolorallocate($dest, 0, 0, 0));
						$offset = $key*28;
						imagecopy($dest, $source, 0, 0, 5, $offset, 35, 28);
						$filename = $tmpFileId.'-'.$key.'.png';
						imagepng($dest, $this->options['TMP_PATH'].$filename);
						array_push($groupArray['layers'], array('title'=>$layer['title'],'img'=>$this->options['TMP_URL'].$filename));
					}
					array_push($themeArray['groups'], $groupArray);
				}
				array_push($this->legendArray, $themeArray);
			}
            //var_export($this->legendArray);
		} catch(Exception $e) {
			throw $e;
		}
	}
	
	protected function getLegendImageWMS($layer, $group, $tmpFileId, $sld = null) {
		$request = $this->getLegendGraphicRequest;
		$request['LAYER'] = $group;
		$url = $request['url'];
		unset($request['url']);
		$queryString = http_build_query($request);
		if(!empty($sld)) $queryString .= '&SLD='.$sld;
		return $this->getLegendImage($url.'?'.$queryString, $tmpFileId);
	}
	
	protected function getLegendImage($url, $tmpFileId) {
		try {
			$dest = $this->options['TMP_PATH'].$tmpFileId.'.png';
			$url = printDocument::addPrefixToRelativeUrl($url);
			$ch = curl_init($url);
			$fp = fopen($dest, "wb");
file_put_contents(DEBUG_DIR.'getlegendgraphic.txt', $url."\n", FILE_APPEND);
			$options = array(CURLOPT_FILE => $fp, CURLOPT_HEADER => 0, CURLOPT_FOLLOWLOCATION => 1, CURLOPT_TIMEOUT => 60);
			curl_setopt_array($ch, $options);

			curl_exec($ch);
			curl_close($ch);
			fclose($fp);
		} catch(Exception $e) {
			throw $e;
		}
		return $dest;
	}
	
	protected function calculateSizes() {
		$dimension = array(
			'w'=>$this->dimensions[$this->options['direction']][$this->options['format']]['w'], 
			'h'=>$this->dimensions[$this->options['direction']][$this->options['format']]['h']
		);

		$this->imageSize = array(
			(int)round(($dimension['w']/(2.54))*$this->options['dpi']), 
			(int)round(($dimension['h']/(2.54))*$this->options['dpi'])
		);
		$this->options['fixed_size'] = array(
			(int)round(($dimension['w']/(2.54))*72), 
			(int)round(($dimension['h']/(2.54))*72)
		);
		
		$this->documentSize = $dimension;
	}
	
	protected function getMapImage() {
		try {
			$this->calculateSizes();
			
			$mapImage = new mapImage($this->tiles, $this->imageSize, $this->options['srid'], $this->options);
			$this->wmsList = $mapImage->getWmsList();
			$this->imageFileName = $mapImage->getImageFileName();
			
		} catch(Exception $e) {
			throw $e;
		}
	}
	
	protected function buildDOM($absoluteUrls = false) {
		try {
			$this->getMapImage();
			$this->buildLegendArray();
		} catch(Exception $e) {
			throw $e;
		}

		$dom = new DOMDocument('1.0', 'utf-8');
		$dom->formatOutput = true;
		$dom_map = $dom->appendChild(new DOMElement('map'));

		$dom_layout = $dom_map->appendChild(new DOMElement('page-layout'));
		
		$direction = ($this->options['direction'] == 'vertical') ? 'P' : 'L';
		$layout = $this->options['format'].$direction;
		
		$dom_layout->appendChild(new DOMText($layout));

		$dom_width = $dom_map->appendChild(new DOMElement('map-width'));
		$dom_width->appendChild(new DOMText($this->documentSize['w']));

		$dom_height = $dom_map->appendChild(new DOMElement('map-height'));
		$dom_height->appendChild(new DOMText($this->documentSize['h']));

		$dom_img = $dom_map->appendChild(new DOMElement('map-img'));
		$mapImgUrl = $this->options['TMP_URL'].$this->imageFileName;
		if($absoluteUrls) $mapImgUrl = printDocument::addPrefixToRelativeUrl($mapImgUrl);
		$dom_img->appendChild(new DOMText($mapImgUrl));

		if(isset($this->documentElements['map-date'])) {
			$dom_date = $dom_map->appendChild(new DOMElement('map-date'));
			$dom_date->appendChild(new DOMText($this->documentElements['map-date']));
		}

		foreach($this->documentElements as $key => $val) {
			$dom_element = $dom_map->appendChild(new DOMElement($key));
			if(strpos($key, 'map-logo') !== false && $absoluteUrls) $val = printDocument::addPrefixToRelativeUrl($val);
			$dom_element->appendChild(new DOMText($val));
		}

		if(!empty($this->legendArray)) {
			$dom_legend = $dom_map->appendChild(new DOMElement('map-legend'));
			
			$i = 0;			
			foreach($this->legendArray as $theme) {
				if(empty($theme['groups'])) continue;
				$continue = true;
				foreach($theme['groups'] as $group) {
					if(!empty($group['layers'])) $continue = false;
				}
				if($continue) continue;
				$dom_group = $dom_legend->appendChild(new DOMElement('legend-group'));
				$dom_title = $dom_group->appendChild(new DOMElement('group-title'));
				$dom_title->appendChild(new DOMText($theme['title']));

				$dom_icon = $dom_group->appendChild(new DOMElement('group-icon'));
				$dom_icon->appendChild(new DOMText(''));
				
				foreach($theme['groups'] as $group) {
					foreach($group['layers'] as $layer) {
						if ($i % 3 == 0) $dom_grp_block = $dom_group->appendChild(new DOMElement('group-block'));
						$i++;
						$dom_item = $dom_grp_block->appendChild(new DOMElement('group-item'));
						$dom_item_attr = $dom_item->appendChild(new DOMElement('title'));
						$dom_item_attr->appendChild(new DOMText($layer['title']));
						$dom_item_attr = $dom_item->appendChild(new DOMElement('icon'));
						if($absoluteUrls) $layer['img'] = printDocument::addPrefixToRelativeUrl($layer['img']);
						$dom_item_attr->appendChild(new DOMText($layer['img']));
					}		
				}
				while($i % 3 != 0) {
					$dom_item = $dom_grp_block->appendChild(new DOMElement('group-item'));
					$i++;
				}
			}
		}
		
		$xmlContent = $dom->saveXML();
		file_put_contents($this->options['TMP_PATH'].'print.xml', $xmlContent);
		
		return $dom;
	}
	
	protected function deleteOldTmpFiles() {
		if($this->options['TMP_PATH'] == '/tmp/') return;
		if ($handle = opendir($this->options['TMP_PATH'])) {
			while (false !== ($file = readdir($handle))) {
				if ($file[0] == '.')
					continue;

				$name = $this->options['TMP_PATH'] . '/' . $file;
				$isold = (time() - filectime($name)) > 5 * 60 * 60;
				$ext = strtolower(strrchr($name, '.'));
				if (is_file($name) && $isold) {
					unlink($name);
				}
			}
			closedir($handle);
		}
	}
	
    public static function addPrefixToRelativeUrl($url) {
        if (defined('PRINT_RELATIVE_URL_PREFIX') && !preg_match("/^(http|https):\/\//", $url, $matches)) {
            return PRINT_RELATIVE_URL_PREFIX.$url;
        } else if (!preg_match("/^(http|https):\/\//", $url, $matches)) {
            throw new Exception('Undefined PRINT_RELATIVE_URL_PREFIX, cannot add to '.$url);
        }
        return $url;
    }
	
}
