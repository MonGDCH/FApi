<?php
namespace FApi;

use FApi\Container;
use FApi\exception\RouteException;

/**
 * 异常处理类
 *
 * @author  Mon <985558837@qq.com>
 * @version 2.0
 * @see 修改日志写入逻辑。
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
	 * @return [type] [description]
	 */
	public static function register()
	{
		self::$debug = Container::get('config')->get('debug', false);
		// 判断显示所有错误
		!self::$debug or error_reporting(E_ALL);
		// 错误
        set_error_handler( [ __CLASS__, 'appError'] );
       	// 异常
        set_exception_handler( [ __CLASS__, 'appException'] );
        // 致命错误|结束运行
        register_shutdown_function( [__CLASS__, 'fatalError'] );
	}

	/**
	 * PHP结束运行
	 *
	 * @return [type] [description]
	 */
	public static function fatalError()
	{
		$error = error_get_last() ?: null;
        if(!is_null($error) && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR]))
        {
        	Container::get('log')->error(self::makeErrorMsg($error))->save();
        	self::halt($error);
        }
        else
        {
        	Container::get('log')->save();
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
        	'errorNum'	=> $errno,
        	'message'	=> $errstr,
        	'file'		=> $errfile,
        	'line'		=> $errline
        ];

        Container::get('log')->warning(self::makeErrorMsg($error))->save();
        self::halt($error);
    }

	/**
	 * 应用异常
	 *
	 * @return [type] [description]
	 */
	public static function appException($e)
	{
		$error = [];
        $error['message']  	= $e->getMessage();
        $error['file'] 		= $e->getFile();
        $error['line'] 		= $e->getLine();
        $trace = $e->getTrace();
        if(empty($trace[0]['function']) && $trace[0]['function'] == 'exception') {
            $error['file'] = $trace[0]['file'];
            $error['line'] = $trace[0]['line'];
        }
        $error['function'] = $error['class'] = '';

        Container::get('log')->alert(self::makeErrorMsg($error))->save();
        $code = ($e instanceof RouteException) ? $e->getCode() : 500;
        self::halt($error, $code);
	}

	/**
	 * 异常输出
	 * @return [type] [description]
	 */
	public static function halt($error, $code = 500)
	{
		// 清空输出缓存
		ob_get_contents() && ob_end_clean();
		http_response_code($code);
		// 调试模式, 引入错误提示模板
		if(self::$debug)
		{
			include __DIR__ . '/tpl/exception.tpl';
		}
		// 非调试模式，不返回
		exit();
	}

	/**
	 * 格式化错误信息
	 *
	 * @param  [type] $error [description]
	 * @return [type]        [description]
	 */
	private static function makeErrorMsg($error)
	{
		// 触发时间
		$time = date('Y-m-d H:s:s', time());
		// 分割错误信息数组为字符串
		$message = "[{$time}] {$error['message']} in file {$error['file']} on line {$error['line']}";

		return $message;
	}
}