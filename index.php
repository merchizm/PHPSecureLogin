<?php

use Rocks\Database;

date_default_timezone_set('Europe/Istanbul');

require_once __DIR__ . '/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

$db = new Database();