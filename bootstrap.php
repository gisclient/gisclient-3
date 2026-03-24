<?php

use Symfony\Component\Dotenv\Dotenv;

$loader = require_once(__DIR__ . '/vendor/autoload.php');

// Load cached env vars if the .env.local.php file exists
// Run "composer dump-env prod" to create it (requires symfony/flex >=1.2)
if (!class_exists(Dotenv::class)) {
    throw new RuntimeException('Please run "composer require symfony/dotenv" to load the ".env" files configuring the application.');
} elseif (!getenv('AUTHOR_PUBLIC_URL')) {
    // load all the .env files
    (new Dotenv())->load(__DIR__ . '/.env');
}

// Load local overrides — for CLI usage; Docker loads this via env_file in compose.yaml
if (file_exists($localEnvFile = __DIR__ . '/.env.local')) {
    $dotenv = new Dotenv();
    $dotenv->populate($dotenv->parse(file_get_contents($localEnvFile), $localEnvFile));
}

if (($sentryDsn = getenv('SENTRY_DSN')) && function_exists('Sentry\init')) {
    $sentryRelease = null;
    $versionFile = __DIR__ . '/version.txt';
    if (is_readable($versionFile)) {
        // Format: {version}-{build}-{sha}, e.g. "3.6.4-42-abc123..."
        // Sentry release: {version}-{build}, e.g. "3.6.4-42"
        $parts = explode('-', trim(file_get_contents($versionFile)), 3);
        if (count($parts) >= 2) {
            $sentryRelease = $parts[0] . '-' . $parts[1];
        }
    }
    \Sentry\init([
        'dsn' => $sentryDsn,
        'environment' => getenv('APP_ENV') ?: 'prod',
        'release' => $sentryRelease,
    ]);
}

require_once(__DIR__ . '/config/config.php');
$container = require_once(__DIR__ . '/container.php');
