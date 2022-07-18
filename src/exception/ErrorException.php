<?php

namespace FApi\exception;

use Exception;

/**
 * 程序错误集异常
 * 
 * @author Mon <985558837@qq.com>
 * @version 1.0.0
 */
class ErrorException extends Exception
{
    /**
     * 保存错误级别
     *
     * @var integer
     */
    protected $level;

    /**
     * 错误异常构造函数
     *
     * @param  integer $level    错误级别
     * @param  string  $message  错误详细信息
     * @param  string  $file     出错文件路径
     * @param  integer $line     出错行号
     */
    public function __construct($level, $message, $file, $line)
    {
        $this->level = $level;
        $this->message  = $message;
        $this->file     = $file;
        $this->line     = $line;
        $this->code     = 0;
    }

    /**
     * 获取错误级别
     *
     * @return integer 错误级别
     */
    final public function getLevel()
    {
        return $this->level;
    }
}
