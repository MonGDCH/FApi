# Fapi

#### 项目介绍
PHP Api友好快速路由框架。
性能大约是slim3.0的2倍，可以自行使用ab压测，如有疑问欢迎邮件@我

#### 版本说明

> v1.3.1

1. 优化代码
2. 修复升级1.3.0版本后部分方法不支持PHP5.6的BUG

> v1.3.0

1. 优化代码
2. 升级调整Jump跳转类为URL类，增加URL生成功能
3. 移除trait辅助类库

> v1.2.4

1. 优化代码，提高性能
2. 修改定义中间价和后置的索引名称件为befor, after
3. 增加Jump跳转类（引入原有Jump-trait）
4. 修复已知BUG

> v1.2.3

1. 优化代码，提高性能
2. 移除内置的Config配置模块
3. 移除内置的Container容器模块，使用mongdch/mon-container代替
4. 调整运行模式的定义，App::debug方法取消设置运行模式(只获取当前运行模式)，改为从App::init方法进行定义当前是否为开发者模式

> v1.2.2

1. 修复组别路由嵌套情况下，命名空间重置的问题
2. 优化代码

> v1.2.1

注意： 这是一个大的改动，不支持平滑升级

1. 移除自带的Log类
2. 增加Hook支持
3. 优化路由性能及路由缓存
4. 优化容器服务支持二维数组定义(一维键值作为二维键值前缀，以 _ 分割)
5. 增强错误提示信息
 
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
/**
 * 路由DEMO
 */
require '../vendor/autoload.php';

// 获取应用实例
$app = \FApi\App::instance()->init(true);

// 控制器调用演示
$app->route->group(['path' => '/class', 'namespace' => '\App\Controller\\'], function($r){
    $r->get('', 'Index@action');
});

// 匿名方法调用
$app->route->post(['path' => '/test', 'befor' => 'Middleware', 'after' => 'After'], function(){
    return 'This is Middleware and after demo!';
});

// 多种请求方式
$app->route->map(['GET', 'POST'], '/', function(){
	echo 'more query method';
})

// 默认路由, 没有对应路径的时候，调用 * 回调
$app->route->any('*', 'App\Controller\Index@index');


// 执行应用, 获取响应对象
$response = $app->run();

// 获取响应内容
$result = $app->getResult();

// 输出响应对象
$response->send();

```