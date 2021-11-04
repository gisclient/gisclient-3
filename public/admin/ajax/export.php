<?php
  include_once "../../../config/config.php";
  error_reporting (E_ERROR | E_PARSE);
  $db = GCApp::getDB();
  $array_levels = array();
  $Errors = array();
  include_once ADMIN_PATH."lib/export.php";
  $level=$_POST["level"];
  $project=$_POST["project"];
  $objId=$_POST["obj_id"];
  $fName=$_POST["filename"];
  $overwrite=$_POST["overwrite"];
  $structure=_getPKeys();
  if (!file_exists(ADMIN_PATH."export/$fName") || $overwrite){
    if (file_exists(ADMIN_PATH."export/$fName"))
      $overwrite_message="Il File $fName è stato sovrascritto.";
    $l=$structure["pkey"][$_POST["livello"]][0];
	$sql="select e_level.id,e_level.name,coalesce(e_level.struct_parent_id,0) as parent,X.name as parent_name,e_level.leaf from ".DB_SCHEMA.".e_level left join ".DB_SCHEMA.".e_level X on (e_level.struct_parent_id=X.id) order by e_level.depth asc;";
    try {
      $stmt = $db->query($sql);
      //secondo me questo array_levels non serve a niente...
      while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $array_levels[$v["id"]]=Array("name"=>$v["name"],"parent"=>$v["parent"],"leaf"=>$v["leaf"]);
      }
    } catch(Exception $e) {
      die("Impossibile eseguire la query : $sql");
    }
	$r=_export($fName,$_POST["livello"],$project,$structure,1,'',Array("$l"=>$objId),$Errors);
	$message="$overwrite_message <br> FILE <a href=\"export/$fName\" download>$fName</a> ESPORTATO CORRETTAMENTE";
  } else {
    $message = "Il File $fName è già presente. Utilizzare un nome differente o procedere alla sovrascrizione.";
  }
  die($message);
?>
