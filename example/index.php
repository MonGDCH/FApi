<?php
/**
 * 路由DEMO
 */
require '../vendor/autoload.php';

// 获取应用实例
$app = \FApi\App::instance()->init(true);

// 函数调用演示
$app->route->group('', function ($route) {
    // 注册GET路由
    $route->get('/', function () {
        return 'Hello FApi, Version: ' . \FApi\App::VERSION;
    });

    // 注册组别路由
    $route->group(
        // 路由信息
        [
            // 路由前缀
            'path' => '/home',
            // 路由中间件，注意需要返回$app->next()才会往下执行控制器及后置件
            'befor' => function ($vars, $app) {
                $app->vars['befor'] = 'this is befor msg, ';
                return $app->next();
            },
            'after' => function ($res) {
                return $res . ', this is after msg';
            }
        ],
        // 路由回调
        function ($r) {
            // 重置中间件及后置件, 继承上级组别路由的路径, {/home/test}
            $r->get(
                [
                    // 补充请求后缀
                    'path' => '/test',
                    // 重置中间件
                    'befor' => function ($vars, $app) {
                        echo "The Middleware is reset here! \r\n<br/>";
                        return $app->next();
                    },
                    // 设置后置件
                    'after'    => function ($result, $app) {
                        return $result . "\r\n<br/> The after is set here!";
                    }
                ],
                // 回调
                function () {
                    return 'This is Middleware and after demo!';
                }
            );

            // 路由跳转
            $r->get('/baidu', function(\FApi\Url $url){
                $redirect = $url->build('http://www.baidu.com', ['test' => 1]);
                return $url->redirect($redirect);
            });

            // 路由参数, /home/{name}/{age}
            $r->get('/{name}[/{age:\d+}]', function ($name, $age = 18, $befor = '') {
                return $befor . "Hello, Mysql name is {$name}, I am {$age} years old.";
            });
        }
    );
});

// 控制器调用演示
$app->route->group(['path' => '/class', 'namespace' => '\App\Controller\\'], function ($r) {
    $r->get('', 'Index@action');
});


// 执行应用, 获取响应对象
$response = $app->run();

// 获取响应内容
// $result = $app->getResult();
// echo $result;

// 输出响应对象
$response->send();
