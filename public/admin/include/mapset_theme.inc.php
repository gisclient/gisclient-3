<?php
require_once "../../config/config.php";
$project=$this->parametri["project"];
$mapset=$this->parametri["mapset"];
$db = GCApp::getDB();
$data = Array();
$JOIN=($this->mode==0)?(" INNER JOIN "):(" LEFT JOIN ");
$sql="SELECT DISTINCT coalesce(mapset_name,'') as mapset_name, X.theme_title, theme_id, rootpath, mapset_theme_title, mapset_theme_order, CASE WHEN COALESCE(mapset_name, '') <> '' THEN 1 ELSE 0 END  AS presente from (select distinct theme_id, theme_title from ".DB_SCHEMA.".layergroup inner join ".DB_SCHEMA.".theme using (theme_id) inner join ".DB_SCHEMA.".mapset_layergroup using (layergroup_id) where project_name=:project and mapset_name=:mapset) as X $JOIN (select * from ".DB_SCHEMA.".mapset_theme where mapset_name=:mapset) as foo using(theme_id) order by X.theme_title";
//echo $sql;

try {
    $stmt = $db->prepare($sql);
    $stmt->execute(array('project'=>$project, 'mapset'=>$mapset));
    if($stmt->rowCount() > 0) {
        while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            if($this->mode!=0 || $row['presente']==1) {
                array_push($data, array(
                    'presente'=>$row['presente'],
                    'theme_id'=>$row['theme_id'],
                    'theme_title'=>$row['theme_title'],
                    'rootpath'=>$row['rootpath'],
                    'mapset_theme_title'=>$row['mapset_theme_title'],
                    'mapset_theme_order'=>$row['mapset_theme_order']
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
