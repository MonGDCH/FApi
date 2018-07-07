<?php
/**
 * 请求类DEMO
 */
require '../vendor/autoload.php';

$app = \FApi\App::instance();

// 通过请求对象实例获取
$class =  \FApi\Request::instance();
// 通过App实例获取
$class = $app->request;
// 注册过APP实例后可以通过容器对象获取
$class = \FApi\Container::get('request');

// 判断是否未GET请求
var_dump($class->isGet());

// 获取
var_dump($class->get('name', 'Hello Mon'));