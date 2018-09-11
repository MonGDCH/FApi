<?php
namespace FApi\traits;

trait Instance
{
	/**
	 * 对象单例
	 *
	 * @var [type]
	 */
	protected static $instance;

	/**
	 * 获取实例
	 *
	 * @param  [type] $option [description]
	 * @return [type]         [description]
	 */
	public static function instance($option = [])
	{
		if(is_null(static::$instance)){
			static::$instance = new static($option);
		}

		return static::$instance;
	}
}