<?php
/**
 * 路由DEMO
 */
require '../vendor/autoload.php';

// 获取应用实例
$app = \FApi\App::instance()->init();

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
            'append'    => function($result, $app){
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

