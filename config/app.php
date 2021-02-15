<?php

return [
    'settings' => [
        'displayErrorDetails' => true,
        'addContentLengthHeader' => false,

        'spotify' => [
            'client_id' => '1202313a89ba4cc390d095749a0750cc',
            'client_secret' => '392b74b35e0644cd9a2d27c16255f199',
        ],
    ],
        'logger' => [
            'name' => 'slim-app',
            'level' => Monolog\Logger::DEBUG,
            'path' => __DIR__ . '/../logs/app.log',
        ],
];


 

