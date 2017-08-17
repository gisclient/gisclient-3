<?php

require_once __DIR__ . '/../../bootstrap.php';

$project = $this->parametri["project"];
$mapset = $this->parametri["mapset"];
$db = GCApp::getDB();
$data = array();

$schema = DB_SCHEMA;
$JOIN = ($this->mode == 0)? (" INNER JOIN ") : (" LEFT JOIN ");
$sql = "SELECT X.*,Y.edit, case when coalesce(Y.groupname,'')='' then 0 else 1 end as presente FROM (SELECT distinct groupname FROM {$schema}.groups ORDER BY groupname) X LEFT JOIN (SELECT * FROM {$schema}.mapset_groups WHERE mapset_name=:mapset)  Y using (groupname)";

try {
    $stmt = $db->prepare($sql);
    $stmt->execute(array('mapset' => $mapset));
    if ($stmt->rowCount() > 0) {
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            if ($this->mode != 0 || $row['presente'] == 1) {
                array_push($data, array(
                    'presente' => $row['presente'],
                    'groupname' => $row['groupname'],
                    'edit' => $row['edit']
                ));
            }
        }
    } else {
        $data = array();
        $msg = "Nessun layer definito nel mapset";
    }
} catch (Exception $e) {
    $data = array();
    $msg = "<b style=\"color:red\">Errore</b>";
}
    
$btn[] = '<button name="azione" class="hexfield" type="submit" value="annulla">'.GCAuthor::t('button_cancel').'</button>';
$btn[] = '<button name="azione" class="hexfield" type="submit" value="salva">'.GCAuthor::t('button_save').'</button>';
$button = "modifica";
