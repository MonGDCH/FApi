<?php
namespace FApi;

use Exception;
use Psr\Log\LoggerInterface;

/**
 * 日志处理类
 * @author Mon
 * @version v2.1 修改打印日志格式
 */
class Log implements LoggerInterface
{
    /**
     * 单例实体
     *
     * @var null
     */
    protected static $instance;

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
     * 记录日志
     *
     * @var array
     */
    public $log = [];

    /**
     * 日志保存驱动, 提供save接口用于保存日志, 且返回true表示日志记录成功
     *
     * @var [type]
     */
    public $driver;

    /**
     * 获取单例
     *
     * @return [type] [description]
     */
    public static function instance()
    {
        if(!self::$instance)
        {
            self::$instance = new self();
        }

        return self::$instance;
    }

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
     * 记录日志信息
     *
     * @param string $level     日志级别
     * @param mixed  $message   日志信息
     * @param array  $context   替换内容
     * @return void
     */
    public function log($level, $message, array $context = [])
    {
        return $this->record($message, $level, $context);
    }

    /**
     * 记录emergency信息
     *
     * @param mixed  $message   日志信息
     * @param array  $context   替换内容
     * @return void
     */
    public function emergency($message, array $context = [])
    {
        return $this->log(__FUNCTION__, $message, $context);
    }

    /**
     * 记录警报信息
     *
     * @param mixed  $message   日志信息
     * @param array  $context   替换内容
     * @return void
     */
    public function alert($message, array $context = [])
    {
        return $this->log(__FUNCTION__, $message, $context);
    }

    /**
     * 记录紧急情况
     *
     * @param mixed  $message   日志信息
     * @param array  $context   替换内容
     * @return void
     */
    public function critical($message, array $context = [])
    {
        return $this->log(__FUNCTION__, $message, $context);
    }

    /**
     * 记录错误信息
     *
     * @param mixed  $message   日志信息
     * @param array  $context   替换内容
     * @return void
     */
    public function error($message, array $context = [])
    {
        return $this->log(__FUNCTION__, $message, $context);
    }

    /**
     * 记录warning信息
     *
     * @param mixed  $message   日志信息
     * @param array  $context   替换内容
     * @return void
     */
    public function warning($message, array $context = [])
    {
        return $this->log(__FUNCTION__, $message, $context);
    }

    /**
     * 记录notice信息
     *
     * @param mixed  $message   日志信息
     * @param array  $context   替换内容
     * @return void
     */
    public function notice($message, array $context = [])
    {
        return $this->log(__FUNCTION__, $message, $context);
    }

    /**
     * 记录一般信息
     *
     * @param mixed  $message   日志信息
     * @param array  $context   替换内容
     * @return void
     */
    public function info($message, array $context = [])
    {
        return $this->log(__FUNCTION__, $message, $context);
    }

    /**
     * 记录调试信息
     *
     * @param mixed  $message   日志信息
     * @param array  $context   替换内容
     * @return void
     */
    public function debug($message, array $context = [])
    {
        return $this->log(__FUNCTION__, $message, $context);
    }

    /**
     * 记录sql信息
     *
     * @param mixed  $message   日志信息
     * @param array  $context   替换内容
     * @return void
     */
    public function sql($message, array $context = [])
    {
        return $this->log(__FUNCTION__, $message, $context);
    }

    /**
     * 记录cache信息
     *
     * @param mixed  $message   日志信息
     * @param array  $context   替换内容
     * @return void
     */
    public function cache($message, array $context = [])
    {
        return $this->log(__FUNCTION__, $message, $context);
    }

    /**
     * 记录日志信息
     *
     * @param mixed  $msg       日志信息
     * @param string $type      日志级别
     * @param array  $context   替换内容
     * @return $this
     */
    public function record($msg, $type = 'info', array $context = [])
    {
        if(is_string($msg))
        {
            $replace = [];
            foreach($context as $key => $val)
            {
                $replace['{' . $key . '}'] = $val;
            }

            $msg = strtr($msg, $replace);
        }

        $this->log[$type][] = $msg;

        if (PHP_SAPI == 'cli') {
            // 命令行日志实时写入
            $this->save();
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