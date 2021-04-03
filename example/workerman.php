<?php

use FApi\App;
use FApi\Route;
use Workerman\Worker;
use FApi\Response as apiResponse;
use Workerman\Protocols\Http\Response;

require_once '../vendor/autoload.php';


$worker = new Worker('http://0.0.0.0:8080');
$app = App::instance()->init();

Route::instance()->get('/', function () {
    return 'test';
});

Route::instance()->get('/{id}', function ($id) {
    return apiResponse::create(['code' => 1, 'msg' => 'id is ' . $id], 'json');
});


$worker->onMessage = function ($connection, $request) use ($app) {
    $method = $request->method();
    $path = $request->path();

    $response = $app->run($method, $path);
    $result = new Response($response->getCode(), $response->getHeader(), $response->getContent());
    $connection->send($result);
};

// 运行worker
Worker::runAll();
