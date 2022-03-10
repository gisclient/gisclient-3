<?php
require_once "../../config/config.php";
$qtfield=$this->parametri["qt_field"];
$project=$this->parametri["project"];
$db = GCApp::getDB();
$data = array();

$JOIN=($this->mode==0)?(" INNER JOIN "):(" LEFT JOIN ");

$sql="SELECT X.groupname,CASE WHEN (SELECT count(*)=0 FROM ".DB_SCHEMA.".qt_field_groups  WHERE qt_field_id=:qtfield_id) THEN 1 WHEN coalesce(Y.groupname,'')='' THEN 0 ELSE 1 end as presente FROM ".DB_SCHEMA.".groups X LEFT JOIN (SELECT groupname FROM ".DB_SCHEMA.".qt_field_groups  WHERE qt_field_id=:qtfield_id) Y USING(groupname);";

try {
    $stmt = $db->prepare($sql);
    $stmt->execute(array('qtfield_id'=>$qtfield));

    if($stmt->rowCount() > 0) {
        while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            if($this->mode!=0 || $row['presente']==1) {
                array_push($data, array(
                    'presente'=>$row['presente'],
                    'groupname'=>$row['groupname']
                ));
            }
        }
    } else {
		$msg="";
    }
} catch(Exception $e) {
	$msg="<b style=\"color:red\">Errore</b>";
}


$btn[] = '<button name="azione" class="hexfield" type="submit" value="annulla">'.GCAuthor::t('button_cancel').'</button>';
$btn[] = '<button name="azione" class="hexfield" type="submit" value="salva">'.GCAuthor::t('button_save').'</button>';
$button="modifica";
?>
