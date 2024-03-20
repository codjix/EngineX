<?php

session_start();

use HimaPro\Blade;
use HimaPro\Router;

include "../vendor/autoload.php";

// init blade (views & cache) dirs
Blade::init(__DIR__);

// init & run app
Router::init();
foreach (glob("app/*.php") as $source) include $source;
Router::run();
