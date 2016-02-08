<?php
require_once "../../config/config.php";

$layer=$this->parametri["layer"];
$data = array();

$db = GCApp::getDB();

$JOIN=($this->mode==0)?(" INNER JOIN "):(" LEFT JOIN ");
$sql="select X.*,Y.wms,Y.wfs,Y.wfst,case when coalesce(Y.groupname,'')='' then 0 else 1 end as presente from (select distinct groupname from ".DB_SCHEMA.".groups order by groupname) X LEFT JOIN (SELECT * FROM ".DB_SCHEMA.".layer_groups WHERE layer_id=:layer)  Y using (groupname)";

try {
    $stmt = $db->prepare($sql);
    $stmt->execute(array('layer'=>$layer));
    if($stmt->rowCount() > 0) {
        while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            if($this->mode!=0 || $row['presente']==1) {
                array_push($data, array(
                    'presente'=>$row['presente'],
                    'groupname'=>$row['groupname'],
                    'wms'=>$row['wms'],
                    'wfs'=>$row['wfs'],
                    'wfst'=>$row['wfst']
                ));
            }
        }
    } else {
        $data=Array();
        $msg="Nessun layer definito nel mapset";
    }
} catch(Exception $e) {
    $data=Array();
    $msg="<b style=\"color:red\">Errore</b>";
}

	
$btn[] = '<button name="azione" class="hexfield" type="submit" value="annulla">'.GCAuthor::t('button_cancel').'</button>';
$btn[] = '<button name="azione" class="hexfield" type="submit" value="salva">'.GCAuthor::t('button_save').'</button>';
$button="modifica";
