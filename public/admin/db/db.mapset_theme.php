<?php

$saveData = $_POST;
foreach($saveData['dati'] as $numRow => $dataRow) {
	if (strlen(trim($dataRow['rootpath'])) == 0 && strlen(trim($dataRow['mapset_theme_order'])) == 0) {
		unset($saveData['dati'][$numRow]);
	}
}
$save=new saveData($saveData);
$p=$save->performAction($p);
?>
