<?php

require __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__.'/..');
$dotenv->load();

// Instantiate the app
$settings = require __DIR__ . '/../config/app.php';

$app = new \Slim\App($settings);

// Set up dependencies
require __DIR__ . '/dependencies.php';

// Register routes
require __DIR__ . '/../app/routes.php';
