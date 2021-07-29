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
     * @var Response
     */
    protected $response;

    /**
     * 构造方法
     *
     * @param Response $response 响应类
     */
    public function __construct(Response $response)
    {
        $this->response = $response;
    }

    /**
     * 获取响应实例
     *
     * @return Response
     */
    final public function getResponse()
    {
        return $this->response;
    }
}
