<?php

function connInfofromPath($sPath)
{
    $pathInfo = explode("/", $sPath);
    if (!empty(MAP_USER)) {
        $mapUser = MAP_USER;
        $mapPwd = MAP_PWD;
    } else {
        $mapUser = DB_USER;
        $mapPwd = DB_PWD;
    }

    // Il nome del database del deployment ha la precedenza su quello eventualmente
    // scritto in catalog_path: un catalogo copiato da un altro ambiente porterebbe
    // con se' il database sbagliato (es. 'app' su un'installazione staging).
    // Non tocchiamo l'host: il mapfile resta sul primario e il routing verso la
    // replica avviene a runtime, per richiesta (MapDatabaseRouter).
    $dbName = (defined('MAP_DB_NAME') && MAP_DB_NAME !== '') ? MAP_DB_NAME : DB_NAME;
    $pinDbName = defined('MAP_DB_ROUTING') && MAP_DB_ROUTING && getenv('MAP_DB_NAME') !== false;

    if (count($pathInfo) == 1) {//Mancano le informazioni di connessione, ho solo lo schema e il db � quello del gisclient
        $connString = "user=" . $mapUser . " password=" . $mapPwd . " dbname=" . $dbName . " host=" . DB_HOST . " port=" . DB_PORT;
        $datalayerSchema = $pathInfo[0];
    } else {//Abbiamo db e schema
        $datalayerSchema = $pathInfo[1];
        $connInfo = explode(" ", $pathInfo[0]);
        if (count($connInfo) == 1) { //abbiamo il nome del db
            $connString = "user=" . $mapUser . " password=" . $mapPwd . " dbname=" . ($pinDbName ? $dbName : $connInfo[0]) . " host=" . DB_HOST . " port=" . DB_PORT;
        } else { //abbiamo la stringa di connessione
            $connString = $pinDbName
                ? \GisClient\MapServer\Connection\ConnectionString::withEndpoint($pathInfo[0], null, $dbName)
                : $pathInfo[0];
        }
    }
    return [$connString, $datalayerSchema];
}

function connAdminInfofromPath($sPath)
{
    if (!isset($sPath)) {
        return;
    }
    $pathInfo = explode("/", $sPath);
    $datalayerSchema = $pathInfo[1];
    $connInfo = explode(" ", $pathInfo[0]);

    if (count($connInfo) == 1) { //abbiamo il nome del db
        $connString = "user=" . DB_USER . " password=" . DB_PWD . " dbname=" . $connInfo[0] . " host=" . DB_HOST . " port=" . DB_PORT;
    } else { //abbiamo la stringa di connessione
        $connString = $pathInfo[0];
    }

    return [$connString, $datalayerSchema];
}

function setDBPermission($db, $sk, $usr, $type, $mode, $table = '')
{
    if ($type == 'EXECUTE') {
        $sql = "select specific_name,routine_name from information_schema.routines where routine_schema='$sk'";
        $result = pg_query($db, $sql);
        if (!$result) {
            echo "<p><b style=\"color:red\">Errore nella query:<br>$sql</b></p>";
        }

        $ris = pg_fetch_all($result);
        for ($i = 0; $i < count($ris); $i++) {
            // vecchia riga modificata come sotto
            //$sql="select udt_name from information_schema.parameters where specific_name='".$ris[$i]["specific_name"]."' and specific_schema='$sk' order by ordinal_position";
            $sql = "select udt_schema,udt_name from information_schema.parameters where specific_name='" . $ris[$i]["specific_name"] . "' and parameter_mode='IN' and specific_schema='$sk' order by ordinal_position";
            $fld = [];
            $result = pg_query($db, $sql);
            if (!$result) {
                echo "<p><b style=\"color:red\">Errore nella query:<br>$sql</b></p>";
            }
            $flds = pg_fetch_all($result);
            //for($j=0;$j<count($flds);$j++) $fld[]=$flds[$j]["udt_name"]; vecchia istruzione
            //inizio modifiche carlio
            if (!$flds) {
                continue;
            }
            for ($j = 0; $j < count($flds); $j++) {
                if (($flds[$j]["udt_schema"] == 'pg_catalog') || ($flds[$j]["udt_schema"] == 'public')) {
                    $fld[] = $flds[$j]["udt_name"];
                } else {
                    $fld[] = $flds[$j]["udt_schema"] . "." . $flds[$j]["udt_name"];
                }
            }
            // fine modifiche carlio
            $prm = implode(',', $fld);


            if ($ris[$i]["routine_name"]) {
                $fName = $sk . '.' . $ris[$i]["routine_name"] . "($prm)";
                $sql = ($mode == 'GRANT') ? ("GRANT EXECUTE ON FUNCTION $fName TO $usr") : ("REVOKE EXECUTE ON FUNCTION $fName FROM $usr");
                $result = pg_query($db, $sql);
                if (!$result) {
                    echo "<p><b style=\"color:red\">Errore nella query:<br>$sql</b></p>";
                }
            }
        }
    } else {
        $sql = ($mode == 'GRANT') ? ("GRANT USAGE ON SCHEMA $sk TO $usr;") : ("REVOKE USAGE ON SCHEMA $sk FROM $usr;");
        if (!$table) {
            $result = pg_query($db, $sql);
            if (!$result) {
                echo "<p><b style=\"color:red\">Errore nella query:<br>$sql</b></p>";
            }
        } else {
            $result = 1;
        }
        if ($result) {
            $filter = ($sk == 'public') ? ("and table_name IN ('geometry_columns','spatial_ref_sys')") : (($table) ? ("and table_name ='$table'") : (""));
            $sql = "select '$sk.'||table_name as tb from information_schema.tables where table_schema='$sk' $filter order by table_name";
            $result = pg_query($db, $sql);
            if (!$result) {
                echo "<p><b style=\"color:red\">Errore nella query:<br>$sql</b></p>";
            }
            $ris = pg_fetch_all($result);
            for ($i = 0; $i < count($ris); $i++) {
                $sql = ($mode == 'GRANT') ? ("GRANT SELECT ON TABLE " . $ris[$i]["tb"] . " TO $usr;") : ("REVOKE SELECT ON TABLE " . $ris[$i]["tb"] . " FROM $usr;");
                $result = pg_query($db, $sql);
                if (!$result) {
                    echo "<p><b style=\"color:red\">Errore nella query:<br>$sql</b></p>";
                }
            }
        }
    }
}
