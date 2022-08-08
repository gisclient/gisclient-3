<?php

if (!defined('DEBUG_DIR')) {
    if (defined('ROOT_PATH')) {
        define('DEBUG_DIR', ROOT_PATH.'config/debug/');
    } else {
        define('DEBUG_DIR', __DIR__.'/../config/debug/');
    }
}

function print_debug($t = "", $db = null, $file = null)
{
    if (DEBUG!=1) {
        return;
    }
    if (!defined("DEBUG_DIR")) {
        define("DEBUG_DIR", './');
    } elseif (!is_dir(DEBUG_DIR)) {
        mkdir(DEBUG_DIR);
    }
    $data=date('j-m-y');
    $ora=date("H:i:s");
    if (!$file) {
        $nomefile = DEBUG_DIR."standard.debug";
    } else {
                $nomefile=DEBUG_DIR.$file.".debug";
    }
    $size = (file_exists($nomefile)) ? filesize($nomefile) : 0;
    $f = ($size>100000) ? (fopen($nomefile, "w+")) : (fopen($nomefile, "a+"));

    if (!$f) {
        die("<p>Impossibile aprire il file $nomefile</p>");
    }

    if (is_array($t)||is_object($t)) {
        ob_start();
        print_r($t);
        $out = ob_get_contents();
        ob_end_clean();
        if (!fwrite($f, "\n$data\t$ora\t --- STAMPA DI UN ARRAY ---\n\t$out")) {
            echo "<p>Impossibile scrivere sul file $nomefile </p>";
        }
        fclose($f);
    } elseif (!fwrite($f, "\n$data\t$ora\n\t".$t)) {
        echo "<p>Impossibile scrivere sul file $nomefile </p>";
    } else {
        fclose($f);
    }
}

//FUNZIONE CHE CERCA RICORSIVAMENTE UN TESTO NEI FILE DI UNA DIRECTORY

function trova_testo($testo, $dirname)
{
    $ast=str_repeat("*", 10);
    ob_start();
    echo "\n$ast\tRicerca di $testo nei File della Directory $dirname\t$ast\n";
    $ris = [];
    if ($dir = @opendir($dirname)) {
        while (($file = readdir($dir)) !== false) {
            if (!is_dir($file)) {
                $filename=$dirname."/".$file;
                $f=fopen($filename, "r+");
                if ($f) {
                    $text=fread($f, filesize($filename));
                    if (strpos(strtolower($text), $testo)) {
                        $ris[dirname($file)][]="Trovato in $file";
                    }
                    fclose($f);
                } else {
                    trova_testo($testo, $dirname."/".$file);
                }
            } elseif ($file!="." and $file!="..") {
                trova_testo($testo, $dirname."/".$file);
            }
        }
        closedir($dir);
    } else {
        $ris[$dirname]="$dirname non è una directory";
    }
    print_r($ris);
    echo "\n$ast$ast FINE RICERCA TESTO IN $dirname $ast$ast\n";
    $output=ob_get_contents();
    print_debug($output, "", "trova_testo");
}

function exec_command($cmd)
{
    $ast=str_repeat("*", 10);
    ob_start();
    system($cmd, $out);
    $ris=ob_get_contents();
    ob_end_clean();
    print_debug("$ast\t ESECUZIONE COMANDO $cmd con RETURN CODE $out\t$ast\n");
    print_debug("$ast$ast\tRISULTATO EXEC\t$ast$ast\n$ris\n$ast$ast FINE ESECUZIONE COMANDO $ast$ast\n");
}

function print_array($arr)
{
    echo "<pre>";
    print_r($arr);
    echo "</pre>";
}
