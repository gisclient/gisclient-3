<?php

//todo:  aggiungere anche la parte di codice delle formule!!!

include('../../config/config.php');

ini_set('display_errors', 'On');
error_reporting(E_ALL);
set_time_limit(0);

if(empty($_POST)) {
    echo '<html><head><title>Aggiornamento GisClient</title></head><body>';
    echo '<form method="post">';
    echo 'Migrazione database da GisClient 2 a GisClient 3<br><br>';
    echo 'Nome database '.DB_NAME.'<br><br>';
    echo 'Nome schema GisClient 2 <input type="text" name="old_schema"><br><br>';
    echo 'Schema GisClient 3: '.DB_SCHEMA.' (<input type="checkbox" name="drop_schema" value="yes">Drop if exists)<br><br>';
    echo '<input type="submit" name="submit" value="Esegui">';
    echo '</form></body></html>';
    die();
}

if(empty($_POST['old_schema'])) die('Specificare il nome dello schema GisClient 2');

echo '<pre>';
echo 'Migrazione schema da '.$_POST['old_schema'].' a '.DB_SCHEMA.' nel database '.DB_NAME."\n\n";

$workingDir = ROOT_PATH.'tmp/';
if(!is_writable($workingDir)) die('La directory '.$workingDir.' non è scrivibile');

$db = GCApp::getDB();
if(!GCApp::schemaExists($db, $_POST['old_schema'])) die('Lo schema '.$_POST['old_schema'].' non esiste');

if(!empty($_POST['drop_schema'])) {
    $db->exec('drop schema if exists '.DB_SCHEMA.' cascade');
}

$authCmd = 'export PGPASSWORD='.DB_PWD.' && export PGUSER='.DB_USER.' && ';
$unsetAuthCmd = ' && unset PGPASSWORD && unset PGUSER';
$host = DB_HOST;
$port = DB_PORT;
if($host == 'localhost') $host = '127.0.0.1'; //per come è configurato di solito pg_hba.conf

//DUMP
$exportFile = $workingDir.'gc21.sql';
$outputFile = $workingDir.'dump_output.txt';
$errorFile = $workingDir.'dump_errors.txt';
$cmd = $authCmd.'pg_dump -h '.$host.' -p '.$port.' -f '.$exportFile.' -n '.$_POST['old_schema'].' '.DB_NAME.' > '.$outputFile.' 2> '.$errorFile. ' ' .$unsetAuthCmd;
exec($cmd, $output, $return); //TODO: aggiungere output degli errori
if($return != 0) {
    die('Errore nel dump '."\n\n".file_get_contents($errorFile));
}

if(!file_exists($exportFile)) die('Errore nel dump dello schema');

//sostizione schema vecchio / schema nuovo sul file dump
$content = file_get_contents($exportFile);
$content = str_replace($_POST['old_schema'], DB_SCHEMA, $content);
file_put_contents($exportFile, $content);

//importazione su schema nuovo
$outputFile = $workingDir.'import_output.txt';
$errorFile = $workingDir.'import_errors.txt';
$cmd = $authCmd . 'psql -h '.$host.' -p '.$port.' -f '.$exportFile.' '.DB_NAME . ' > '.$outputFile.' 2> '.$errorFile. ' ' .$unsetAuthCmd;
exec($cmd, $output, $return);
if($return != 0) {
    die('Errore in importazione '."\n\n".file_get_contents($errorFile));
}

//upgrade33
$content = file_get_contents('upgrade33.sql');
$sql = 'set search_path to '.DB_SCHEMA.', public; '.$content;
$db->exec($sql);

//aggiornamento ms6
$outputFile = $workingDir.'ms6_output.txt';
$errorFile = $workingDir.'ms6_errors.txt';
$cmd = $authCmd . 'psql --quiet -h '.$host.' -p '.$port.' -f '.ROOT_PATH.'doc/aggiornamento_database_mapserver_6.sql '.DB_NAME . ' > '.$outputFile.' 2> '.$errorFile. ' ' .$unsetAuthCmd;
exec($cmd, $output, $return);
if($return != 0) {
    die('Errore in aggiornamento mapserver 6 '."\n\n".file_get_contents($errorFile));
}

/*
non so se è applicabile anche in questo caso dove dumpo tutto...
$sqlLayer = ($_REQUEST["layer"])? " and layer_id=" . $_REQUEST["layer"]:"";
*/
$sqlLayer = '';

//$db->beginTransaction();

$sql = "insert into ".DB_SCHEMA.".field (field_id,relation_id,field_name,field_header,fieldtype_id,searchtype_id,resultype_id,field_format,column_width,orderby_id,field_filter,datatype_id,field_order,default_op,layer_id,formula)
    select qtfield_id,qtfield.qtrelation_id, 
    case when (qtfield.qtfield_name LIKE '%(%' OR qtfield.qtfield_name LIKE '%::%' OR qtfield.qtfield_name LIKE '%||%') then 'formula_'||qtfield.qtfield_id else qtfield.qtfield_name end,
    field_header,fieldtype_id,searchtype_id,resultype_id,field_format,column_width,orderby_id,field_filter,datatype_id,qtfield_order,default_op,qt.layer_id,
    case when (qtfield.qtfield_name LIKE '%(%' OR qtfield.qtfield_name LIKE '%::%' OR qtfield.qtfield_name LIKE '%||%') then qtfield.qtfield_name else null end
    from ".DB_SCHEMA.".qtfield inner join ".DB_SCHEMA.".qt using(qt_id) where qt_id=(select min(qt_id) from ".DB_SCHEMA.".qt where layer_id=:layer)";

$insertQTField = $db->prepare($sql);

$sql = 'insert into '.DB_SCHEMA.'.field (field_id, field_name, field_header, fieldtype_id, searchtype_id, resultype_id, layer_id)
    values (:field_id, :field_name, :field_header, 1, 1, 4, :layer_id)';
$insertField = $db->prepare($sql);

$sql = 'select layer_id, data, layer_title, data_unique, data_filter, classitem, labelitem, labelsizeitem, catalog_path
    from '.DB_SCHEMA.'.layer inner join '.DB_SCHEMA.'.catalog using(catalog_id) 
    where connection_type = 6' . $sqlLayer;
//$sql .= ' limit 20';
$layers = $db->query($sql)->fetchAll(PDO::FETCH_ASSOC);

$sql = 'select expression, class_text, label_def, label_angle, label_color, label_outlinecolor, label_size, 
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

    try {
        $insertQTField->execute(array('layer'=>$layer['layer_id']));
    }
    catch (Exception $e) {
        echo 'Caught exception: ',  $e->getMessage(), "\n";
    }


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
                if(strpos($style['class_text'], $field) !== false) {
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

die('fatto!');

//$db->rollback();

