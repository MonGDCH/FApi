<?php
/**
 * 配置类DEMO
 */
require '../vendor/autoload.php';

$app = \FApi\App::instance();

// 通过配置对象实例获取
$class =  \FApi\Config::instance();
// 通过App实例获取
$class = $app->config;
// 注册过APP实例后可以通过容器对象获取
$class = \FApi\Container::get('config');

$config = [
	'debug'		=> true,
	'version'	=> \FApi\App::VERSION,
	'Auth'		=> 'Mon',
	'Email'		=> '985558837@qq.com'
];

// 批量注册配置
$res = $class->register($config);

// 动态设置节点配置
$res = $class->set('test.name', 'Hello Config');

// 加载配置文件, 注意，需要返回一个数据
// <?php
// return ['vvvv', 'aaa' => 'aaaa'];

// $res = $class->load('file_name.php', 'config_name');

// 获取配置
var_dump($class->get());