<?php
include_once "../../../config/config.php";
require_once ADMIN_PATH."lib/functions.php";
require_once ADMIN_PATH.'lib/gcFeature.class.php';
require_once ROOT_PATH."lib/i18n.php";
include_once ROOT_PATH.'lib/ajax.class.php';

$ajax = new GCAjax();
if(empty($_REQUEST['layer_id'])) {
    $ajax->error(array('type'=>'checkfields_errors', 'text'=>'Nessun layer specificato'));
    die();
}
$layerId = $_REQUEST['layer_id'];
$gcCheckFeature = new gcFeature();
$gcCheckFeature->initfeature($layerId);
$layerData = $gcCheckFeature->getFeatureData();
$checkDB = new GCDataDB($layerData['catalog_path']);
$stmt = $checkDB->db->prepare('SELECT column_name FROM information_schema.columns WHERE table_schema=:schema AND table_name=:table');
$stmt->execute(array('schema'=>$checkDB->schema,'table'=>$layerData['data']));
$tableColumns = $stmt->fetchAll(PDO::FETCH_COLUMN);
$fromClause = '';

$result = array();
$result[error_fields] = array();

foreach ($layerData['fields'] as $fieldId => $fieldData){
    if (empty($fieldData['formula']) && $fieldData['relation'] == 0) {
        $inTable = array_search($fieldData['field_name'],$tableColumns);
        if ($inTable === FALSE) {
            $result[error_fields][] = $fieldId;
        }
        else {
            array_splice($tableColumns, $inTable, 1);
        }
    }
    else {
        // **** Formula or relation field
        if ($fieldData["relation"] != 0) {//Il campo appartiene alla relazione e non alla tabella del layer
            $idRelation = $fieldData["relation"];
            if ( $layerData["relation"][$idRelation]['relation_type'] != 1)
                continue;
            $aliasTable = $layerData["relation"][$idRelation]["name"];
        }
        if (!empty($fieldData['formula'])) {
            $fieldName = $fieldData["formula"] . " AS " . $fieldData["field_name"];
        }
        else {
            $fieldName = $aliasTable . "." . $fieldData["field_name"];
        }
        if (empty($fieldName)) {
            continue;
        }

        // **** FROM Clause
        if (empty($fromClause)) {
            $datalayerTable = $layerData["data"];
            $datalayerSchema = $layerData["table_schema"];

            if ($aFeature["tileindex"]) //X TILERASTER
                continue;
            elseif (preg_match("|select (.+) from (.+)|i", $datalayerTable))//Definizione alias della tabella o vista pricipale (nel caso l'utente abbia definito una vista)  (da valutare se ha senso)
                $datalayerTable = "($datalayerTable) AS " . DATALAYER_ALIAS_TABLE;
            else
                $datalayerTable = $datalayerSchema . "." . $datalayerTable . " AS " . DATALAYER_ALIAS_TABLE;

            $fromClause = $datalayerTable;
            if ($aRelation = $layerData["relation"]) {
                foreach ($aRelation as $idrel => $rel) {
                    $relationAliasTable = NameReplace($rel["name"]);
                    if ($rel["relation_type"] == 2) {
                        continue;
                    }
                    $joinList = array();
                    for ($i = 0; $i < count($rel["join_field"]); $i++) {
                        $joinList[] = DATALAYER_ALIAS_TABLE . "." . $rel["join_field"][$i][0] . "=" . $relationAliasTable . "." . $rel["join_field"][$i][1];
                    }
                    $joinFields = implode(" AND ", $joinList);
                    $fromClause = "$fromClause left join " . $rel["table_schema"] . "." . $rel["table_name"] . " AS " . $relationAliasTable . " ON (" . $joinFields . ")";
                }
            }
        }

        try {
            if (FALSE === $checkDB->db->query("SELECT $fieldName FROM $fromClause LIMIT 1")) {
                $result[error_fields][] = $fieldId;
            }
        }
        catch (Exception $e) {
            $result[error_fields][] = $fieldId;
	    }
    }
}
$result[missing_fields] = $tableColumns;

$errors = GCError::get();
if(!empty($errors)) {
    $ajax->error(array('type'=>'checkfields_errors', 'text'=>prepareOutputForError($errors)));
} else {
    $ajax->success(array('data'=>$result));
}


?>
