<?php

use FApi\Hook;

require '../vendor/autoload.php';

/**
 * 对象支持
 */
class TestHook
{
    public function handler($err)
    {
        var_dump($err);
        exit();
    }
}

// 增加钩子
\FApi\Hook::register([
    'bootstrap' => [function () {
        debug('bootstrap');
    }],
    'run'       => [function () {
        debug('run');
    }],
    'beforSend'      => [function ($data) {
        var_dump($data);
    }],
    'end'       => [function () {
        debug('end');
    }],
    'error'     => [TestHook::class],
]);

$app = \FApi\App::instance()->init();

Hook::listen('afterAction', function($data){
    var_dump('afterAction: ', $data);
});

$app->route->get('/', function ($id = 1) {
    return $id;
});

$app->run()->send();
