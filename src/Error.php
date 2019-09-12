<?php

namespace FApi;

use FApi\Hook;
use FApi\exception\RouteException;

/**
 * 异常处理类
 *
 * @author  Mon <985558837@qq.com>
 * @version 2.0
 * @see 2.0 修改日志写入逻辑。
 */
class Error
{
    /**
     * 应用执行模式
     *
     * @var [type]
     */
    protected static $debug;

    /**
     * 注册异常处理接管
     *
     * @param boolean $debug    是否为调试模式
     * @return void
     */
    public static function register($debug)
    {
        self::$debug = $debug;
        // 判断显示所有错误
        !self::$debug or error_reporting(E_ALL);
        // 错误
        set_error_handler([__CLASS__, 'appError']);
        // 异常
        set_exception_handler([__CLASS__, 'appException']);
        // 致命错误|结束运行
        register_shutdown_function([__CLASS__, 'fatalError']);
    }

    /**
     * PHP结束运行
     *
     * @return [type] [description]
     */
    public static function fatalError()
    {
        $error = error_get_last() ?: null;
        if (!is_null($error) && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR])) {
            // 应用错误
            $error['level'] = 'error';
            Hook::listen('error', $error);
            self::halt($error);
        } else {
            // 应用结束
            Hook::listen('end');
        }
    }

    /**
     * 应用错误
     *
     * @param  integer $errno   错误编号
     * @param  integer $errstr  详细错误信息
     * @param  string  $errfile 出错的文件
     * @param  integer $errline 出错行号
     * @throws ErrorException
     */
    public static function appError($errno, $errstr, $errfile = '', $errline = 0)
    {
        $error = [
            'errorNum'  => $errno,
            'message'   => $errstr,
            'file'      => $errfile,
            'line'      => $errline,
            'level'     => 'warning',
        ];

        // 应用错误
        Hook::listen('error', $error);
        self::halt($error);
    }

    /**
     * 应用异常
     *
     * @param [type] $e 异常实例
     * @return void
     */
    public static function appException($e)
    {
        $error = [];
        $error['message']   = $e->getMessage();
        $error['file']      = $e->getFile();
        $error['line']      = $e->getLine();
        $trace = $e->getTrace();
        if (isset($trace[0]) && !empty($trace[0]['function']) && $trace[0]['function'] == 'exception') {
            $error['file'] = $trace[0]['file'];
            $error['line'] = $trace[0]['line'];
        }
        $error['function'] = $error['class'] = '';
        $error['level'] = 'exception';

        // 应用异常
        Hook::listen('error', $error);
        $code = ($e instanceof RouteException) ? $e->getCode() : 500;
        self::halt($error, $code);
    }

    /**
     * 异常输出
     *
     * @param [type] $error 错误信息
     * @param integer $code 错误码
     * @return void
     */
    public static function halt($error, $code = 500)
    {
        // 清空输出缓存
        ob_get_contents() && ob_end_clean();
        http_response_code($code);
        // 调试模式, 引入错误提示模板
        if (self::$debug) {
            include __DIR__ . '/tpl/exception.tpl';
        }
        // 非调试模式，不返回
        exit();
    }
}
