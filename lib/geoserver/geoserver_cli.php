<?php
require_once __DIR__."/../../config/config.php";
if (!file_exists(__DIR__."/vendor/autoload.php")) {
    echo "Missing autoload. Execute composer install from " . __DIR__ . "\n";
    exit(1);
}
require_once __DIR__."/vendor/autoload.php";
include_once ROOT_PATH.'lib/ajax.class.php';

use GisClient\GeoServer\Author2Geoserver;
use GisClient\Logger\CommandLineLogger;

function usage()
{
    $filename = basename(__FILE__);
    echo "GisClient-Author for GeoServer command line utility\n";
    echo "Usage: \n";
    echo "  -p <project>      Name of the GC project\n";
    echo "  -m <mapset>       Name of the GC mapset\n";
    echo "  -t                Use a temporary workspace\n";
    echo "  -r                Remove data from geoserver only\n";
    echo "  -d                Debug mode\n";
    echo "\n";
    if (!defined('GEOSERVER_URL')) {
        echo "WARNING: geoserver not enabled. Check configuration GEOSERVER_URL parameter\n\n";
    } else {
        $params = Author2GeoServer::getParameters();
        echo "Current GeoServer settings:\n";
        echo "  URL:              {$params['url']}\n";
        echo "  User:             {$params['user']}\n";
        echo "  Password:         (not shown)\n";
    }
    exit(3);
}
if (count(debug_backtrace()) === 0 &&
    basename($argv[0]) == basename(__FILE__)) {

    if ($argc < 2 || $argv[1] == '--help') {
        usage();
    }
    $opt = 'p:m:trd';
    $options = getopt($opt);

    $project = isset($options['p']) ? $options['p'] : null;
    $mapset = isset($options['m']) ? $options['m'] : null;
    $temporary = isset($options['t']);
    $removeOnly = isset($options['r']);
    $debug = isset($options['d']);

    if (empty($project)) {
        echo "Missing project\n";
        exit(1);
    }

    $geoServerWrapper = new Author2Geoserver();
    $geoServerWrapper->setLogger(new CommandLineLogger());
    if ($removeOnly) {
        $geoServerWrapper->removeOnly($project, $mapset, !$temporary);
    } else {
        $geoServerWrapper->sync($project, $mapset, !$temporary);
    }
}