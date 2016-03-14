<?php
include __DIR__ .'/../config/config.php';

$db = GCApp::getDB();

if(!GCApp::tableExists($db, DB_SCHEMA, 'export_i18n')) {
    $db->exec('
        create table '.DB_SCHEMA.'.export_i18n (
            exporti18n_id serial,
            table_name character varying,
            field_name character varying,
            project_name character varying,
            pkey_id character varying,
            language_id character varying,
            value text,
            original_value text,
            CONSTRAINT export_i18n_pkey PRIMARY KEY (exporti18n_id)
        );
    ');
} else {
    $db->exec('truncate table '.DB_SCHEMA.'.export_i18n');
}

$sql = 'insert into '.DB_SCHEMA.'.export_i18n (table_name, field_name, project_name, pkey_id, language_id, value, original_value) values (:table, :field, :project, :pkey, :lang, :val, :orig_val)';
$insertStmt = $db->prepare($sql);

$sql = "select i18n_field.table_name, i18n_field.field_name, project_name, pkey_id, language_id, value
from ".DB_SCHEMA.".localization inner join ".DB_SCHEMA.".i18n_field
using(i18nf_id)
where value != ''
";
//evt. aggiungere filtri dopo la where sopra

$export = array();
foreach($db->query($sql, PDO::FETCH_ASSOC) as $row) {
    $pkey = GCApp::getTablePKey($db, DB_SCHEMA, $row['table_name']);
    $sql = 'select '.$row['field_name'].' from '.DB_SCHEMA.'.'.$row['table_name'].' where '.$pkey.' = :val';
    $stmt = $db->prepare($sql);
    $stmt->execute(array('val'=>$row['pkey_id']));
    $result = $stmt->fetchColumn(0);
    if(!empty($result)) {
        $insertStmt->execute(array(
            'table'=>$row['table_name'],
            'field'=>$row['field_name'],
            'project'=>$row['project_name'],
            'pkey'=>$row['pkey_id'],
            'lang'=>$row['language_id'],
            'val'=>trim($row['value']),
            'orig_val'=>$result
        ));
    }
}
