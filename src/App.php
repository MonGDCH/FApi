<?php

namespace FApi;

use Closure;
use FApi\Url;
use FApi\Hook;
use FApi\Error;
use FApi\Route;
use FApi\Request;
use FApi\Response;
use mon\util\Instance;
use mon\util\Container;
use FastRoute\Dispatcher;
use FApi\exception\JumpException;
use FApi\exception\RouteException;

/**
 * Fapi核心驱动类
 * 
 * @property Route $route   路由类实例
 * @property Request $request   请求类实例
 * @property Url $url   URL类实例
 * 
 * @author Mon <985558837@qq.com>
 * @version 2.0.0 2019-12-21
 * @version 2.0.2 2020-07-17    增强注解
 */
class App
{
    use Instance;

    /**
     * 版本号
     * 
     * @var string
     */
    const VERSION = '2.1.2';

    /**
     * 启动模式
     *
     * @var boolean
     */
    protected $debug = true;

    /**
     * App名称
     *
     * @var string
     */
    protected $name = 'MonApi';

    /**
     * 服务容器实例
     *
     * @var Container
     */
    protected $container;

    /**
     * 路由返回结果集
     *
     * @var mixed
     */
    protected $result;

    /**
     * 路由回调
     *
     * @var array
     */
    protected $callback;

    /**
     * 路由请求参数
     *
     * @var array
     */
    protected $vars = [];

    /**
     * 路由回调中间件
     *
     * @var array
     */
    protected $befor;

    /**
     * 路由回调控制器
     *
     * @var mixed
     */
    protected $controller;

    /**
     * 路由回调后置件
     *
     * @var mixed
     */
    protected $after;

    /**
     * 构造方法
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
            // 注册URL类实例
            'url'       => Url::instance()
        ]);
    }

    /**
     * 魔术属性支持
     *
     * @return mixed 服务容器获取的实例
     */
    public function __get($abstract)
    {
        return $this->container->make($abstract);
    }

    /**
     * 初始化应用
     *
     * @param boolean $debug 是否为调试模式
     * @return App
     */
    public function init($debug = true)
    {
        // 设置运行模式
        $this->debug = boolval($debug);
        // 注册异常处理
        Error::register($this->debug);
        // 应用初始化钩子
        Hook::trigger('bootstrap');

        return $this;
    }

    /**
     * 判断当前是否为调试模式
     *
     * @return boolean
     */
    public function debug()
    {
        return $this->debug;
    }

    /**
     * 获取或者设置App名称
     *
     * @param string $name
     * @return string
     */
    public function name($name = '')
    {
        if (!is_string($name) && !empty($name)) {
            $this->name = $name;
        }
        return $this->name;
    }

    /**
     * 注册服务
     *
     * @param  mixed $abstract 服务标识
     * @param  mixed $server   服务实例
     * @return App
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
     * @return App
     */
    public function definition($tags = [])
    {
        Hook::register($tags);
        return $this;
    }

    /**
     * 获取响应结果集
     *
     * @return mixed
     */
    public function getResult()
    {
        return $this->result;
    }

    /**
     * 设置响应结果集
     *
     * @param mixed $result 结果集
     * @return App
     */
    public function setResult($result)
    {
        $this->result = $result;
        return $this;
    }

    /**
     * 获取路由回调
     *
     * @return mixed
     */
    public function getCallback()
    {
        return $this->callback;
    }

    /**
     * 设置路由回调
     *
     * @param array $callback   回调信息
     * @return App
     */
    public function setCallback(array $callback)
    {
        $this->callback = $callback;
        return $this;
    }

    /**
     * 获取路由参数
     *
     * @return mixed
     */
    public function getVars()
    {
        return $this->vars;
    }

    /**
     * 设置路由参数
     *
     * @param array $vars   路由参数
     * @return App
     */
    public function setVars(array $vars)
    {
        $this->vars = $vars;;
        return $this;
    }

    /**
     * 获取前置中间件
     *
     * @return mixed
     */
    public function getBefor()
    {
        return $this->befor;
    }

    /**
     * 设置前置中间件
     *
     * @param array $befor  中间件
     * @return App
     */
    public function setBefor(array $befor)
    {
        $this->befor = $befor;
        return $this;
    }

    /**
     * 获取回调控制器
     *
     * @return mixed
     */
    public function getController()
    {
        return $this->controller;
    }

    /**
     * 设置回调控制器
     *
     * @param array $controller 控制器
     * @return App
     */
    public function setController($controller)
    {
        $this->controller = $controller;
        return $this;
    }

    /**
     * 获取后置中间件
     *
     * @return mixed
     */
    public function getAfter()
    {
        return $this->after;
    }

    /**
     * 设置后置中间件
     *
     * @param array $after  中间件
     * @return App
     */
    public function setAfter(array $after)
    {
        $this->after = $after;
        return $this;
    }

    /**
     * 执行应用
     *
     * @param string $method 请求类型
     * @param string $path 请求pathinfo
     * @throws RouteException
     * @return Response 响应结果集
     */
    public function run($method = null, $path = null)
    {
        $method = is_null($method) ? $this->request->method() : strtoupper($method);
        $path = is_null($path) ? $this->request->pathInfo() : $path;
        // 应用执行钩子
        Hook::trigger('run', ['method' => $method, 'path' => $path]);

        // 解析路由
        $callback = $this->route->dispatch($method, $path);
        switch ($callback[0]) {
                // 200 匹配请求
            case Dispatcher::FOUND:
                // 执行路由响应
                $result = $this->handler($callback[1], $callback[2]);
                // 返回响应类实例
                return $this->response($result);

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
                    $result = $this->handler($default[1], $default[2]);
                    // 返回响应类实例
                    return $this->response($result);
                }
                throw new RouteException("Route is not found", 404);

                // 不存在路由定义
            default:
                throw new RouteException("Route is not found", 404);
        }
    }

    /**
     * 获取响应结果集
     * 
     * @param  string $result 结果集
     * @return Response
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
     * @param  mixed  $callback 路由回调标志
     * @param  array  $vars     路由参数
     * @return mixed
     */
    protected function handler($callback, array $vars = [])
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
        Hook::trigger('beforAction', $this);
        try {
            // 执行中间件
            if ($this->befor) {
                // 存在中间件，执行中间件，绑定参数：路由请求参数和App实例
                $result = $this->kernel($this->befor, $this->vars);
                if ($result === true) {
                    $result = $this->callback();
                }
            } else {
                // 不存在中间件，执行控制器及后置件
                $result = $this->callback();
            }
        } catch (JumpException $e) {
            $result =  $e->getResponse();
        }

        // 回调结束后
        Hook::trigger('afterAction', $result);

        return $result;
    }

    /**
     * 执行中间件
     *
     * @param mixed $kernel         中间件
     * @param array|string $vars    参数
     * @return mixed
     */
    protected function kernel($kernel, $vars = [])
    {
        // 转为数组
        $kernel = !is_array($kernel) ? [$kernel] : $kernel;
        foreach ($kernel as $k => $v) {
            // 执行回调，不返回true，则结束执行，返回中间件的返回结果集
            $result = $this->exec($v, $vars);
            if ($result !== true) {
                return $result;
            }
        }

        return true;
    }

    /**
     * 执行业务回调
     *
     * @return mixed
     */
    protected function callback()
    {
        // 执行控制器
        $this->result = $this->container->invoke($this->controller, $this->vars);
        // 执行后置件
        if ($this->after) {
            $this->result = $this->kernel($this->after, $this->result);
        }

        return $this->result;
    }

    /**
     * 执行回调
     *
     * @param mixed  $kernel        回调对象
     * @param array|string $vars    参数
     * @return mixed
     */
    protected function exec($kernel, $vars = [])
    {
        if (is_string($kernel) || (is_object($kernel) && !($kernel instanceof Closure))) {
            $kernel = [$this->container->make($kernel), 'handler'];
        }

        return $this->container->invoke($kernel, [$vars, $this]);
    }
}
