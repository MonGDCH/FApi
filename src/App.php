<?php
namespace FApi;

use FApi\Log;
use FApi\Error;
use FApi\Route;
use FApi\Request;
use FApi\Response;
use FApi\exception\RouteException;
use FApi\exception\JumpException;
use FastRoute\Dispatcher;

class App
{
	/**
	 * 版本号
	 */
	const VERSION = '1.0.1';

	/**
	 * 应用实例
	 *
	 * @var [type]
	 */
	protected static $instance;

	/**
	 * 应用执行模式
	 *
	 * @var boolean
	 */
	public $debug = false;

	/**
	 * 应用配置
	 *
	 * @var array
	 */
	protected $config =[];

	/**
	 * 路由实例
	 *
	 * @var [type]
	 */
	protected $route;

	/**
	 * 请求实例
	 *
	 * @var [type]
	 */
	protected $request;

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
	 * 获取实例
	 *
	 * @param  [type] $debug [description]
	 * @return [type]        [description]
	 */
	public static function instance($debug = false)
	{
		if(is_null(self::$instance))
		{
			self::$instance = new self($debug);
		}

		return self::$instance;
	}

	/**
	 * 构造方法
	 *
	 * @param array $debug [description]
	 */
	private function __construct($debug = false)
	{
		$this->debug = $debug;
		// 注册异常处理
		Error::register($this->debug);
		// 获取请求类实例
		$this->request = Request::instance();
	}

	/**
	 * 注册应用应用
	 *
	 * @return [type] [description]
	 */
	public function register($config = [])
	{
		$this->config = array_merge($this->config, $config);
		
		// 注册日志驱动
		if(isset($this->config['log_driver']))
		{
			Log::register($this->config['log_driver']);
		}
		// 注册路由映射表
		if(isset($this->config['route_table']))
		{
			$this->getRoute()->setTable($this->config['route_table']);
		}
		// 注册路由信息
		if(isset($this->config['data']))
		{
			$this->getRoute()->setData($this->config['data']);
		}

		return $this;
	}

	/**
	 * 魔术调用
	 *
	 * @param  [type] $method [description]
	 * @param  [type] $args   [description]
	 * @return [type]         [description]
	 */
	public function __call($method, $args)
	{
		if(in_array($method, ['get', 'post', 'put', 'patch', 'delete', 'head', 'group']))
		{
			// 调用路由实例接口，注册路由
			return call_user_func_array([$this->getRoute(), $method], $args);
		}
	}

	/**
	 * 获取路由
	 *
	 * @return [type] [description]
	 */
	public function getRoute()
	{
		if(is_null($this->route))
		{
			$this->route = Route::instance();
		}

		return $this->route;
	}

	/**
	 * 执行应用
	 *
	 * @return [type] [description]
	 */
	public function run()
	{
		$path = $this->request->pathInfo();
		$method = $this->request->method();

		// 解析路由
		$callback = $this->getRoute()->dispatch($method, $path);
		switch ($callback[0]) {
			// 200 匹配请求
		    case Dispatcher::FOUND: 
		    	// 获得处理函数
		        $this->callback = $callback[1];
		        // 获取请求参数
		        $this->vars = $callback[2];
		        // 获取路由映射表
		        $table = $this->getRoute()->getTable();
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
		        	$next = true;
		        	$result = null;
		        	// 执行中间件
		        	if($this->middleware)
		        	{
		        		// 中间件绑定3个参数
		        		$next = Container::instance()->invoke($this->middleware, [$this->vars, $item]);
		        	}
		        	// 执行控制器
		        	if($next !== false)
		        	{
		        		$result = Container::instance()->invoke($this->controller, $this->vars);
		        	}
		        	// 执行后置件
		        	if($this->append && $next !== false)
		        	{
		        		$result = Container::instance()->invoke($this->append, [$result, $item]);
		        		// var_dump($result);
		        	}
		        	
		        } catch (JumpException $e) {
		        	$result =  $e->getResponse();
		        }
		        
		        if($result instanceof Response)
		        {
		            $response = $result;
		        }
		        elseif(!is_null($result))
		        {
		            $response = Response::create($result);
		        }
		        else
		        {
		            $response = Response::create();
		        }

		        return $response;

		        break;
		    // 405 Method Not Allowed  方法不允许
			case Dispatcher::METHOD_NOT_ALLOWED:
				// 允许调用的请求类型
		        $allowedMethods = $callback[1];
		        throw (new RouteException("Route method is not found", 403))->set($allowedMethods);
		        break;
		    // 404 Not Found 没找到对应的方法
		    case Dispatcher::NOT_FOUND:
		    default:
		        throw new RouteException("Route is not found", 404);
		        break;
		}
	}

}