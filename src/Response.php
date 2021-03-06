<?php

namespace FApi;

use FApi\Hook;
use mon\util\Tool;
use FApi\exception\ResponseException;

class Response
{
    /**
     * 响应类型
     *
     * @var string
     */
    protected $type;

    /**
     * 响应数据
     *
     * @var array
     */
    protected $data;

    /**
     * 字符集
     *
     * @var string
     */
    protected $charset = 'utf-8';

    /**
     * 状态值
     *
     * @var integer
     */
    protected $code = 200;

    /**
     * 输出的响应头
     *
     * @var array
     */
    protected $header = [];

    /**
     * 响应头
     *
     * @var array
     */
    protected $headers = [
        'json'   => 'application/json',
        'xml'    => 'text/xml',
        'html'   => 'text/html',
        'js'     => 'application/javascript',
        'text'   => 'text/plain',
    ];

    /**
     * 构造方法
     *
     * @param  string  $data 发送的数据
     * @param  string  $type 数据类型
     * @param  integer $code 状态码
     */
    private function __construct($data = '', $type = 'html', $code = 200)
    {
        $this->data = $data;
        $this->type = strtolower($type);
        $this->code = $code;

        // 设置头信息
        $header = $this->headers[$this->type] . ';charset=' . $this->charset;
        $this->header('Content-Type', $header);
    }

    /**
     * 创建一个响应结果集
     *
     * @param  string  $data 发送的数据
     * @param  string  $type 数据类型
     * @param  integer $code 状态码
     * @return Response
     */
    public static function create($data = '', $type = 'html', $code = 200)
    {
        return new self($data, $type, $code);
    }

    /**
     * 设置响应头
     *
     * @param  mixed  $name 响应类型
     * @param  mixed  $val  值
     * @return Response
     */
    public function header($name, $val = null)
    {
        if (is_array($name)) {
            $this->header = array_merge($this->header, (array) $name);
        } else {
            $this->header[$name] = $val;
        }
        return $this;
    }

    /**
     * 获取请求头
     *
     * @return array
     */
    public function getHeader()
    {
        return $this->header;
    }

    /**
     * 设置输出的数据
     *
     * @param  mixed $data 输出结果集
     * @return Response
     */
    public function data($data)
    {
        $this->data = $data;
        return $this;
    }

    /**
     * 设置状态码
     *
     * @param  integer $code 状态码
     * @return Response
     */
    public function code($code)
    {
        $this->code = $code;
        return $this;
    }

    /**
     * 获取状态码
     *
     * @return integer
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * 设置响应数据类型
     *
     * @param  string $type 结果集数据格式类型
     * @return Response
     */
    public function type($type)
    {
        $this->type = strtolower($type);
        return $this;
    }

    /**
     * 发送数据
     *
     * @return void
     */
    public function send()
    {
        // 获取数据
        $data = $this->getContent();
        // 输出结果前钩子
        Hook::trigger('beforSend', $data);
        // 输出头
        if (!headers_sent() && !empty($this->getHeader())) {
            // 发送状态码
            http_response_code($this->getCode());
            // 发送头部信息
            foreach ($this->getHeader() as $name => $val) {
                if (is_null($val)) {
                    header($name);
                } else {
                    header($name . ':' . $val);
                }
            }
        }
        // 输出数据
        echo $data;
        // 输出结果后钩子
        Hook::trigger('afterSend', $data);
        // fastcgi提高页面响应
        if (function_exists('fastcgi_finish_request')) {
            fastcgi_finish_request();
        }

        exit();
    }

    /**
     * 格式化获取输出数据
     *
     * @return string
     */
    public function getContent()
    {
        switch ($this->type) {
            case 'json':
                $content = $this->toJson();
                break;
            case 'xml':
                $content = $this->toXML();
                break;
            case 'html':
            default:
                $content = $this->toHTML();
                break;
        }

        return $content;
    }

    /**
     * 数据转换为HTML数据
     *
     * @return string
     */
    protected function toHTML()
    {
        return $this->data;
    }

    /**
     * 数据转换为json
     *
     * @return string
     */
    protected function toJson()
    {
        $data = json_encode($this->data, JSON_UNESCAPED_UNICODE);
        // 转换失败，抛出错误信息
        if ($data === false) {
            throw new ResponseException('Data conversion to json format failed,' . json_last_error_msg(), 500);
        }

        return $data;
    }

    /**
     * 数据转换为XML
     *
     * @return string
     */
    protected function toXML()
    {
        // XML根节点
        $root = App::instance()->name();
        $xml  = "<?xml version=\"1.0\" encoding=\"{$this->charset}\"?>";
        $xml .= "<{$root}>";
        $xml .= Tool::instance()->dataToXML($this->data);
        $xml .= "</{$root}>";

        return $xml;
    }
}
