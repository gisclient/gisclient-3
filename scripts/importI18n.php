<?php
include __DIR__ .'/../config/config.php';

$project = 'vuz'; //inserire il nome di un progetto se ci sono più progetti

echo "\n\ninizio... \n\n";

set_time_limit ( 5*60 );

$db = GCApp::getDB();

if(!GCApp::tableExists($db, DB_SCHEMA, 'export_i18n')) die('non esiste export_i18n');

if(empty($project)) {
    $projects = $db->query('select project_name from '.DB_SCHEMA.'.project')->fetchAll(PDO::FETCH_ASSOC);
    if(count($projects) > 1) {
        echo "ATTENZIONE: se ci sono più progetti, bisogna impostare il progetto a mano e comunque si rischia di tradurre anche l'altro progetto\n\n";
        die();
    }
    $project = $projects[0]['project_name'];
}
echo "\n\n progetto: $project\n\n";

$sql = 'insert into '.DB_SCHEMA.'.localization (localization_id, project_name, i18nf_id, pkey_id, language_id, value) values (:localization_id, :project, :i18nfid, :pkey, :lang, :val)';
$insertStmt = $db->prepare($sql);

$sql = 'select value from '.DB_SCHEMA.'.localization where project_name=:project and i18nf_id=:i18nfid and pkey_id = :pkey and language_id = :lang';
$checkStmt = $db->prepare($sql);

//$traduzioni = $db->query('select * from '.DB_SCHEMA.'.export_i18n')->fetchAll(PDO::FETCH_ASSOC);
$traduzioni = $db->query('select * from '.DB_SCHEMA.".export_i18n where project_name = '{$project}'")->fetchAll(PDO::FETCH_ASSOC);

$n1 = 0;
$n2 = 0;

foreach($traduzioni as $row) {
    $pkey = GCApp::getTablePKey($db, DB_SCHEMA, $row['table_name']);
    
    $sql = 'select '.$pkey.' from '.DB_SCHEMA.'.'.$row['table_name'].' where '.$row['field_name'].' = :orig_val and '.$row['table_name'].'_id = :pkey_id' ;
    $stmt = $db->prepare($sql);
    $stmt->execute(array('orig_val'=>$row['original_value'], 'pkey_id' => $row['pkey_id']));
    while($trad = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $i18nfid = $db->query('select i18nf_id from '.DB_SCHEMA.".i18n_field where table_name='".$row['table_name']."' and field_name='".$row['field_name']."'")->fetchColumn(0);
        if(empty($i18nfid)) {
            echo 'manca '.$row['table_name'].' '.$row['field_name'].' in i18n_field, inserimento '.$row['original_value'].' - '.$row['value']." saltato \n";
            continue;
        }
        $localization_id = $db->query("select ".DB_SCHEMA.".new_pkey('localization', 'localization_id') ")->fetchColumn(0);
        
        $checkStmt->execute(array(
            'project'=>$project,
            'i18nfid'=>$i18nfid,
            'pkey'=>$trad[$pkey],
            'lang'=>$row['language_id']
        ));
        $check = $checkStmt->fetchAll(PDO::FETCH_ASSOC);
        if(count($check) > 1) {
            echo " casino... c'è già più di una traduzione per lo stesso record....\n\n";
            continue;
        } else if(count($check) == 1) {
            if($row['value'] != $check[0]['value']) {
                echo ' c\'è già una traduzione per '.$row['table_name'].' '.$row['field_name'].' id '.$row['pkey_id'].' ...';
                echo ' E SONO DIVERSE!!! | lang_1:['.$row['original_value'].']: trad1:['.$row['value'].'] != trad2['.$check[0]['value'].']';
                echo "\n\n";
            }
            continue;
        }
        
        $insertStmt->execute(array(
            'localization_id' => $localization_id,
            'project'=>$project,
            'i18nfid'=>$i18nfid,
            'pkey'=>$trad[$pkey],
            'lang'=>$row['language_id'],
            'val'=>$row['value']
        ));
        $n2++;
    }
    $n1++;
}

echo "\n\nsono state inserite $n2 traduzioni partendo da $n1 righe\n\n\n\n ho finito\n\n\n";
