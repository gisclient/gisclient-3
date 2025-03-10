<?php

require_once __DIR__ . '/../../bootstrap.php';
include_once ADMIN_PATH . "lib/functions.php";
include_once ADMIN_PATH . "lib/gcFeature.class.php";
$azione = $_REQUEST["azione"];
$db = GCApp::getDB();
switch ($azione) {
    case "classify_save":
        include_once ADMIN_PATH . "lib/savedata.class.php";
        require_once ADMIN_PATH . "db/db.layer.php";
        return;
        break;
    case "classify":
        $_REQUEST["mode"] = '';
        $layerId = $_REQUEST['prm_layer'];
        $field = $_REQUEST['classField'];
        $startC = explode(' ', $_REQUEST["startCol"]);
        $endC = explode(' ', $_REQUEST["endCol"]);
        $totClass = $_REQUEST["nbins"];
        
        $feature = new gcFeature();
        $feature->initFeature($layerId);

        $fld = $feature->getFeatureField();
        
        foreach ($fld as $id => $f) {
            if ($field == $f["name"]) {
                $field = $f;
            }
        }
        
        $dataDb = GCApp::getDataDB($field['catalog_path']);
        
        $sql = "SELECT DISTINCT $field[name] as val FROM $field[schema].$field[table] ORDER BY 1;";
        
        $stmt = $dataDb->prepare($sql);
        $stmt->execute();
        $totClass = $stmt->rowCount();
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (!$totClass) {
            $delta_r = ($endC[0] - $startC[0]) / ($totClass - 1);
            $delta_g = ($endC[1] - $startC[1]) / ($totClass - 1);
            $delta_b = ($endC[2] - $startC[2]) / ($totClass - 1);

            for ($i = 0; $i < $totClass; $i++) {
                $ris = $results[$i];
                $colors = sprintf('%02X', $startC[0]) . sprintf('%02X', $startC[1]) . sprintf('%02X', $startC[2]);
                $res[] = [
                    "val" => $ris["val"],
                    "color" => $colors,
                    "name" => 'class_' . ($i + 1),
                    "title" => $ris["val"],
                    "condition" => "([$field[name]] = '$ris[val]')",
                    "legend_type" => 1,
                ];
                $startC[0] += $delta_r;
                $startC[1] += $delta_g;
                $startC[2] += $delta_b;
            }
        } else {
            $delta_r = ($endC[0] - $startC[0]) / ($totClass - 1);
            $delta_g = ($endC[1] - $startC[1]) / ($totClass - 1);
            $delta_b = ($endC[2] - $startC[2]) / ($totClass - 1);
            switch ($field["data_type"]) {
                case 1:     //CAMPO TESTO
                    for ($i = 0; $i < $totClass; $i++) {
                        $ris = $results[$i];
                            
                        $colors = sprintf('%02X', $startC[0]) . sprintf('%02X', $startC[1]) . sprintf('%02X', $startC[2]);
                        $res[] = [
                            "val" => "classe " . ($i + 1),
                            "color" => $colors,
                            "name" => "class_" . ($i + 1),
                            "title" => "class " . ($i + 1),
                            "condition" => "",
                            "legend_type" => 1,
                        ];
                        $startC[0] += $delta_r;
                        $startC[1] += $delta_g;
                        $startC[2] += $delta_b;
                    }
                    break;
                case 2:     //CAMPO NUMERICO
                case 3:     //CAMPO DATA
                    $sql = "SELECT max($field[name]) as maxVal,min($field[name]) as minVal,(max($field[name])-min($field[name]))/$totClass as delta FROM $field[schema].$field[table];";
                    $stmt = $dataDb->query($sql);
                    $ris = $stmt->fetch(PDO::FETCH_NUM);
                
                    $delta = $ris[2];
                    $minV = $ris[1];
                    $maxV = $ris[0];
                    $startV = $minV;
                    $delta_r = ($endC[0] - $startC[0]) / ($totClass - 1);
                    $delta_g = ($endC[1] - $startC[1]) / ($totClass - 1);
                    $delta_b = ($endC[2] - $startC[2]) / ($totClass - 1);
                    for ($i = 0; $i < $totClass; $i++) {
                        $colors = sprintf('%02X', $startC[0]) . sprintf('%02X', $startC[1]) . sprintf('%02X', $startC[2]);
                        $startC[0] += $delta_r;
                        $startC[1] += $delta_g;
                        $startC[2] += $delta_b;
                        if ($field["data_type"] == 2) {
                            $sql = "SELECT round($startV +($delta*($i+1)),2) as val";
                        } else {
                            $sql = "SELECT '\''||($startV::date +(($delta::varchar||' day')::interval*($i+1))::date)::varchar||'\'' as val";
                        }
                        
                        $stmt = $dataDb->query($sql);
                        $endV = $stmt->fetchColumn(0);
                        
                        $res[] = [
                            "val" => $startV . ' - ' . $endV,
                            "color" => $colors,
                            "title" => 'class ' . ($i + 1),
                            //$startV.' - '.$endV,
                            "condition" => "(([$field[name]] > $startV) AND ([$field[name]] < $endV))",
                            "name" => "classe " . ($i + 1),
                            "legend_type" => "1",
                        ];
                        $startV = $endV;
                    }
                    
                    
                    break;
                
                default:
                    break;
            }
        }

        header('Cache-Control: no-cache, must-revalidate');
        //header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
        header('Content-type: application/json');
        echo json_encode($res);
        return;

        break;
    case "request":
        $fk = $_REQUEST["parent_level"] . "_id";
        $table = $_REQUEST["level"];
        $id = $_REQUEST["id"];
        
        if (!GCApp::columnExists($db, DB_SCHEMA, $table, $fk)) {
            echo "{error:'Impossibile eseguire la query '}";
            break;
        }
        
        switch ($table) {
            case "layer":
                $fld = $table . "_id as id,trim(" . $table . "_name) as title";
                break;
            default:
                $fld = $table . "_id as id,trim(" . $table . "_title) as title";
                break;
        }
        $sql = "SELECT $fld FROM " . DB_SCHEMA . ".$table WHERE $fk=:val order by 2;";
        try {
            $stmt = $db->prepare($sql);
            $stmt->execute([
                'val' => $id,
            ]);
            $ris = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $opt = [];
            foreach ($ris as $v) {
                array_push($opt, [
                    'id' => $v['id'],
                    'name' => $v['title'],
                ]);
            }
            $res = json_encode($opt) . ",'$table'";
        } catch (Exception $e) {
            echo "{error:'Impossibile eseguire la query $sql'}";
        }
        break;
    default:
        echo "{error:'Invalid action'}";
        break;
}
header("Content-Type: text/plain; Charset=" . CHAR_SET);
echo $res;
