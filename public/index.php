<?php

use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// Support both standard Laravel layout and shared-hosting layouts that keep
// the app files under a sibling "private" directory.
$basePath = is_dir(__DIR__.'/../private')
    ? __DIR__.'/../private'
    : __DIR__.'/..';

// Determine if the application is in maintenance mode...
if (file_exists($maintenance = $basePath.'/storage/framework/maintenance.php')) {
    require $maintenance;
}

// Register the Composer autoloader...
require $basePath.'/vendor/autoload.php';

// Bootstrap Laravel and handle the request...
(require_once $basePath.'/bootstrap/app.php')
    ->handleRequest(Request::capture());
