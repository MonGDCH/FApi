<?php
namespace FApi\exception;

use Exception;
use FApi\Response;

/**
* 路由异常
*
* @author Mon 985558837@qq.com
*/
class JumpException extends Exception
{
    /**
     * 响应类实例
     *
     * @var [type]
     */
    protected $response;

    /**
     * 构造方法
     *
     * @param Response $response [description]
     */
    public function __construct(Response $response)
    {
        $this->response = $response;
    }

    /**
     * 获取响应实例
     *
     * @return [type] [description]
     */
    public function getResponse()
    {
        return $this->response;
    }
}