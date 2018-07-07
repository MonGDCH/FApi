<?php
namespace FApi\traits;

/**
 * 日志接口
 */
trait LogInterface
{
	/**
     * 记录日志
     *
     * @var array
     */
    public $log = [];

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
}