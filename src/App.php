<?php
namespace FApi;

use FApi\Log;
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
	const VERSION = '1.0.2';

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
	private function __construct($debug = true)
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
			// 注册日志类实例
			'log'		=> Log::instance()
		]);
		// 配置运行模式
		$this->container->make('config')->set('debug', $debug !== false ? true : false);
		// 注册异常处理
		Error::register();
	}

	/**
	 * 魔术方法获取实例
	 *
	 * @param  [type] $abstract [description]
	 * @return [type]           [description]
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
		$request = $this->container->make('request');
		$path = $request->pathInfo();
		$method = $request->method();

		// 解析路由
		$callback = $this->container->make('route')->dispatch($method, $path);
		switch ($callback[0]) {
			// 200 匹配请求
		    case Dispatcher::FOUND: 
		    	// 获得处理函数
		        $this->callback = $callback[1];
		        // 获取请求参数
		        $this->vars = $callback[2];
		        // 获取路由映射表
		        $table = $this->container->make('route')->getTable();
		        if(!isset($table[$this->callback]))
		        {
		        	throw new RouteException("Route callback is not found", 500);
		        }
		        $item = $table[$this->callback];

		        // 获取回调中间件
		        $this->middleware = $item['middleware'];
		        // 获取回调控制器
		        $this->controller = $item['callback'];
		        // 获取回调后置件
		        $this->append = $item['append'];

		        try {
		        	// 是否执行回调控制器
		        	// 执行中间件
		        	if($this->middleware){
		        		// 存在中间件，执行中间件，绑定参数：路由请求参数和App实例
		        		$this->result = $this->container->invoke($this->middleware, [$this->vars, $this]);
		        	}else{
		        		// 不存在中间件，执行控制器及后置件
		        		$this->result = $this->next();
		        	}
		        	
		        } catch (JumpException $e) {
		        	$this->result =  $e->getResponse();
		        }
		        
		        if($this->result instanceof Response)
		        {
		            $response = $this->result;
		        }
		        elseif(!is_null($this->result))
		        {
		            $response = Response::create($this->result);
		        }
		        else
		        {
		            $response = Response::create();
		        }

		        // 返回响应类实例
		        return $response;

		    // 405 Method Not Allowed  方法不允许
			case Dispatcher::METHOD_NOT_ALLOWED:
				// 允许调用的请求类型
		        $allowedMethods = $callback[1];
		        throw (new RouteException("Route method is not found", 403))->set($allowedMethods);

		    // 404 Not Found 没找到对应的方法
		    case Dispatcher::NOT_FOUND:
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
    	if($this->append)
    	{
    		$result = $this->container->invoke($this->append, [$result, $this]);
    	}

    	return $result;
	}
}