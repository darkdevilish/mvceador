<?php
use routeador\Route;
use mappeador\MySQLDatabase;

session_start();

defined('DS') ? null : define('DS', DIRECTORY_SEPARATOR);

require_once __DIR__.DS.'..'.DS.'config'.DS.'init.php';

Route::call_target_method(Route::submit());


MySQLDatabase::getInstance()->close_connection();