<?php
  include_once "../../../config/config.php";
  error_reporting (E_ERROR | E_PARSE);
  $db = GCApp::getDB();
  $message = "";
  if(!empty($_POST["filename"])) {
    include_once ADMIN_PATH."lib/export.php";
	$objId=$_POST["obj_id"];
	$level=$_POST["level"];
	$project=$_POST["project"];
	$livello=$_POST["livello"];
	$fName=$_POST["filename"];
	$newName = empty($_POST["name"]) ? $_POST["filename"] : $_POST["name"];
	$layer=$_POST["layer"];
	if($project!=''){
	  $sql="SELECT project_name FROM ".DB_SCHEMA.".project WHERE project_name=:project";
      $stmt = $db->prepare($sql);
      try {
        $stmt->execute(array('project'=>$project));
      } catch(Exception $e) {
        echo "Impossibile eseguire la query : $sql";
      }
      $projectName = $stmt->fetchColumn(0);
	} else
	  $projectName = $newName;
	if (!file_exists(ADMIN_PATH."export/$fName"))
	  $message = "File $fName non esistente.";
	else {
	  $parentId=Array($objId);
	  if($layer) {
	    $layer=$_POST["layer"];
		$objId=$_POST["obj_id"];
	  }
	  $error=import(ADMIN_PATH."export/$fName",$objId,$projectName,$newName,$layer);
      if (!$error)
        $message = "Procedura di importazione per $newName terminata correttamente.";
	  else {
        $message = "Errore nell'importazione di $fName su entità $newName:";
        $message .= "<ul><li>".implode("</li><li>",$error)."</li></ul>";
	  }
	  if($_POST["overwrite"] == "1") {
        unlink(ADMIN_PATH."export/$fName");
        $message .= "<br/>File $fName eliminato correttamente.";
      }
    }
  } else {
    $message = "Errore nell'importazione. Specificare un file per procedere.";
  }
  echo $message;
?>
