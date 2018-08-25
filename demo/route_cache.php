<?php

/**
 * 加载composer
 */
require __DIR__ . '/../vendor/autoload.php';

/**
 * 注册应用实例
 */
$app = \FApi\App::instance(true);

$app->singleton([
	'midd'	=> Test::class
]);

class Test{
	public function handler($vars, $app)
	{
		// 获取路由标志定义
		// var_dump($app->route->getData());
		// 获取路由回调定义
		// var_export($app->route->getTable());
		return $app->next();
	}
}
class Demo{
	public function index(){
		return  999;
	}
}

/**
 * 定义路由
 */
// $app->route->group([
//     'namespace' => '',
// ], function ($router) {
//     // require __DIR__.'/../app/Http/router.php';
//     $router->get(['prefix' => '/', 'middleware' => 'midd'], 'Demo@index');

//     // 通配路由，无路由匹配情况下调用的路由
//     $router->any('*', function(){
//     	echo '*';
//     });
// });



// 缓存路由, path为空获取缓存内容

$cacheRouteFile =  __DIR__ . '/../demo/cacheRoute.php';

// $res = $app->route->cache();
// var_dump($res);

// $app->route->cache($cacheRouteFile);


// 从缓存中获取路由
$app->route->register(require($cacheRouteFile));



return $app->run()->send();