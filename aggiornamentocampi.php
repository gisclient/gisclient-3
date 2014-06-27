<?php

//todo:  aggiungere anche la parte di codice delle formule!!!

include('config/config.php');

echo '<pre>';

$sqlLayer = ($_REQUEST["layerid"])? " and layer_id=" . $_REQUEST["layerid"]:"";

$db = GCApp::getDB();
//$db->beginTransaction();

$sql = 'insert into '.DB_SCHEMA.'.field (field_id, field_name, field_header, fieldtype_id, searchtype_id, resultype_id, layer_id)
    values (:field_id, :field_name, :field_header, 1, 1, 4, :layer_id)';
$insertField = $db->prepare($sql);

$sql = 'select layer_id, data, layer_title, data_unique, data_filter, classitem, labelitem, labelsizeitem, catalog_path
    from '.DB_SCHEMA.'.layer inner join '.DB_SCHEMA.'.catalog using(catalog_id) 
    where connection_type = 6' . $sqlLayer;
//$sql .= ' limit 20';
$layers = $db->query($sql)->fetchAll(PDO::FETCH_ASSOC);

$sql = 'select expression, label_def, label_angle, label_color, label_outlinecolor, label_size, 
    color, outlinecolor, angle
    from '.DB_SCHEMA.'.style right join '.DB_SCHEMA.'.class using(class_id)
    where layer_id = :layer';

$getClasses = $db->prepare($sql);

$sql = 'select field_id from '.DB_SCHEMA.'.field where layer_id = :layer and field_name = :field_name';
$fieldExists = $db->prepare($sql);

foreach($layers as $layer) {
    $dataDb = GCApp::getDataDB($layer['catalog_path']);
    $schema = GCApp::getDataDBSchema($layer['catalog_path']);
    $tableFields = GCApp::getColumns($dataDb, $schema, $layer['data']);
    
    echo "\n\n".'layer '.$layer['data'].' - '.$layer['layer_title']."\n";
    
    foreach($tableFields as $field) {
        $used = false;
        
        //se il campo è contenuto nel tag FILTER
        $used = (strpos($layer['data_filter'], $field) !== false);
        
        //se il campo è un CLASSITEM, LABELITEM, LABELSIZEITEM
        if(!$used) {
            $used = in_array($field, array($layer['classitem'], $layer['labelitem'], $layer['labelsizeitem']));
        }
        
        //se il campo è usato nelle classi o negli stili del layer
        if(!$used) {
            $getClasses->execute(array('layer'=>$layer['layer_id']));

            
            $styles = $getClasses->fetchAll(PDO::FETCH_ASSOC);
            foreach($styles as $style) {
                if(strpos($style['expression'], $field) !== false) {
                    $used = true;
                    break;
                }
                if(strpos($style['label_angle'], $field) !== false) {
                    $used = true;
                    break;
                }
                if(strpos($style['label_size'], $field) !== false) {
                    $used = true;
                    break;
                }
                if(in_array($field, $style)) {
                    $used = true;
                    break;
                }
            }
        }
        
        //se è usato, lo inserisco se non è già nella teballa field
        if($used) {
            $fieldExists->execute(array(
                'layer'=>$layer['layer_id'],
                'field_name'=>$field
            ));
            $exists = $fieldExists->fetchColumn(0);
            
            if(empty($exists)) {
                $insertField->execute(array(
                    'field_id'=>GCApp::getNewPKey(DB_SCHEMA, DB_SCHEMA, 'field', 'field_id', 1),
                    'layer_id'=>$layer['layer_id'],
                    'field_name'=>$field,
                    'field_header'=>$field,
                ));
                echo 'campo '.$field.' di '.$layer['data'].' inserito'."\n";
            } else echo $field.' già inserito'."\n";
        } else echo $field.' non usato '."\n";
    }
    
}

//$db->rollback();

