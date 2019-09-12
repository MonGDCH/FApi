<?php

namespace FApi;

use Closure;
use FApi\Hook;
use FApi\Error;
use FApi\Route;
use FApi\Request;
use FApi\Response;
use FApi\exception\RouteException;
use FApi\exception\JumpException;
use FastRoute\Dispatcher;
use mon\factory\Container;

class App
{
    /**
     * 对象单例
     *
     * @var [type]
     */
    protected static $instance;

    /**
     * 版本号
     */
    const VERSION = '1.3.1';

    /**
     * 启动模式
     *
     * @var [type]
     */
    protected $debug = true;

    /**
     * 服务容器实例
     *
     * @var [type]
     */
    protected $container;

    /**
     * 路由返回结果集
     *
     * @var [type]
     */
    protected $result;

    /**
     * 路由回调
     *
     * @var [type]
     */
    public $callback;

    /**
     * 路由请求参数
     *
     * @var array
     */
    public $vals = [];

    /**
     * 路由回调中间件
     *
     * @var [type]
     */
    public $befor;

    /**
     * 路由回调控制器
     *
     * @var [type]
     */
    public $controller;

    /**
     * 路由回调后置件
     *
     * @var [type]
     */
    public $after;

    /**
     * 构造方法
     *
     * @param array $config [description]
     */
    protected function __construct()
    {
        $this->container = Container::instance();
        // 注册服务
        $this->container->bind([
            // 注册请求类实例
            'request'   => Request::instance(),
            // 注册路由类实例
            'route'     => Route::instance(),
        ]);
    }

    /**
     * 获取实例
     *
     * @return [type]         [description]
     */
    public static function instance()
    {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * 魔术属性支持
     *
     * @return [type] [description]
     */
    public function __get($abstract)
    {
        return $this->container->make($abstract);
    }

    /**
     * 初始化应用
     *
     * @param boolean $debug    是否为调试模式
     * @return void
     */
    public function init($debug = true)
    {
        // 设置运行模式
        $this->debug = boolval($debug);
        // 注册异常处理
        Error::register($this->debug);
        // 应用初始化钩子
        Hook::listen('bootstrap');

        return $this;
    }

    /**
     * 判断当前是否为调试模式
     *
     * @return [type]         [description]
     */
    public function debug()
    {
        return $this->debug;
    }

    /**
     * 注册服务
     *
     * @param  [type] $abstract 服务标识
     * @param  [type] $server   服务实例
     * @return [type]           [description]
     */
    public function singleton($abstract, $server = null)
    {
        $this->container->bind($abstract, $server);
        return $this;
    }

    /**
     * 定义应用钩子
     *
     * @param array $tags   钩子标识
     * @return void
     */
    public function definition($tags = [])
    {
        Hook::register($tags);
        return $this;
    }

    /**
     * 获取响应结果集
     *
     * @return [type] [description]
     */
    public function getResult()
    {
        return $this->result;
    }

    /**
     * 执行应用
     *
     * @return [type] [description]
     */
    public function run()
    {
        // 应用执行钩子
        Hook::listen('run');

        $request = $this->container->make('request');
        $path = $request->pathInfo();
        $method = $request->method();

        // 解析路由
        $callback = $this->route->dispatch($method, $path);
        switch ($callback[0]) {
                // 200 匹配请求
            case Dispatcher::FOUND:
                // 执行路由响应
                $this->result = $this->runHandler($callback[1], $callback[2]);
                // 返回响应类实例
                return $this->response($this->result);

                // 405 Method Not Allowed  方法不允许
            case Dispatcher::METHOD_NOT_ALLOWED:
                // 允许调用的请求类型
                $allowedMethods = $callback[1];
                throw (new RouteException("Route method is not found", 403))->set($allowedMethods);

                // 404 Not Found 没找到对应的方法
            case Dispatcher::NOT_FOUND:
                $default = $this->container->route->dispatch($method, '*');
                if ($default[0] === Dispatcher::FOUND) {
                    // 存在自定义的默认处理路由
                    $this->result = $this->runHandler($default[1], $default[2]);
                    // 返回响应类实例
                    return $this->response($this->result);
                }
                throw new RouteException("Route is not found", 404);

                // 不存在路由定义
            default:
                throw new RouteException("Route is not found", 404);
        }
    }

    /**
     * 执行控制器及后置件
     * 
     * @return function [description]
     */
    public function next()
    {
        // 执行控制器
        $result = $this->container->invoke($this->controller, $this->vars);
        // 执行后置件
        if ($this->after) {
            $result = $this->runKernel($this->after, $result);
        }

        return $result;
    }

    /**
     * 获取响应结果集
     * 
     * @param  string $result 结果集
     * @return [type]         [description]
     */
    public function response($result = '')
    {
        if ($result instanceof Response) {
            $response = $result;
        } elseif (!is_null($result)) {
            $response = Response::create($result);
        } else {
            $response = Response::create();
        }

        return $response;
    }

    /**
     * 执行路由
     *
     * @param  [type] $callback 路由回调标志
     * @param  array  $vars     路由参数
     * @return [type]           [description]
     */
    protected function runHandler($callback, array $vars = [])
    {
        // 获得处理函数
        $this->callback = $callback;
        // 获取请求参数
        $this->vars = $vars;
        // 获取回调中间件
        $this->befor = $this->callback['befor'];
        // 获取回调控制器
        $this->controller = $this->callback['callback'];
        // 获取回调后置件
        $this->after = $this->callback['after'];

        // 回调执行前
        Hook::listen('action_befor', $this);

        try {
            // 执行中间件
            if ($this->befor) {
                // 存在中间件，执行中间件，绑定参数：路由请求参数和App实例
                $result = $this->runKernel($this->befor, $this->vars);
            } else {
                // 不存在中间件，执行控制器及后置件
                $result = $this->next();
            }
        } catch (JumpException $e) {
            $result =  $e->getResponse();
        }

        // 回调结束后
        Hook::listen('action_after', $result);

        return $result;
    }

    /**
     * 执行请求组件
     *
     * @param  [type] $kernel 中间件
     * @param  array  $vals   参数
     * @return [type]         [description]
     */
    protected function runKernel($kernel, $vars = [])
    {
        if (is_string($kernel) || (is_object($kernel) && !($kernel instanceof Closure))) {
            $kernel = [$this->container->make($kernel), 'handler'];
        }

        return $this->container->invoke($kernel, [$vars, $this]);
    }
}
