<?php

use FApi\App;
use FApi\Route;

require __DIR__ . '/../vendor/autoload.php';

App::instance()->init();

Route::instance()->get('/', function () {
    return 'Hello Mon';
});


Route::instance()->get('/test',  function () {
    throw new Exception('Test Error', 66);
});


App::instance()->run()->send();