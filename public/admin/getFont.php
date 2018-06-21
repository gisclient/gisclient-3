<?php
require_once "../../config/config.php";
require_once ADMIN_PATH."lib/Font.php";

$db = GCApp::getDB();

if(isset($_REQUEST['font'])) {
  $fontName = basename($_REQUEST['font']);
  $file = ROOT_PATH . 'fonts/' . $fontName;

  if (is_readable($file)) {
	header('Content-Description: File Transfer');
	header('Content-Type: application/octet-stream');
	header('Content-Disposition: attachment; filename='.basename($file));
	header('Expires: 0');
	header('Cache-Control: must-revalidate');
	header('Pragma: public');
	header('Content-Length: ' . filesize($file));
	readfile($file);
	exit;
  } else {
	echo('not found');
  }
} else if(isset($_REQUEST['action'])) {
  switch($_REQUEST['action']){
    case 'load':
      $sql = "select font_name, symbol_def from ".DB_SCHEMA.".symbol";
      try {
        $result = $db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
	  } catch(Exception $e) {
	    $ajax->error($e->getMessage());
	  }
	  $stmt = $db->prepare($sql);
	  $stmt->execute();
	  $result = array();
      $fontList = ROOT_PATH . 'fonts/fonts.list';
	  $fileContents = file_get_contents($fontList);
      while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $aux = "";
        if(!empty($row['font_name']))
          $aux = $row['font_name'];
        else if(!empty($row['symbol_def'])){
          preg_match("/FONT \"([a-zA-Z0-9\-_]+)\"/",$row['symbol_def'], $tmp);
          if(count($tmp) > 1)
            $aux = $tmp[1];
        }
        if(!empty($aux)) {
          preg_match('/'.$aux.'[ \t]*([a-zA-Z0-9\-_]+\.ttf)[ \t]*$/m', $fileContents, $tmp);
          if(count($tmp) > 1)
            $result[$aux] = $tmp[1];
        }
      }
      echo json_encode($result);
      break;
    default:
      echo 'Funzione non valida';
      break;
  }
}
