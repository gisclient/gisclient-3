<?php
require_once "../../config/config.php";
$project=$this->parametri["project"];
$mapset=$this->parametri["mapset"];
$db = GCApp::getDB();
$data = Array();

$JOIN=($this->mode==0)?(" INNER JOIN "):(" LEFT JOIN ");
$sql="SELECT DISTINCT coalesce(mapset_name,'') as mapset_name,X.layergroup_id, CASE WHEN COALESCE(mapset_name, '') <> '' THEN 1 ELSE 0 END  AS presente, X.layergroup_name,X.layergroup_title,X.theme_title,COALESCE(status::integer, 0) AS status,COALESCE(hide ::integer, 0) AS hide,COALESCE(refmap::integer, 0) AS refmap from (select layergroup_id,layergroup_name,layergroup_title,theme_title from ".DB_SCHEMA.".layergroup inner join ".DB_SCHEMA.".theme using (theme_id) where project_name=:project) as X $JOIN (select * from ".DB_SCHEMA.".mapset_layergroup where mapset_name=:mapset) as foo using(layergroup_id) order by X.theme_title,X.layergroup_title";
//echo $sql;

try {
    $stmt = $db->prepare($sql);
    $stmt->execute(array('project'=>$project, 'mapset'=>$mapset));
    if($stmt->rowCount() > 0) {
        while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            if($this->mode!=0 || $row['presente']==1) {
                array_push($data, array(
                    'presente'=>$row['presente'],
                    'layergroup_id'=>$row['layergroup_id'],
                    'layergroup_title'=>$row['layergroup_title'],
                    'theme_title'=>$row['theme_title'],
                    'status'=>$row['status'],
                    'hide'=>$row['hide'],
                    'refmap'=>$row['refmap']
                ));
            }
        }
    } else {
        $msg="Nessun Livello Interrogabile definito nel Gruppo di selezione";
    }
} catch(Exception $e) {
    $msg="<b style=\"color:red\">Errore</b> ".$e->getMessage();
}

	
$btn[] = '<button name="azione" class="hexfield" type="submit" value="annulla">'.GCAuthor::t('button_cancel').'</button>';
$btn[] = '<button name="azione" class="hexfield" type="submit" value="salva">'.GCAuthor::t('button_save').'</button>';
$button="modifica";
?>