# Fapi

#### 项目介绍
PHP Api友好快速路由框架。
性能大约是slim3.0的2倍，可以自行使用ab压测，如有疑问欢迎邮件@我的^_^

#### 版本说明
> v1.1.0

 1. 中间件、后置件以对象化实例调用时，统一调用handler方法
 2. 增加默认路由定义(路由定义路径为 * )
 3. 增加路由缓存功能，cache方法、register方法
 4. 优化App类
 5. 移除psr/log依赖，减少依赖

> v1.0.2-LTS

 1. 加入FApi\Config用于管理配置信息。
 2. 优化App注册对象容器及应用配置
 3. 优化路由性能和用法
 4. 优化App类对中间件、控制器、后置件的控制

#### 安装教程
```
composer require mongdch/fapi
```
或者
```
git clone https://github.com/MonGDCH/FApi.git
```
当然，使用git的话建议还是使用release的，当然给我提交issues，我也是非常欢迎的^_^。

#### 使用说明
```
<?php

require '../vendor/autoload.php';

// 获取应用实例，true表示开启调试模式
$app = \FApi\App::instance(true);

// 函数调用演示
$app->route->group('', function($route){
	// 注册GET路由
	$route->get('/', function(){
		return 'Hello FApi';
	});

	// 注册组别路由
	$route->group([
		// 路由前缀
		'prefix' => '/home', 
		// 路由中间件，注意需要返回$app->next()才会往下执行控制器及后置件
		'middleware' => function($vars, $app){
		if($vars['age'] > 100)
		{
			$app->vars['age'] = $vars['age'] - 100;
		}

		return $app->next();
	}], 
	function($r){
		// 重置中间件及后置件
		$r->get([
			// 补充请求后缀
			'prefix' => '/test',
			// 重置中间件
			'middleware' => function($vars, $app){
				echo "The Middleware is reset here! \r\n<br/>";
				return $app->next();
			},
			// 设置后置件
			'append'	=> function($result, $app){
				return $result . "\r\n<br/> The Append is set here!";
			}
		], 
		function(){
			return 'This is Middleware and Append demo!';
		});

		// 腹痛住别路由
		$r->get('/{name}[/{age:\d+}]', function($name, $age = 18){
			return "Hello, Mysql name is {$name}, I am {$age} years old.";
		});
	});
});

// 控制器调用演示
$app->route->group(['prefix' => '/class', 'namespace' => '\App\Controller\\'], function($r){
	$r->get('', 'Index@action');
});


// 执行应用, 获取响应对象
$response = $app->run();

// 获取响应内容
// $result = $app->getResult();
// echo $result;

// 输出响应对象
$response->send();


```