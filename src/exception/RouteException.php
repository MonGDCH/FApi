<?php

namespace FApi\exception;

use Exception;

/**
 * 路由异常
 */
class RouteException extends Exception
{
    /**
     * 异常相关数据
     *
     * @var integer
     */
    protected $data = 500;

    /**
     * 设置异常相关
     *
     * @param mixed $data 移除信息
     */
    public function set($data)
    {
        $this->data = $data;

        return $this;
    }

    /**
     * 获取相关数据
     *
     * @return mixed
     */
    public function get()
    {
        return $this->data;
    }
}
