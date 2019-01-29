<?php

use Symfony\Component\Dotenv\Dotenv;

$loader = require_once(__DIR__ . '/vendor/autoload.php');

// Load cached env vars if the .env.local.php file exists
// Run "composer dump-env prod" to create it (requires symfony/flex >=1.2)
if (!class_exists(Dotenv::class)) {
    throw new RuntimeException('Please run "composer require symfony/dotenv" to load the ".env" files configuring the application.');
} elseif (!getenv('AUTHOR_PUBLIC_URL')) {
    // load all the .env files
    (new Dotenv())->load(dirname(__DIR__).'/.env');
}

require_once(__DIR__ . '/config/config.php');
