<?php
require_once "../../config/config.php";
$project=$this->parametri["project"];
$selgroup=$this->parametri["selgroup"];
$db = GCApp::getDB();
$data = Array();

$JOIN=($this->mode==0)?(" INNER JOIN "):(" , ");
$JOINFIELD=($this->mode==0)?(" USING (layer_id) "):("");

$sql="SELECT DISTINCT 
selgroup_id,A.layer_id,A.layer_name,
CASE WHEN COALESCE(B.layer_id, 0) > 0 THEN 1 ELSE 0 END  AS presente,
coalesce(layergroup_title,'') as layergroup_name,
coalesce(theme_title,'') as theme_title
FROM 
((".DB_SCHEMA.".layer INNER JOIN ".DB_SCHEMA.".layergroup using(layergroup_id)) INNER JOIN ".DB_SCHEMA.".theme using(theme_id)) A $JOIN
(select * from ".DB_SCHEMA.".selgroup_layer RIGHT JOIN ".DB_SCHEMA.".selgroup using(selgroup_id) where selgroup_id=:selgroup_id) as B $JOINFIELD 
WHERE 
queryable=1 and A.project_name=:project order by theme_title,layergroup_name,layer_name";



try {
    $stmt = $db->prepare($sql);
    $stmt->execute(array('selgroup_id'=>$selgroup, 'project'=>$project));
    
    if($stmt->rowCount() > 0) {
        while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            if($this->mode!=0 || $row['presente']==1) {
                array_push($data, array(
                    'theme_title'=>$row['theme_title'],
                    'selgroup_id'=>$row['selgroup_id'],
                    'layer_id'=>$row['layer_id'],
                    'presente'=>$row['presente'],
                    'layer_name'=>$row['layer_name'],
                    'layergroup_name'=>$row['layergroup_name']
                ));
            }
        }
    } else {
        $msg="Nessun Livello Interrogabile definito nel Gruppo di selezione";
    }
} catch(Exception $e) {
    $msg="<b style=\"color:red\">Errore</b> ".$e->getMessage();
}


$btn[]="\n\t<input type=\"submit\" name=\"azione\" class=\"hexfield\" style=\"margin-right:5px;margin-left:5px;\" value=\"Annulla\">";
$btn[]="<input type=\"submit\" name=\"azione\" class=\"hexfield\" style=\"margin-right:5px;margin-left:5px;\" value=\"Salva\">";
$btn[]="<input type=\"button\" name=\"azione\" class=\"hexfield\" style=\"width:130px;margin-right:5px;margin-left:5px;\" value=\"Seleziona Tutti\" onclick=\"javascript:selectAll(this,'qt');\">\n";
$button="modifica";
?>
