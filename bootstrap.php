<?php

$config = __DIR__ . '/config/config.php';
if (!file_exists($config)) {
    die ("Manca setup");
}
require_once($config);
$loader = require_once(__DIR__ . '/vendor/autoload.php');
