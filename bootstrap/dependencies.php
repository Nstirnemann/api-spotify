<?php

use App\Controllers\SpotifyController;
use GuzzleHttp\Client;

$container = $app->getContainer();

/* Logger */
$container['logger'] = function($c) {
    $logger = new \Monolog\Logger('my_logger');
    $file_handler = new \Monolog\Handler\StreamHandler('../logs/app.log');
    $logger->pushHandler($file_handler);
    return $logger;
};

/* Client */
$container['client'] = function($c) {
    $client = new GuzzleHttp\Client;
    return $client;
};

$container['SpotifyController'] = function ($c) {
    $SpSettings = $c->get('settings')['spotify'];

    return new \App\Controllers\SpotifyController(
        $c['client'],
        $c['logger'],
        $SpSettings['client_id'],
        $SpSettings['client_secret']
    );
};

$container['HelloController'] = function ($container) {
    return new \App\Controllers\HelloController($container->get('settings'));
};

$container['errorHandler'] = function ($container) {
    return function ($request, $response, $exception) use ($container) {

        $data = [
            'code'    => $exception->getCode(),
            'message' => $exception->getMessage(),
        ];

        return $container['response']
            ->withStatus(500)
            ->withHeader('Content-Type', 'application/json')
            ->write(json_encode(['error' => $data]));
    };
};
