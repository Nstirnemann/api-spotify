<?php
use App\Controllers\HelloController;
use App\Controllers\SpotifyController;

$app->get('/api/v1/albums', 'SpotifyController:get');
