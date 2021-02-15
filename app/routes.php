<?php
use App\Controllers\HelloController;
use App\Controllers\SpotifyController;

$app->get('/hello/{name}', 'HelloController:home');
$app->get('/api/v1/albums', 'SpotifyController:get');
