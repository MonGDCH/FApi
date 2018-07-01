<?php
namespace FApi;

use Closure;
use FApi\Container;
use FApi\exception\RouteException;
use FastRoute\RouteParser\Std;
use FastRoute\DataGenerator\GroupCountBased;
use FastRoute\Dispatcher\GroupCountBased as Dispatcher;
use FastRoute\RouteCollector;

/**
* 路由封装
*/
class Route
{
    /**
     * 路由实例
     *
     * @var [type]
     */
    public static $instance;

	/**
	 * fast-route路由容器
	 *
	 * @var [type]
	 */
	protected $collector;

    /**
     * 路由映射表
     *
     * @var [type]
     */
    protected $table = [];

    /**
     * 路由信息
     *
     * @var array
     */
    protected $data = [];

    /**
     * 是否定义组别路由
     *
     * @var boolean
     */
    protected $isGroup = false;

    /**
     * 路由组前缀
     *
     * @var string
     */
    protected $groupPrefix = '';

    /**
     * 回调命名空间前缀
     *
     * @var string
     */
    protected $prefix = '';

    /**
     * 中间件回调
     *
     * @var string
     */
    protected $middleware;

    /**
     * 路由后置
     *
     * @var [type]
     */
    protected $append;

    /**
     * 获取单例
     *
     * @return [type] [description]
     */
    public static function instance($prefix = '', $groupPrefix = '')
    {
        if(!self::$instance)
        {
            self::$instance = new self($prefix = '', $groupPrefix = '');
        }

        return self::$instance;
    }

    /**
     * 私有化构造方法
     */
    private function __construct($prefix = '', $groupPrefix = '')
    {
        $this->groupPrefix = $groupPrefix;
        $this->prefix = $prefix;
    }

    /**
     * 设置路由表
     *
     * @param [type] $table [description]
     */
    public function setTable(array $table)
    {
        $this->table = $table;
    }

    /**
     * 获取路由表
     *
     * @return [type] [description]
     */
    public function getTable()
    {
        return $this->table;
    }

    /**
     * 设置路由数据
     * @param array $data [description]
     */
    public function setData(array $data)
    {
        $this->data = $data;
    }

    /**
     * 获取路由数据
     *
     * @return [type] [description]
     */
    public function getData()
    {
        return $this->data ?: $this->collector()->getData();
    }

    /**
     * 设置中间件
     *
     * @param  [type] $callback [description]
     * @return [type]           [description]
     */
    public function middleware($callback)
    {
        if($this->isGroup)
        {
            throw new RouteException("routing in the group routing definition cannot define middleware alone!", 500);
        }

        $this->middleware = $callback;
        return $this;
    }

    /**
     * 路由后置
     *
     * @param  [type] $callback [description]
     * @return [type]           [description]
     */
    public function append($callback)
    {
        if($this->isGroup)
        {
            throw new RouteException("routing in the group routing definition cannot define the postpack separately", 500);
        }

        $this->append =  $callback;
        return $this;
    }

	/**
     * 注册GET路由
     *
     * @param  string $pattern  请求路径
     * @param  [type] $callback 路由回调
     */
    public function get($pattern, $callback)
    {
        return $this->map(['GET'], $pattern, $callback);
    }

    /**
     * 注册POST路由
     *
     * @param  string $pattern  请求路径
     * @param  [type] $callback 路由回调
     */
    public function post($pattern, $callback)
    {
        return $this->map(['POST'], $pattern, $callback);
    }

    /**
     * 注册PUT路由
     *
     * @param  string $pattern  请求路径
     * @param  [type] $callback 路由回调
     */
    public function put($pattern, $callback)
    {
        return $this->map(['PUT'], $pattern, $callback);
    }

    /**
     * 注册PATCH路由
     *
     * @param  string $pattern  请求路径
     * @param  [type] $callback 路由回调
     */
    public function patch($pattern, $callback)
    {
        return $this->map(['PATCH'], $pattern, $callback);
    }

    /**
     * 注册DELETE路由
     *
     * @param  string $pattern  请求路径
     * @param  [type] $callback 路由回调
     */
    public function delete($pattern, $callback)
    {
        return $this->map(['DELETE'], $pattern, $callback);
    }

    /**
     * 注册OPTIONS路由
     *
     * @param  string $pattern  请求路径
     * @param  [type] $callback 路由回调
     */
    public function options($pattern, $callback)
    {
        return $this->map(['OPTIONS'], $pattern, $callback);
    }

    /**
     * 注册任意请求方式的路由
     *
     * @param  string $pattern  请求路径
     * @param  [type] $callback 路由回调
     */
    public function any($pattern, $callback)
    {
        return $this->map(['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'], $pattern, $callback);
    }

    /**
     * 注册组别路由
     *
     * @param  string  $pattern  路由前缀
     * @param  Closure $callback 路由回调
     * @return [type]            [description]
     */
    public function group($pattern, Closure $callback)
    {
        $this->isGroup = true;
        $groupPrefix = $this->groupPrefix;
        $prefix = $this->prefix;


        if(is_string($pattern))
        {
            $this->groupPrefix .= $pattern;
        }
        elseif(is_array($pattern))
        {
            $this->groupPrefix .= isset($pattern['prefix']) ? $pattern['prefix'] : '';
            $this->prefix .= isset($pattern['namespace']) ? $pattern['namespace'] : '';
        }
        $callback($this);
        $this->groupPrefix = $groupPrefix;
        $this->prefix = $prefix;
        $this->middleware =  null;
        $this->append = null;
        $this->isGroup =  false;
    }

    /**
     * 注册路由方法
     *
     * @param  array  $method   请求方式
     * @param  string $pattern  请求路径
     * @param  [type] $callback 路由回调
     * @return [type]           [description]
     */
    public function map(array $method, $pattern, $callback)
    {   
        if($callback instanceof Closure)
        {
            // TODO 这里绑定的容器对象有时候会获取不到
            $callback = $callback->bindTo(Container::instance());
        }

        // 增加路由前缀
        $pattern = $this->groupPrefix . $pattern;
        if(is_string($callback))
        {
            $callback = $this->prefix . $callback;
        }
        
        // 记录路由表
        $name = $this->getName();
        $this->table[$name] = [
            'middleware'    => $this->middleware,
            'callback'      => $callback,
            'append'        => $this->append
        ];
        // 注册fast-route路由表
        $this->collector()->addRoute($method, $pattern, $name);

        // 未组别路由，重装中间件、后置件
        if(!$this->isGroup)
        {
            $this->middleware =  null;
            $this->append = null;
        }
    }

	/**
	 * 获取路由容器
	 *
	 * @return [type] [description]
	 */
	public function collector()
	{
		if(is_null($this->collector))
		{
			$this->collector = new RouteCollector(new Std, new GroupCountBased);
		}

		return $this->collector;
	}

	/**
	 * 执行路由
	 *
	 * @param  string $method 请求类型
	 * @param  string $path   请求路径
	 * @return [type]         [description]
	 */
	public function dispatch($method, $path)
	{
		if(empty($this->data))
		{
			$this->data = $this->collector()->getData();
		}
		$dispatch = new Dispatcher($this->data);

		return $dispatch->dispatch($method, $path);
	}

    /**
     * 获取当前路由的唯一标志
     *
     * @return [type] [description]
     */
    public function getName()
    {
        return md5(uniqid());
    }

}