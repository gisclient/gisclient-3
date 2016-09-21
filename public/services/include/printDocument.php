<?php

class printDocument {

    private $options;

    private $tiles = array();
    private $dimensions = array(
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
    private $wmsMergeUrl = 'services/gcWMSMerge.php';
    private $wmsList = array();
    private $imageSize = array();
    private $documentSize = array();
    private $documentElements = array();
    private $imageFileName = '';
    private $legendArray = array();
    private $vectors = array();
    private $db = null;
    private $getLegendGraphicWmsList = array();
    private $nullLogo = 'null.png';
    private $getLegendGraphicRequest;
    
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
            'srid' => null,
            'auth_name'=>'EPSG',
        );
        
        $options = array();

        if(!empty($_REQUEST['tiles']) && is_array($_REQUEST['tiles'])) {
            $this->tiles = $_REQUEST['tiles'];
        } else {
            if(empty($_REQUEST['request_type']) || ($_REQUEST['request_type'] != 'get-box' &&$_REQUEST['request_type'] != 'table')) {
                throw new Exception('No tiles');
            }
        }

        if (!empty($_REQUEST['extent']))
            $options['extent'] = explode(',', $_REQUEST['extent']);
        if (!empty($_REQUEST['scale_mode']))
            $options['scale_mode'] = $_REQUEST['scale_mode'];
        if (!empty($_REQUEST['legend']))
            $options['legend'] = $_REQUEST['legend'];
        if (!empty($_REQUEST['scale'])) // non serve
            $options['scale'] = $_REQUEST['scale'];
        if (!empty($_REQUEST['printFormat']))
            $options['format'] = $_REQUEST['printFormat'];
        if (!empty($_REQUEST['direction']) && in_array($_REQUEST['direction'], array('horizontal', 'vertical')))
            $options['direction'] = $_REQUEST['direction'];
        if (!empty($_REQUEST['dpi']) && is_numeric($_REQUEST['dpi']))
            $options['dpi'] = (int) $_REQUEST['dpi'];
        if (!empty($_REQUEST['rotation']) && is_numeric($_REQUEST['rotation']))
            $options['rotation'] = (double) $_REQUEST['rotation'];
        if (!empty($_REQUEST['srid'])) {
            $options['srid'] = $_REQUEST['srid'];
            if (strpos($_REQUEST['srid'], ':') !== false) {
                $sridParts = explode(':', $_REQUEST['srid']);
                if (count($sridParts) == 2) {
                    // e.g.: EPSG:4306
                    $options['auth_name'] = $sridParts[0];
                    $options['srid'] = $sridParts[1];
                } elseif (count($sridParts) == 7) {
                    // e.g.: urn:ogc:def:crs:EPSG::4306
                    $options['auth_name'] = $sridParts[4];
                    $options['srid'] = $sridParts[6];
                } else {
                    throw new Exception("Could not parse ".$_REQUEST['srid']." as srid");
                }
            }
        }
        if (!empty($_REQUEST['center']))
            $options['center'] = $_REQUEST['center'];
        if(!empty($_REQUEST['vectors'])) {
            $this->vectors = $_REQUEST['vectors'];
        }

        $this->options = array_merge($defaultOptions, $options);
        
        if(substr($this->options['TMP_PATH'], -1) != '/') $this->options['TMP_PATH'] .= '/';
        if(!is_dir($this->options['TMP_PATH']) || !is_writeable($this->options['TMP_PATH'])) {
            throw new RuntimeException('unexisting or not writeable print tmp directory '.$this->options['TMP_PATH']);
        }
        if(substr($this->options['TMP_URL'], -1) != '/') $this->options['TMP_URL'] .= '/';
        
        if(defined('GC_PRINT_IMAGE_SIZE_INI') && file_exists(GC_PRINT_IMAGE_SIZE_INI)) {
            $this->dimensions = parse_ini_file(GC_PRINT_IMAGE_SIZE_INI, true);
        }
        
        if(!isset($this->dimensions[$this->options['direction']])) throw new Exception('Invalid direction');
        if(!isset($this->dimensions[$this->options['direction']][$this->options['format']])) throw new Exception('Invalid print format');
        
        if (!empty($_REQUEST['request_type']) && $_REQUEST['request_type'] != 'table') {
            if(isset($options['scale_mode']) && $options['scale_mode'] == 'user') {
                if(empty($options['scale']))
                    throw new Exception('For user-defined scale mode, the scale must be provided');
                if(empty($options['center']) || count($options['center']) != 2)
                    throw new Exception('For user-defined scale mode, an array of center coordinates must be provided');
            } else {
                if(empty($options['extent']))
                    throw new Exception('For auto scale mode, the extend must be provided');
            }
        }
        $this->wmsMergeUrl = PUBLIC_URL.$this->wmsMergeUrl;
        
        $this->db = GCApp::getDB();
        


        if(!empty($_REQUEST["template"]))
            $this->documentElements['template'] = $_REQUEST["template"];

        if (!empty($_REQUEST['text']))
            $this->documentElements['map-text'] = $_REQUEST['text'];
        if (!empty($_REQUEST['scale']))
            $this->documentElements['map-scale'] = $_REQUEST['scale'];
        if (!empty($_REQUEST['date']))
            $this->documentElements['map-date'] = $_REQUEST['date'];
        if(!empty($_REQUEST['northArrow']) && $_REQUEST['northArrow'] != 'null') {
            $this->documentElements['north-arrow'] = GC_PRINT_TPL_URL.$_REQUEST['northArrow'];
        }
        if(!empty($_REQUEST['copyrightString']) && $_REQUEST['copyrightString'] != 'null') {
            $this->documentElements['copyright-string'] = $_REQUEST['copyrightString'];
        }
        
        $this->documentElements['gisclient-folder'] = GC_PRINT_TPL_URL;
        $this->documentElements['map-logo-sx'] = GC_PRINT_TPL_URL.$this->nullLogo;
        $this->documentElements['map-logo-dx'] = GC_PRINT_TPL_URL.$this->nullLogo;
        $this->documentElements['map-box'] = $_REQUEST['extent'];
 
    }
    
    public function setLang($lang) {
        $this->documentElements['map-lang'] = $lang;
    }
    
    public function setLogo($logo, $position = 'sx') {
        $this->documentElements['map-logo-'.$position] = $logo;
    }
    
    public function printMapHTML() {
        $xslFile = isset($_REQUEST["template"])?$_REQUEST["template"]:'print_map_html';//DEFAULT HTML TEMPLATE
        $xslFile = GC_PRINT_TPL_DIR.$xslFile.".xsl";
        if(!file_exists($xslFile)) throw new RuntimeException('XSL file ('.$xslFile.') not found');
        

        $dom = $this->buildDOM();
        $tmpdoc = new DOMDocument();
        $xsl = new XSLTProcessor();

        $tmpdoc->load($xslFile);
        $xsl->importStyleSheet($tmpdoc);

        $content = $xsl->transformToXML($dom);
        $filename = 'printmap_'.rand(0,99999999).'.html';
        $mapHtmlFile = $this->options['TMP_PATH'].$filename;
        if (false === file_put_contents($mapHtmlFile, $content)) {
            throw new RuntimeException("Could not write to $mapHtmlFile");
        }
        $this->deleteOldTmpFiles();
        return $this->options['TMP_URL'].$filename;
    }
    
    public function printMapPDF() {
        $xslFile = isset($_REQUEST["template"])?$_REQUEST["template"]:'print_map';//DEFAULT PDF TEMPLATE
        $xslFile = GC_PRINT_TPL_DIR.$xslFile.".xsl";;
        if(!file_exists($xslFile)) {
            throw new RuntimeException("XSL file '$xslFile'not found");
        }
        $dom = $this->buildDOM(true);
        $xml = $dom->saveXML();

        $pdfFile = runFOP($dom, $xslFile, array('tmp_path'=>$this->options['TMP_PATH'], 'prefix'=>'GCPrintMap-', 'out_name'=>$this->options['TMP_PATH'].'PrintMap-'.date('Ymd-His').'.pdf'));
        $pdfFile = str_replace($this->options['TMP_PATH'], $this->options['TMP_URL'], $pdfFile);
        $this->deleteOldTmpFiles();
        return $pdfFile;
    }

    public function printTablePDF($data) {
        $xslFile = isset($_REQUEST["template"])?$_REQUEST["template"]:'print_table';//DEFAULT PDF TEMPLATE
        $xslFile = GC_PRINT_TPL_DIR.$xslFile.".xsl";;
        if(!file_exists($xslFile)) {
            throw new RuntimeException("XSL file '$xslFile'not found");
        }
        $dom = $this->buildTableDOM($data, true);
        $xml = $dom->saveXML();

        $pdfFile = runFOP($dom, $xslFile, array('tmp_path'=>$this->options['TMP_PATH'], 'prefix'=>'GCPrintTable-', 'out_name'=>$this->options['TMP_PATH'].'PrintTable-'.date('Ymd-His').'.pdf'));
        $pdfFile = str_replace($this->options['TMP_PATH'], $this->options['TMP_URL'], $pdfFile);
        $this->deleteOldTmpFiles();
        return $pdfFile;
    }

    public function getDimensions() {
        return $this->dimensions;
    }

    public function getBox() {
        $this->calculateSizes();
        
        $mapImage = new mapImage($this->tiles, $this->imageSize, $this->options['srid'], $this->options);
        return $mapImage->getExtent();
    }


    private function buildLegendGraphicWmsList() {
        foreach($this->wmsList as $wms) {
            if (!isset($wms['PARAMETERS']['SERVICE']) ||
                $wms['PARAMETERS']['SERVICE'] != 'WMS'){
                continue;
            }
            if ($wms['PARAMETERS']['SERVICE'] == 'REDLINE') {
                continue;
            }
            $legendGraphicRequest = array_merge($wms['PARAMETERS'], array(
                'url'=>(!empty($wms['URL'])?$wms['URL']:$wms['baseURL']),
                'PROJECT'=>$wms['PARAMETERS']['PROJECT'],
                'REQUEST' => 'GetLegendGraphic',
                'ICONW' => 24,
                'ICONH' => 16,
                'GCLEGENDTEXT' => 0
            ));
            if(defined("GC_SESSION_NAME")){
                $legendGraphicRequest['GC_SESSION_ID'] = session_id();
            }
            $this->getLegendGraphicWmsList[$wms['PARAMETERS']['MAP']] = $legendGraphicRequest;
            $this->getLegendGraphicRequest = $legendGraphicRequest;
        }
    }

	//Recupera la struttura layergroup=>layer=>class dal mapfile se non viene passata dal client
    protected function getLegendsFromMapfile() {
        $layers = array();
        $project = $mapset = null;
        $themes = array();

        foreach($this->tiles as $wms) {
            if(!empty($wms['parameters']['PROJECT']) && empty($project)) $project = $wms['parameters']['PROJECT'];
            if(!empty($wms['parameters']['MAP']) && empty($mapset)) $mapset = $wms['parameters']['MAP'];
            
            //print_array($wms);

            foreach($wms['parameters']['LAYERS'] as $layerName) {
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

        if(!empty($project) && !empty($mapset)) {
            $oMap = ms_newMapobj(ROOT_PATH.'map/'.$project.'/'.$mapset.'.map');
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
                        if(!$legendImages[$layer['url']]) {
                            continue;
                        }
                        if (filesize($legendImages[$layer['url']]) == 0) {
                            // something went wrong
                            continue;
                        }
                        // TODO: add some check if the image is high enough to be sliced
                        $source = imagecreatefrompng($legendImages[$layer['url']]);
                        $dest = imagecreatetruecolor(24, 16);
                        $offset = $key*16;
                        imagecopy($dest, $source, 0, 0, 0, $offset, 24, 16);
                        $filename = $tmpFileId.'-'.$key.'.png';
                        imagepng($dest, $this->options['TMP_PATH'].$filename);
                        array_push($groupArray['layers'], array('title'=>$layer['title'],'img'=>$this->options['TMP_URL'].$filename));
                    }
                    array_push($themeArray['groups'], $groupArray);
                }
                array_push($this->legendArray, $themeArray);
            }
        } catch(Exception $e) {
            throw $e;
        }
    }
    
    private function getLegendImageWMS($layer, $group, $tmpFileId, $sld = null) {
        $request = $this->getLegendGraphicRequest;
        $request['LAYER'] = $group;
        $url = $request['url'];
        unset($request['url']);
        $queryString = http_build_query($request);
        if(!empty($sld)) $queryString .= '&SLD='.$sld;
        return $this->getLegendImage($url.'?'.$queryString, $tmpFileId);
    }
    
    private function getLegendImage($url, $tmpFileId) {
        $dest = $this->options['TMP_PATH'].$tmpFileId.'.png';
        $finalUrl = printDocument::addPrefixToRelativeUrl($url);
        $ch = curl_init($finalUrl);
        if (false === ($fp = fopen($dest, "wb"))) {
            throw new RuntimeException("Unable to open file $dest in write mode");
        }
        $options = array(
            CURLOPT_FILE => $fp,
            CURLOPT_HEADER => 0,
            CURLOPT_FOLLOWLOCATION => 1,
            CURLOPT_TIMEOUT => 60
            );
        curl_setopt_array($ch, $options);

        if (false === curl_exec($ch)) {
            $errMsg = "Call to $finalUrl returned with error ".curl_error($ch);
            throw new RuntimeException($errMsg);
        }
        if (200 != ($httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE))) {
            throw new RuntimeException("Call to $finalUrl return HTTP code $httpCode");
        }
        curl_close($ch);
        fclose($fp);
        return $dest;
    }
    
    private function calculateSizes() {
        $dimension = array(
            'w'=>$this->dimensions[$this->options['direction']][$this->options['format']]['w'], 
            'h'=>$this->dimensions[$this->options['direction']][$this->options['format']]['h']
        );

        $this->imageSize = array(
            (int)round($dimension['w'] * ($this->options['dpi'] / 2.54)), 
            (int)round($dimension['h'] * ($this->options['dpi'] / 2.54)),
        );
        $this->documentSize = $dimension;
    }
    
    private function getMapImage() {
        $this->calculateSizes();

        if(!empty($this->vectors)) {
            $this->options['vectors'] = $this->vectors;
        }
        
        $mapImage = new mapImage($this->tiles, $this->imageSize, $this->options['srid'], $this->options);
        $this->wmsList = $mapImage->getWmsList();
        $this->imageFileName = $mapImage->getImageFileName();
    }
    
    private function buildDOM($absoluteUrls = false) {
        $this->getMapImage();
        $this->buildLegendArray();

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
        $xmlFile = $this->options['TMP_PATH'].'print.xml';
        if (false === file_put_contents($xmlFile, $xmlContent)) {
            throw new RuntimeException("Could not write to $xmlFile");
        }
        
        return $dom;
    }
    
    private function buildTableDOM($tableData, $absoluteUrls = false) {
        
        $dom = new DOMDocument('1.0', 'utf-8');
        $dom->formatOutput = true;
        
        $dom_report = $dom->appendChild(new DOMElement('Report'));
        
        $dom_table = $dom_report->appendChild(new DOMElement('ReportData'));
        
        if(empty($tableData['data']) || !is_array($tableData['data'])) {
            return $dom;
        }

        if(empty($tableData['fields']) || !is_array($tableData['fields'])) {
            return $dom;
        }

        // **** Set column headers
        $tot_col_width = 0;
        $dom_col_headers = $dom_table->appendChild(new DOMElement('ColumnHeaders'));
        foreach($tableData['fields'] as $field){
            $dom_col_header = $dom_col_headers->appendChild(new DOMElement('ColumnHeader'));
            $dom_col_name = $dom_col_header->appendChild(new DOMElement('Name'));
            $dom_col_name->appendChild(new DOMText($field['title']));
            
            $col_width=0;
            if (isset($field['width']) && $field['width'] > 0) {
                $col_width = $field['width'];
            }
            else {
                $col_width = strlen($field['title']);
                $col_width = ceil($col_width/3);
            }
            $tot_col_width += $col_width;
            $dom_col_width = $dom_col_header->appendChild(new DOMElement('Width'));
            $dom_col_width->appendChild(new DOMText($col_width));         
        }

        // **** Set table rows
        $dom_rows = $dom_table->appendChild(new DOMElement('Rows'));
        foreach($tableData['data'] as $row) {
            $dom_row = $dom_rows->appendChild(new DOMElement('Row'));
            foreach($tableData['fields'] as $field) {
                $dom_col = $dom_row->appendChild(new DOMElement('Column'));
                if(isset($row[$field['field_name']]) && !empty($row[$field['field_name']]) && $row[$field['field_name']] != 'null') {
                    $dom_col->appendChild(new DOMText($row[$field['field_name']]));
                }
            }
        } 
 
        $dom_total_width = $dom_report->appendChild(new DOMElement('total-width'));
        $dom_total_width->appendChild(new DOMText($tot_col_width));
        
        $dom_layout = $dom_report->appendChild(new DOMElement('page-layout'));
        
        $layout = '';
        if ($this->dimensions[$this->options['direction']][$this->options['format']]['w'] >= $tot_col_width ) {
            $direction = ($this->options['direction'] == 'vertical') ? 'P' : 'L';
            $layout = $this->options['format'].$direction;
        }
        else {
            foreach($this->dimensions[$this->options['direction']] as $format => $dim) {
                if ($dim['w'] >= $tot_col_width) {
                    $direction = ($this->options['direction'] == 'vertical') ? 'P' : 'L';
                    $layout = $format.$direction;
                    break;
                }
            }
        }
        if (strlen($layout) == 0)
            $layout = 'A0L';
        
        
            
        $dom_layout->appendChild(new DOMText($layout));

        foreach($this->documentElements as $key => $val) {
            $dom_element = $dom_report->appendChild(new DOMElement($key));
            if(strpos($key, 'map-logo') !== false && $absoluteUrls) $val = printDocument::addPrefixToRelativeUrl($val);
            $dom_element->appendChild(new DOMText($val));
        }

        $xmlContent = $dom->saveXML();
        $xmlFile = $this->options['TMP_PATH'].'print.xml';
        if (false === file_put_contents($xmlFile, $xmlContent)) {
            throw new RuntimeException("Could not write to $xmlFile");
        }
        
        return $dom;
    }
    
    private function deleteOldTmpFiles() {
        if($this->options['TMP_PATH'] == '/tmp/') return;
        if ($handle = opendir($this->options['TMP_PATH'])) {
            while (false !== ($file = readdir($handle))) {
                if ($file[0] == '.')
                    continue;

                $name = $this->options['TMP_PATH'] . '/' . $file;
                $isold = (time() - filectime($name)) > 5 * 60 * 60;
                if (is_file($name) && $isold) {
                    if (false === unlink($name)) {
                        throw new RuntimeException("Could not remove $name");
                    }
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
