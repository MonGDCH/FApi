<?php
namespace FApi;

use Exception;
use FApi\traits\Instance;
use FApi\traits\LogInterface;

/**
 * 日志处理类
 * @author Mon
 * @version v2.1 修改打印日志格式
 */
class Log
{
    use Instance, LogInterface;

    /**
     * 日志级别
     */
    const EMERGENCY = 'emergency';
    const ALERT     = 'alert';
    const CRITICAL  = 'critical';
    const ERROR     = 'error';
    const WARNING   = 'warning';
    const NOTICE    = 'notice';
    const INFO      = 'info';
    const DEBUG     = 'debug';
    const SQL       = 'sql';
    const CACHE     = 'cache';

    /**
     * 日志保存驱动, 提供save接口用于保存日志, 且返回true表示日志记录成功
     *
     * @var [type]
     */
    public $driver;

    /**
     * 注册日志配置
     *
     * @return [type]         [description]
     */
    private function __construct(){}

    /**
     * 注册日志驱动
     *
     * @param  [type] $driver [description]
     * @return [type]        [description]
     */
    public function register($driver)
    {
        // 传入对象实例
        if(is_object($driver))
        {
            $this->driver = $driver;
        }
        // 传入对象路径
        elseif(is_string($driver))
        {
            if(!class_exists($driver))
            {
                throw new Exception("log driver is not found! [ {$driver} ]", 500);
            }
            $this->driver = new $driver();
        }
        else
        {
            throw new Exception("Log drive error! Only allowed object instances or object names! ", 500);
        }

        return $this;
    }

    /**
     * 获取日志信息
     *
     * @param string $type 信息类型
     * @return array
     */
    public function getLog($type = '')
    {
        return $type ? $this->log[$type] : $this->log;
    }

    /**
     * 清空日志信息
     *
     * @return $this
     */
    public function clear()
    {
        $this->log = [];
        return $this;
    }

    /**
     * 批量写入日志(系统结束自动调用)
     *
     * @return [type] [description]
     */
    public function save()
    {
        if(!empty($this->log) && !is_null($this->driver))
        {
            // 调用日志保存驱动记录日志
            $save = $this->driver->save($this->log);
            // 保存成功，情况日志列表
            if($save)
            {
                $this->log = [];
            }
            return $save;
        }

        return true;
    }
}