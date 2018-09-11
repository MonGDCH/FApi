<?php
namespace FApi;

use Closure;
use ReflectionClass;
use ReflectionMethod;
use ReflectionFunction;
use InvalidArgumentException;
use FApi\traits\Instance;

/**
 * 服务容器类
 *
 * @author Mon 985558837@qq.com
 * @version 1.2  2018-07-05
 */
class Container
{
	use Instance;

	/**
	 * 容器中对象的标识符
	 *
	 * @var array
	 */
	protected $bind = [];

	/**
	 * 实例容器
	 *
	 * @var array
	 */
	protected $service = [];

	/**
	 * 获取容器中的对象实例
	 *
	 * @param  [type]  $abstract    对象名称或标识
	 * @param  array   $vars        入参
	 * @param  boolean $newInstance 是否获取新的实例
	 * @return object
	 */
	public static function get($abstract, $vars = [], $newInstance = false)
	{
		return static::instance()->make($abstract, $vars, $newInstance);
	}

	/**
	 * 绑定一个类、闭包、实例、接口实现到容器
	 *
	 * @param [type] $abstract 类名称或标识符
	 * @param [type] $server   要绑定的实例
	 * @return Container
	 */
	public static function set($abstract, $server = null)
	{
		return static::instance()->bind($abstract, $server);
	}

	/**
	 * 私有化构造方法
	 */
	private function __construct(){}

	/**
	 * 绑定一个类、闭包、实例、接口实现到容器
	 *
	 * @param [type] $abstract 类名称或标识符
	 * @param [type] $server   要绑定的实例
	 *
	 * @return $this
	 */
	public function bind($abstract, $server = null)
	{
		if(is_array($abstract)){
			// 传入数组，批量注册
			$this->bind = array_merge($this->bind, $abstract);
		}
		elseif($server instanceof Closure){
			// 闭包，绑定闭包
			$this->bind[$abstract] = $server;
		}
		elseif(is_object($server)){
			// 实例化后的对象, 保存到实例容器中
			$this->service[$abstract] = $server;
		}
		else{
			// 对象类名称，先保存，不实例化
			$this->bind[$abstract] = $server;
		}

		return $this;
	}

	/**
	 * 判断容器中是否存在某个类或标识
	 *
	 * @param  [type]  $abstract 类名称或标识符
	 * @return boolean           [description]
	 */
	public function has($abstract)
	{
		return isset($this->bind[$abstract]) || isset($this->service[$abstract]);
	}

	/**
	 * 创建获取对象的实例
	 *
	 * @param  string  $abstract 类名称或标识符
	 * @param  array   $vars     [description]
	 * @param  boolean $new      [description]
	 * @return object
	 */
	public function make($abstract, $vars = [], $new = false)
	{
		if(isset($this->service[$abstract]) && !$new){
			$object = $this->service[$abstract];
		}
		else{
			if(isset($this->bind[$abstract])){
				// 存在标识
				$service = $this->bind[$abstract];

				if($service instanceof Closure){
					// 匿名函数，绑定参数
					$object = $this->invokeFunction($service, $vars);
				}
				elseif(is_object($service)){
					// 已实例化的对象
					$object = $service;
				}
				else{
					// 类对象，回调获取实例
					$object = $this->make($service, $vars, $new);
				}
			}
			else{
				// 不存在，判断为直接写入的类对象, 获取实例
				$object = $this->invokeClass($abstract, $vars);
			}

			// 保存实例
			if (!$new) {
                $this->service[$abstract] = $object;
            }
		}

		return $object;
	}

	/**
	 * 绑定参数，执行函数或者闭包
	 *
	 * @param  [type] $function 函数或者闭包
	 * @param  [type] $vars     变量
	 */
	public function invokeFunction($function, $vars = [])
	{
		// 创建反射对象
		$reflact = new ReflectionFunction($function);
		// 获取参数
		$args = $this->bindParams($reflact, $vars);

		return $reflact->invokeArgs($args);
	}

	/**
	 * 执行类型方法， 绑定参数
	 *
	 * @param  string|array $method 方法
	 * @param  array  		$vars   参数
	 */
	public function invokeMethd($method, $vars = [])
	{
		if(is_string($method)){
			$method = explode('@', $method);
		}

		if(is_array($method)){
			$class = is_object($method[0]) ? $method[0] : $this->invokeClass($method[0]);
			$reflact = new ReflectionMethod($class, $method[1]);
		}
		else{
			$reflact = new ReflectionMethod($method);
		}

		$args = $this->bindParams($reflact, $vars);

		return $reflact->invokeArgs(isset($class) ? $class : null, $args);
	}

	/**
	 * 反射执行对象实例化，支持构造方法依赖注入
	 * @param  [type] $class [description]
	 * @param  [type] $vars  [description]
	 * @return [type]        [description]
	 */
	public function invokeClass($class, $vars = [])
	{
		$reflect     = new ReflectionClass($class);
		// 获取构造方法
        $constructor = $reflect->getConstructor();

        if ($constructor){
        	// 存在构造方法
            $args = $this->bindParams($constructor, $vars);
        }
        else{
            $args = [];
        }

        return $reflect->newInstanceArgs($args);
	}

	/**
	 * 反射执行回调方法
	 *
	 * @param  [type] $callback 回调方法
	 * @param  array  $vars     参数
	 */
	public function invoke($callback, $vars = [])
	{
		if($callback instanceof Closure){
			$result = $this->invokeFunction($callback, $vars);
		}
		else{
			$result = $this->invokeMethd($callback, $vars);
		}

		return $result;
	}

	/**
	 * 为反射对象绑定参数
	 *
	 * @param  object $reflact 反射对象
	 * @param  array  $vars    参数
	 * @return array
	 */
	protected function bindParams($reflact, $vars = [])
	{
		$args = [];

		if($reflact->getNumberOfParameters() > 0){
			// 判断数组类型 数字数组时按顺序绑定参数
			reset($vars);
			$type = key($vars) === 0 ? 1 : 0;

			// 获取类方法需要的参数
			$params = $reflact->getParameters();

			// 获取参数类型, 绑定参数
			foreach($params as $param)
			{
				$name  = $param->getName();
                $class = $param->getClass();

                if ($class) {
                	$className = $class->getName();
                	$args[] = $this->make($className);
                }
                elseif (1 == $type && !empty($vars)) {
                    $args[] = array_shift($vars);
                } 
                elseif (0 == $type && isset($vars[$name])) {
                    $args[] = $vars[$name];
                }
                elseif ($param->isDefaultValueAvailable()) {
                	$args[] = $param->getDefaultValue();
                }
                else {
                	throw new InvalidArgumentException('bind parameters were not found! [ '.$name.' ]', 500);
                }
			}
		}

		return $args;
	}

	/**
	 * 魔术方法获取实例
	 *
	 * @param  [type] $abstract [description]
	 * @return [type]           [description]
	 */
    public function __get($abstract)
    {
    	return static::get($abstract);
    }

}