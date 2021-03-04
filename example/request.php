<?php

/**
 * 请求类DEMO
 */
require '../vendor/autoload.php';

$app = \FApi\App::instance()->init();

$app->route->any('/', function () use ($app) {
    // 通过请求对象实例获取
    $class =  \FApi\Request::instance();
    // 通过App实例获取
    $class2 = $app->request;
    // 注册过APP实例后可以通过容器对象获取
    $class3 = \mon\util\Container::get('request');

    debug($class->get());
    debug($class2->post());
    debug($class2->input());
    debug($class3->server());
    debug($class3->header());
    debug($class->has('sign', 'post'));
    debug($class->getContentType());


    // 判断是否未GET请求
    var_dump($class->isGet());
    // 获取
    var_dump($class->get('name', 'Hello Mon'));
});

$app->run();