<?php

namespace FApi;

use Closure;
use mon\util\Container;

/**
 * 钩子类
 *
 * 预置钩子
 * bootstarap           应用初始化
 * run                  执行应用前
 * beforAction          回调执行前, 传值App实例
 * afterAction          回调执行后，传值回调返回结果集
 * beforSend            响应输出前，传值响应实例中的响应内容
 * afterSend            响应输出后，传值响应实例中的响应内容
 * end                  应用执行结束
 * error                应用错误, 传值错误信息
 * 
 * @author Mon <985558837@qq.com>
 * @version 1.0.0
 */
class Hook
{
    /**
     * 构造列表
     *
     * @var array
     */
    private static $tags = [];

    /**
     * 批量注册钩子
     *
     * @param  array  $tags 钩子标识
     * @return void
     */
    public static function register(array $tags)
    {
        self::$tags = array_merge(self::$tags, $tags);
    }

    /**
     * 监听一个行为
     *
     * @param mixed $tag     钩子名称
     * @param mixed $callbak 钩子回调
     * @return void
     */
    public static function listen($tag, $callbak)
    {
        isset(self::$tags[$tag]) || self::$tags[$tag] = [];
        self::$tags[$tag][] = $callbak;
    }

    /**
     * 获取钩子信息
     *
     * @param  string $tag 钩子名称
     * @return array
     */
    public static function get($tag = '')
    {
        if (empty($tag)) {
            //获取全部的插件信息
            return self::$tags;
        } else {
            return array_key_exists($tag, self::$tags) ? self::$tags[$tag] : [];
        }
    }

    /**
     * 触发&执行行为
     *
     * @param  string $tag   钩子名称
     * @param  mixed $params 参数
     * @return array
     */
    public static function trigger($tag, $params = null)
    {
        $tags = static::get($tag);
        $results = [];
        foreach ($tags as $k => $v) {
            $results[$k] = self::exec($v, $k, $params);
            if ($results[$k] === false) {
                // 如果返回false 则中断行为执行
                break;
            }
        }

        return $results;
    }

    /**
     * 执行一个行为
     *
     * @param  mixed  $class   行为回调
     * @param  string $tag     钩子名称
     * @param  mixed  $params  参数
     * @return mixed
     */
    public static function exec($class, $tag = '', $params = null)
    {
        if ($class instanceof Closure) {
            // 匿名回调
            return call_user_func_array($class, [$params]);
        } elseif (is_string($class) && !empty($class)) {
            return Container::instance()->invokeMethd([$class, 'handler'], [$params]);
        }
    }
}
