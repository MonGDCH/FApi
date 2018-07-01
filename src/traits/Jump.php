<?php
namespace FApi\traits;

use FApi\Response;
use FApi\exception\JumpException;

/**
 * 程序结束，返回输出结果
 *
 * @author Mon 985558837@qq.com
 * @version v1.0 
 */
trait Jump
{
	/**
	 * 页面跳转
	 * 
	 * @param  string  $url    [description]
	 * @param  integer $code   [description]
	 * @param  array   $header [description]
	 * @return [type]          [description]
	 */
	protected function redirect($url = '', $code = 302, $header = [])
	{
		$header['Location'] = $url;
		$response = Response::create()->header($header)->code($code);

		throw new JumpException($response);
	}

	/**
     * 返回封装后的API数据到客户端
     *
     * @param mixed  $data   要返回的数据
     * @param int    $code   返回的 code
     * @param mixed  $msg    提示信息
     * @param array  $extend 返回数据扩展字段
     * @param string $type   返回数据格式
     * @param array  $header 发送的 Header 信息
     * @return void
     * @throws HttpResponseException
     */
    protected function result($code = 0, $msg = '', $data = [], $extend = [], $type = 'json', array $header = [])
    {
        $result = [
            'code' => $code,
            'msg'  => $msg,
            'data' => $data,
        ];

        $result = array_merge($result, $extend);
        ob_get_contents() && ob_end_clean();
        $response = Response::create($result, $type)->header($header);

        throw new JumpException($response);
    }

    /**
     * 程序结束
     *
     * @param  [type] $code    状态码
     * @param  [type] $msg     返回内容
     * @param  array  $header  响应头信息
     * @return [type]          [description]
     */
    protected function abort($code, $msg = null, $header = [])
    {
        $response = Response::create($msg, 'html')->header($header)->code($code);
        throw new JumpException($response);
    }
}