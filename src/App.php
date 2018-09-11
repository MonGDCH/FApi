<?php
namespace FApi;

use FApi\Hook;
use FApi\Error;
use FApi\Route;
use FApi\Config;
use FApi\Request;
use FApi\Response;
use FApi\Container;
use FApi\traits\Instance;
use FApi\exception\RouteException;
use FApi\exception\JumpException;
use FastRoute\Dispatcher;

class App
{
	use Instance;

	/**
	 * 版本号
	 */
	const VERSION = '1.1.2';

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
	public $middleware;

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
	public $append;

	/**
	 * 构造方法
	 *
	 * @param array $config [description]
	 */
	private function __construct($debug = false)
	{
		$this->container = Container::instance();
		// 注册服务
		$this->container->bind([
			// 注册配置类实例
			'config'	=> Config::instance(),
			// 注册请求类实例
			'request'	=> Request::instance(),
			// 注册路由类实例
			'route'		=> Route::instance(),
		]);
		// 配置运行模式
		$this->container->make('config')->set('debug', $debug !== false ? true : false);
		// 注册异常处理
		Error::register();

		// 应用初始化
		Hook::listen('bootstrap');
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
	 * 注册应用配置
	 *
	 * @return [type] [description]
	 */
	public function register($config = [])
	{
		$this->container->make('config')->register($config);
		return $this;
	}

	/**
	 * 注册服务
	 *
	 * @param  [type] $abstract [description]
	 * @param  [type] $server   [description]
	 * @return [type]           [description]
	 */
	public function singleton($abstract, $server = null)
	{
		$this->container->bind($abstract, $server);
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
		    	if($default[0] === Dispatcher::FOUND){
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
    	if($this->append){
    		$result = $this->runKernel($this->append, $result);
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
		if($result instanceof Response){
            $response = $result;
        }
        elseif(!is_null($result)){
            $response = Response::create($result);
        }
        else{
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
		// 获取路由映射表
		$table = $this->route->getTable();
		if(!isset($table[$this->callback])){
			throw new RouteException("Route callback is not found", 500);
		}
		$item = $table[$this->callback];

		// 获取回调中间件
		$this->middleware = $item['middleware'];
		// 获取回调控制器
		$this->controller = $item['callback'];
		// 获取回调后置件
		$this->append = $item['append'];

		// 回调执行前
		Hook::listen('action_befor', $this);

		try {
			// 执行中间件
			if($this->middleware){
				// 存在中间件，执行中间件，绑定参数：路由请求参数和App实例
				$result = $this->runKernel($this->middleware, $this->vars);
			}
			else{
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
	 * @param  [type] $kernel [description]
	 * @param  array  $vals   [description]
	 * @return [type]         [description]
	 */
	protected function runKernel($kernel, array $vars = [])
	{
		if(is_string($kernel) || is_object($kernel)){
			$kernel = [$this->container->make($kernel), 'handler'];
		}

		return $this->container->invoke($kernel, [$vars, $this]);
	}
}