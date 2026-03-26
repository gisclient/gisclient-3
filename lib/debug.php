<?php

if (!defined('DEBUG_DIR')) {
    define('DEBUG_DIR', defined('ROOT_PATH') ? ROOT_PATH . 'config/debug/' : __DIR__ . '/../config/debug/');
}

function print_debug($t = "", $db = null, $file = null)
{
    if (DEBUG != 1) {
        return;
    }
    $ts = date('d-m-y H:i:s');
    $category = $file ?? 'debug';
    $msg = (is_array($t) || is_object($t)) ? print_r($t, true) : (string)$t;
    fwrite(fopen('php://stderr', 'w'), sprintf("\n%s [%s] %s\n", $ts, $category, $msg));

    if (function_exists('Sentry\addBreadcrumb')) {
        \Sentry\addBreadcrumb(new \Sentry\Breadcrumb(
            \Sentry\Breadcrumb::LEVEL_DEBUG,
            \Sentry\Breadcrumb::TYPE_DEFAULT,
            $category,
            $msg
        ));
    }
}

//FUNZIONE CHE CERCA RICORSIVAMENTE UN TESTO NEI FILE DI UNA DIRECTORY

function trova_testo($testo, $dirname)
{
    $ast = str_repeat("*", 10);
    ob_start();
    echo "\n$ast\tRicerca di $testo nei File della Directory $dirname\t$ast\n";
    $ris = [];
    if ($dir = @opendir($dirname)) {
        while (($file = readdir($dir)) !== false) {
            if (!is_dir($file)) {
                $filename = $dirname . "/" . $file;
                $f = fopen($filename, "r+");
                if ($f) {
                    $text = fread($f, filesize($filename));
                    if (strpos(strtolower($text), (string) $testo)) {
                        $ris[dirname($file)][] = "Trovato in $file";
                    }
                    fclose($f);
                } else {
                    trova_testo($testo, $dirname . "/" . $file);
                }
            } elseif ($file != "." and $file != "..") {
                trova_testo($testo, $dirname . "/" . $file);
            }
        }
        closedir($dir);
    } else {
        $ris[$dirname] = "$dirname non è una directory";
    }
    print_r($ris);
    echo "\n$ast$ast FINE RICERCA TESTO IN $dirname $ast$ast\n";
    $output = ob_get_contents();
    print_debug($output, "", "trova_testo");
}

function exec_command($cmd)
{
    $ast = str_repeat("*", 10);
    ob_start();
    system($cmd, $out);
    $ris = ob_get_contents();
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
