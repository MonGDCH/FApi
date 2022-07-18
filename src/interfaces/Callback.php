<?php

namespace FApi\interfaces;

/**
 * App回调接口
 * 
 * @author Mon <985558837@qq.com>
 * @version 1.0.0
 */
interface Callback
{
    /**
     * 获取响应结果集
     *
     * @return mixed
     */
    public function getResult();

    /**
     * 设置响应结果集
     *
     * @param mixed $result 结果集
     * @return App
     */
    public function setResult($result);

    /**
     * 获取路由回调
     *
     * @return mixed
     */
    public function getCallback();

    /**
     * 设置路由回调
     *
     * @param array $callback   回调信息
     * @return mixed
     */
    public function setCallback(array $callback);

    /**
     * 获取路由参数
     *
     * @return mixed
     */
    public function getVars();

    /**
     * 设置路由参数
     *
     * @param array $vars   路由参数
     * @return mixed
     */
    public function setVars(array $vars);

    /**
     * 获取前置中间件
     *
     * @return mixed
     */
    public function getBefor();

    /**
     * 设置前置中间件
     *
     * @param array $befor  中间件
     * @return mixed
     */
    public function setBefor(array $befor);

    /**
     * 获取回调控制器
     *
     * @return mixed
     */
    public function getController();

    /**
     * 设置回调控制器
     *
     * @param array $controller 控制器
     * @return mixed
     */
    public function setController($controller);

    /**
     * 获取后置中间件
     *
     * @return mixed
     */
    public function getAfter();

    /**
     * 设置后置中间件
     *
     * @param array $after  中间件
     * @return mixed
     */
    public function setAfter(array $after);
}
