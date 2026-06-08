<?php

/**
 * Hostinger shared hosting — document root: public_html/
 * Laravel app root: ../new-rentri-crm/ (fuori dal web root)
 *
 * Struttura:
 *   demolisci.backsoftware.it/
 *   ├── new-rentri-crm/   ← .env, vendor, app/ (NON accessibili via web)
 *   └── public_html/      ← solo file pubblici (questo file)
 */

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

$laravelRoot = dirname(__DIR__).'/new-rentri-crm';

if (file_exists($maintenance = $laravelRoot.'/storage/framework/maintenance.php')) {
    require $maintenance;
}

require $laravelRoot.'/vendor/autoload.php';

/** @var Application $app */
$app = require_once $laravelRoot.'/bootstrap/app.php';

$app->handleRequest(Request::capture());
