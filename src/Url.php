<?php

namespace FApi;

use FApi\Request;
use FApi\Response;
use FApi\exception\JumpException;

/**
 * URL构建类
 *
 * @author Mon 985558837@qq.com
 * @version 2.0
 * @see 重写URL构造类，重新定义构造方法
 */
class Url
{
    /**
     * 静态单例
     *
     * @var [type]
     */
    protected static $instance;

    /**
     * 服务容器
     *
     * @var [type]
     */
    protected $request;

    /**
     * 构造方法
     */
    public function __construct()
    {
        $this->request = Request::instance();
    }

    /**
     * 获取实例
     *
     * @return [type]         [description]
     */
    public static function instance()
    {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * 构建URL
     *
     * @param  string        $url    URL路径
     * @param  string|array  $vars   传参
     * @param  boolean       $domain 是否补全域名
     * @return string                生成的URL
     */
    public function build($url = '', $vars = [], $domain = false)
    {
        // $url为空是，采用当前pathinfo
        if (empty($url)) {
            $url = $this->request->pathinfo();
        }

        // 判断是否包含域名,解析URL和传参
        if (false === strpos($url, '://') && 0 !== strpos($url, '/')) {
            $info = parse_url($url);
            $url  = !empty($info['path']) ? $info['path'] : '';
            // 判断是否存在锚点,解析请求串
            if (isset($info['fragment'])) {
                // 解析锚点
                $anchor = $info['fragment'];
                if (false !== strpos($anchor, '?')) {
                    // 解析参数
                    list($anchor, $info['query']) = explode('?', $anchor, 2);
                }
            }
        } elseif (false !== strpos($url, '://')) {
            // 存在协议头，自带domain
            $info = parse_url($url);
            $url  = $info['host'];
            $scheme = isset($info['scheme']) ? $info['scheme'] : 'http';
            // 判断是否存在锚点,解析请求串
            if (isset($info['fragment'])) {
                // 解析锚点
                $anchor = $info['fragment'];
                if (false !== strpos($anchor, '?')) {
                    // 解析参数
                    list($anchor, $info['query']) = explode('?', $anchor, 2);
                }
            }
        }

        // 解析参数
        if (is_string($vars)) {
            // aaa=1&bbb=2 转换成数组
            parse_str($vars, $vars);
        }

        // 判断是否已传入URL,且URl中携带传参, 解析传参到$vars中
        if ($url && isset($info['query'])) {
            // 解析地址里面参数 合并到vars
            parse_str($info['query'], $params);
            $vars = array_merge($params, $vars);
            unset($info['query']);
        }

        // 还原锚点
        $anchor = !empty($anchor) ? '#' . $anchor : '';
        // 组装传参
        if (!empty($vars)) {
            $vars = http_build_query($vars);
            $url .= '?' . $vars . $anchor;
        } else {
            $url .= $anchor;
        }

        if (!isset($scheme)) {
            // 补全baseUrl
            $url = rtrim($this->request->baseUrl(), '/') . '/' . ltrim($url, '/');
            // 判断是否需要补全域名
            if ($domain === true) {
                $url = $this->request->domain() . $url;
            }
        } else {
            $url = $scheme . '://' . $url;
        }

        return $url;
    }

    /**
     * 页面跳转
     * 
     * @param  string  $url    跳转URL
     * @param  integer $code   跳转状态码，默认302
     * @param  array   $header 响应头
     * @return [type]          [description]
     */
    public function redirect($url = '', array $vars = [], $code = 302, array $header = [])
    {
        $header['Location'] = $this->build($url, $vars);
        $response = Response::create()->header($header)->code($code);

        throw new JumpException($response);
    }

    /**
     * 返回封装后的API数据到客户端
     * 
     * @param integer   $code           数据集code值
     * @param string    $msg            数据集提示信息
     * @param array     $data           数据集结果集
     * @param array     $extend         或者数据集数据
     * @param string    $type           返回数据类型，默认Json，支持json、xml类型
     * @param array     $header         响应头
     * @param integer   $http_code      响应状态码
     * @return void
     */
    public function result($code = 0, $msg = '', array $data = [], array $extend = [], $type = 'json', array $header = [], $http_code = 200)
    {
        $result = [
            'code' => $code,
            'msg'  => $msg,
            'data' => $data,
        ];

        $result = array_merge($result, $extend);
        ob_get_contents() && ob_end_clean();
        $response = Response::create($result, $type)->header($header)->code($http_code);

        throw new JumpException($response);
    }

    /**
     * 程序结束
     *
     * @param  integer $code    状态码
     * @param  string  $msg     返回内容
     * @param  array   $header  响应头信息
     * @return [type]           [description]
     */
    public function abort($code, $msg = null, array $header = [])
    {
        $response = Response::create($msg, 'html')->header($header)->code($code);
        throw new JumpException($response);
    }
}
