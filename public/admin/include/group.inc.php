<?php

require_once __DIR__ . '/../../../bootstrap.php';

$db = \GCApp::getDB();

$groupname = $this->parametri["groups"] ?? [];

if (!empty($groupname)) {
    if (!isset($data) || !is_array($data)) {
        $data = [];
    }
        
    $sql = '
            SELECT groupname, description FROM ' . DB_SCHEMA . '.groups
            WHERE groupname=:group
        ';
    $stmt = $db->prepare($sql);
    $stmt->execute([
        'group' => $groupname
    ]);
        
    array_push($data, $stmt->fetch(\PDO::FETCH_ASSOC));
}
if (empty($data)) {
    $msg = "Nessun Utente definito";
}


$btn[] = '<button name="azione" class="hexfield" type="submit" value="annulla">' . GCAuthor::t('button_cancel') . '</button>';
$btn[] = '<button name="azione" class="hexfield" type="submit" value="salva">' . GCAuthor::t('button_save') . '</button>';
$button = ($this->currentMode == 'view') ? ("modifica") : ("nuovo");
