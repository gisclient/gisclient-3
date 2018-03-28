<?php
require_once "../../config/config.php";
	
	$db = GCApp::getDB();
	$project=$this->parametri["project"];
	$JOIN=($this->mode==0)?(" INNER JOIN "):(" LEFT JOIN ");
    $data=Array();
	
	$sql="select groupname as group_name,case when coalesce(project_name,'')='' then 0 else 1 end as presente from (select 'Authenticated Users' as groupname UNION select distinct groupname from ".USER_SCHEMA.".groups) X $JOIN (select * from ".DB_SCHEMA.".project_groups where project_name=:project) Y on (groupname=group_name) order by group_name";
    
    try {
        $stmt = $db->prepare($sql);
        $stmt->execute(array('project'=>$project));
        if($stmt->rowCount() > 0) {
            while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                if($this->mode!=0 || $row['presente']==1) {
                    array_push($data, array(
                        'project_name'=>$project,
                        'group_name'=>$row['group_name'],
                        'presente'=>$row['presente']
                    ));
                }
            }
        } else {
            $msg="Nessun Gruppo definito nel portale";
        }
    } catch(Exception $e) {
        $msg="Nessun Gruppo definito nel portale";
    }
    
		
	$btn[]="\n\t<input type=\"submit\" name=\"azione\" class=\"hexfield\" style=\"margin-right:5px;margin-left:5px;\" value=\"Annulla\">";
	$btn[]="<input type=\"submit\" name=\"azione\" class=\"hexfield\" style=\"margin-right:5px;margin-left:5px;\" value=\"Salva\">";
	$btn[]="<input type=\"button\" name=\"azione\" class=\"hexfield\" style=\"width:130px;margin-right:5px;margin-left:5px;\" value=\"Seleziona Tutti\" onclick=\"javascript:selectAll(this,'group');\">\n";
	$button="modifica";
