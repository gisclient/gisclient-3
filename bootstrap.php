<?php

require_once 'config/config.php';
$loader = require_once 'vendor/autoload.php';
$loader->addPsr4('GisClient\\', __DIR__.'/src/');
