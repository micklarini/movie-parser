<?php

ini_set('display_errors', 1);
error_reporting(E_ALL & ~E_DEPRECATED);

setlocale(LC_ALL, 'ru_RU.utf8');

$cli = php_sapi_name() == 'cli';
if (!$cli) {
  ob_start('ob_gzhandler');
}

require dirname(__DIR__) . '/vendor/autoload.php';

$system = new \App\System();
$system->startUp($cli, $cli ? $argv[1]: (@$_SERVER['REDIRECT_URL'] ?: $_SERVER['REQUEST_URI']));
