<?php

namespace FApi;

use Exception;
use FApi\Hook;
use FApi\exception\ErrorException;
use FApi\exception\RouteException;

/**
 * 异常处理类
 *
 * @author  Mon <985558837@qq.com>
 * @version 2.0
 */
class Error
{
    /**
     * 应用执行模式
     *
     * @var boolean
     */
    protected static $debug;

    /**
     * 注册异常处理接管
     *
     * @param boolean $debug 是否为调试模式
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
     * @return void
     */
    public static function fatalError()
    {
        $error = error_get_last() ?: null;
        if (!is_null($error) && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR])) {
            // 应用错误
            $exception = new ErrorException($error['type'], $error['message'], $error['file'], $error['line']);
            self::appException($exception);
        } else {
            // 应用结束
            Hook::trigger('end');
        }
    }

    /**
     * 应用错误
     *
     * @param  integer $errno   错误编号
     * @param  integer $errstr  详细错误信息
     * @param  string  $errfile 出错的文件
     * @param  integer $errline 出错行号
     * @return void
     */
    public static function appError($errno, $errstr, $errfile = '', $errline = 0)
    {
        $exception = new ErrorException($errno, $errstr, $errfile, $errline);
        self::appException($exception);
    }

    /**
     * 应用异常
     *
     * @param mixed $e 异常实例
     * @return void
     */
    public static function appException($e)
    {
        // 应用异常
        Hook::trigger('error', $e);
        $code = ($e instanceof RouteException) ? $e->getCode() : 500;
        self::halt($e, $code);
    }

    /**
     * 异常输出
     *
     * @param Exception  $error 错误信息
     * @param integer $code 错误码
     * @return void
     */
    protected static function halt($exception, $httpCode = 500)
    {
        if (!(PHP_SAPI == 'cli' || PHP_SAPI == 'cli-server')) {
            // 清空输出缓存
            ob_get_contents() && ob_end_clean();
            http_response_code($httpCode);
            // 调试模式, 引入错误提示模板
            if (self::$debug) {
                $data = [
                    'name'    => get_class($exception),
                    'file'    => $exception->getFile(),
                    'line'    => $exception->getLine(),
                    'message' => $exception->getMessage(),
                    'trace'   => $exception->getTrace(),
                    'code'    => $exception->getCode(),
                    'source'  => self::getSourceCode($exception),
                    'tables'  => [
                        'GET Data'              => $_GET,
                        'POST Data'             => $_POST,
                        'Files'                 => $_FILES,
                        'Cookies'               => $_COOKIE,
                        'Session'               => isset($_SESSION) ? $_SESSION : [],
                        'Server/Request Data'   => $_SERVER,
                        'Environment Variables' => $_ENV,
                    ],
                ];
                extract($data);
                include __DIR__ . '/tpl/exception.tpl';
            }
            // 非调试模式，不返回
            exit();
        } else {
            // 脚本调用，直接抛出异常
            throw $exception;
        }
    }

    /**
     * 获取出错文件内容
     * 获取错误的前9行和后9行
     * 
     * @param  Exception $exception
     * @return array 错误文件内容
     */
    protected static function getSourceCode($exception)
    {
        // 读取前9行和后9行
        $line  = $exception->getLine();
        $first = ($line - 9 > 0) ? $line - 9 : 1;

        try {
            $contents = file($exception->getFile());
            $source   = [
                'first'  => $first,
                'source' => array_slice($contents, $first - 1, 19),
            ];
        } catch (Exception $e) {
            $source = [];
        }

        return $source;
    }
}
