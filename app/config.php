<?php

$config = [
    'settings' => [
        'displayErrorDetails' => true,
        'addContentLengthHeader' => false,
    ],
        'logger' => [
            'name' => 'slim-app',
            'level' => Monolog\Logger::DEBUG,
            'path' => __DIR__ . '/../logs/app.log',
        ],
];

$app = new \Slim\App($config);
$container = $app->getContainer();

/* Logger */
$container['logger'] = function($c) {
    $logger = new \Monolog\Logger('my_logger');
    $file_handler = new \Monolog\Handler\StreamHandler('../logs/app.log');
    $logger->pushHandler($file_handler);
    return $logger;
};

//$config['db']['host']   = 'localhost';
//$config['db']['user']   = 'user';
//$config['db']['pass']   = 'password';
//$config['db']['dbname'] = 'exampleapp';

 

