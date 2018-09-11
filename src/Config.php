<?php
namespace FApi;

use Exception;
use FApi\traits\Instance;

/**
 * 配置信息类
 *
 * @author Mon <985558837@qq.com>
 * @version 1.2
 */
class Config
{
	use Instance;

	/**
	 * 存储配置信息
	 *
	 * @var array
	 */
	public $config = [];

	/**
	 * 注册配置
	 *
	 * @param  array  $config 配置信息
	 * @return [type]         [description]
	 */
	public function register(array $config = [])
	{
		// 合并获取配置信息
		$this->config = array_merge($this->config, $config);
		return $this->config;
	}

	/**
	 * 加载配置文件
	 *
	 * @param  [type] $config 扩展配置文件名
	 * @param  [type] $alias  配置节点别名
	 * @return [type]         [description]
	 */
	public function load($config, $alias = null)
	{
		if(!file_exists($config)){
			throw new Exception("config file not found! [ {$config} ]");
		}
		$data = include $config;
		$node = (empty($alias)) ? $config : $alias;
		$this->config[$node] = $data;

		return $this->get($node);
	}

	/**
	 * 动态设置配置信息, 最多通过'.'分割设置2级配置
	 *
	 * @param [type] $key   [description]
	 * @param [type] $value [description]
	 */
	public function set($key, $value = null)
	{
		if(is_array($key)){
			// 数组，批量注册
			return $this->register($config);
		}
		elseif(is_string($key)){
			// 字符串，节点配置
			if (!strpos($key, '.')) {
				$this->config[ $key ] = $value;
			}
			else{
				$name = explode('.', $key, 2);
				$this->config[$name[0]][$name[1]] = $value;
			}

		}
		return $value;
	}

	/**
	 * 获取配置信息内容, 可以通过'.'分割获取无限级节点数据
	 *
	 * @param  [type] $key     [description]
	 * @param  [type] $default [description]
	 * @return [type]          [description]
	 */
	public function get($key = null, $default = null)
	{
		if(empty($key)){
			return $this->config;
		}
		// 以"."分割，支持多纬度配置信息获取
		$name = explode('.', $key);

		$data = $this->config;
		for($i=0,$len=count($name); $i<$len; $i++){
			// 不存在配置节点，返回默认值
			if(!isset($data[ $name[$i] ])){
				$data = $default;
				break;
			}
			$data = $data[ $name[$i] ];
		}

		return $data;
	}

	/**
	 * 清空配置信息
	 *
	 * @return [type] [description]
	 */
	public function clear()
	{
		$this->config = [];
		return $this;
	}

}