<?php

namespace FApi;

use mon\util\Instance;

/**
 * 请求类
 *
 * @author Mon <985558837@qq.com>
 * @version v2.0 2017-11-28
 * @version v2.0.1 20210304 优化代码
 */
class Request
{
    use Instance;

    /**
     * 请求类型
     *
     * @var null
     */
    public $method = null;

    /**
     * 请求域名
     *
     * @var null
     */
    public $domain = null;

    /**
     * 请求URL带uri
     *
     * @var null
     */
    public $url = null;

    /**
     * 请求URl不带uri
     *
     * @var null
     */
    public $debaseUrl = null;

    /**
     * pathinfo路径
     *
     * @var null
     */
    public $pathInfo = null;

    /**
     * 请求URI
     *
     * @var null
     */
    public $requestUri = null;

    /**
     * 根路由
     *
     * @var null
     */
    public $baseUrl = null;

    /**
     * php://input数据
     *
     * @var null
     */
    protected $input = null;

    /**
     * HTTP请求头
     *
     * @var array
     */
    protected $header = [];

    /**
     * 私有化构造方法
     */
    protected function __construct()
    {
        if (function_exists('apache_request_headers') && $result = apache_request_headers()) {
            $header = $result;
        } else {
            $header = [];
            $server = $_SERVER;
            foreach ($server as $key => $val) {
                if (0 === strpos($key, 'HTTP_')) {
                    $key = str_replace('_', '-', strtolower(substr($key, 5)));
                    $header[$key] = $val;
                }
            }
            if (isset($server['CONTENT_TYPE'])) {
                $header['content-type'] = $server['CONTENT_TYPE'];
            }
            if (isset($server['CONTENT_LENGTH'])) {
                $header['content-length'] = $server['CONTENT_LENGTH'];
            }
        }

        $this->header = array_change_key_case($header);
        $this->input = file_get_contents('php://input');
    }

    /**
     * 获取传参
     *
     * @param string $name      参数键名
     * @param mixed  $default   默认值
     * @param boolean $filter   是否过滤参数
     * @return mixed
     */
    public function params($name = '', $default = null, $filter = true)
    {
        $result = empty($name) ? $_REQUEST : $this->getData($_REQUEST, $name, $default);

        return $filter ? $this->filter($result) : $result;
    }

    /**
     * 获取GET数据
     *
     * @param string $name      参数键名
     * @param mixed  $default   默认值
     * @param boolean $filter   是否过滤参数
     * @return mixed
     */
    public function get($name = '', $default = null, $filter = true)
    {
        $result = empty($name) ? $_GET : $this->getData($_GET, $name, $default);

        return $filter ? $this->filter($result) : $result;
    }

    /**
     * 获取POST数据
     *
     * @param string $name      参数键名
     * @param mixed  $default   默认值
     * @param boolean $filter   是否过滤参数
     * @return mixed
     */
    public function post($name = '', $default = null, $filter = true)
    {
        $result = empty($name) ? $_POST : $this->getData($_POST, $name, $default);

        return $filter ? $this->filter($result) : $result;
    }

    /**
     * 获取input输入数据
     *
     * @return mixed
     */
    public function input($name = '', $default = null, $filter = true)
    {
        $data = $this->getInputData();
        $result = empty($name) ? $data : $this->getData($data, $name, $default);

        return $filter ? $this->filter($result) : $result;
    }

    /**
     * 获取$_SERVER数据
     *
     * @param  string $name    参数键名
     * @param  mixed  $default 默认值
     * @return mixed
     */
    public function server($name = '', $default = null)
    {
        return empty($name) ? $_SERVER : $this->getData($_SERVER, $name, $default);
    }

    /**
     * 获取当前的Header
     *
     * @param  string $name    header名称
     * @param  mixed  $default 默认值
     * @return string|array
     */
    public function header($name = '', $default = null)
    {
        if ('' === $name) {
            return $this->header;
        }

        $name = str_replace('_', '-', strtolower($name));

        return isset($this->header[$name]) ? $this->header[$name] : $default;
    }

    /**
     * 当前请求 HTTP_CONTENT_TYPE
     *
     * @return string
     */
    public function getContentType()
    {
        $contentType = $this->header('Content-Type');

        if ($contentType) {
            if (strpos($contentType, ';')) {
                $typeArr = explode(';', $contentType);
                $type = $typeArr[0];
            } else {
                $type = $contentType;
            }
            return trim($type);
        }

        return '';
    }

    /**
     * 当前URL地址中的scheme参数
     *
     * @return string
     */
    public function scheme()
    {
        return $this->isSsl() ? 'https' : 'http';
    }

    /**
     * 当前请求的host
     *
     * @return string
     */
    public function host()
    {
        return $this->server('HTTP_HOST');
    }

    /**
     * 获取请求来源地址
     *
     * @return string
     */
    public function referer()
    {
        return $this->server('HTTP_REFERER');
    }

    /**
     * 获取客户端的IP地址
     *
     * @return string
     */
    public function ip()
    {
        foreach (['X_FORWARDED_FOR', 'HTTP_X_FORWARDED_FOR', 'CLIENT_IP', 'REMOTE_ADDR'] as $key) {
            if (isset($_SERVER[$key])) {
                return $_SERVER[$key];
            }
        }

        return '';
    }

    /**
     * 获取请求类型
     *
     * @return string
     */
    public function method()
    {
        if (is_null($this->method)) {
            if (isset($_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE'])) {
                $method = $_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE'];
            } else {
                // 默认GET方法访问
                $method = isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : 'GET';
            }

            $this->method = $method;
        }

        return $this->method;
    }

    /**
     * 判断是否存在某个请求参数
     *
     * @param string $name  参数名
     * @param string $type  请求类型，支持params、get、post、input、server、header等类型
     * @param boolean $checkEmpty 是否验证空字符串
     * @return boolean
     */
    public function has($name, $type, $checkEmpty = false)
    {
        if (!in_array($type, ['params', 'get', 'post', 'input', 'server', 'header'])) {
            return false;
        }
        $data = $this->$type();
        // 按.拆分成多维数组进行判断
        foreach (explode('.', $name) as $val) {
            if (isset($data[$val])) {
                $data = $data[$val];
            } else {
                return false;
            }
        }

        return ($checkEmpty && '' === $data) ? false : true;
    }

    /**
     * 数据安全过滤，采用filter_var函数
     * 
     * @param  mixed $input 过滤的数据
     * @return mixed
     */
    public function filter($input)
    {
        if (is_array($input)) {
            return filter_var_array((array)$input, FILTER_SANITIZE_STRING);
        }

        return filter_var($input, FILTER_SANITIZE_STRING);
    }

    /**
     * 当前是否Ajax请求
     *
     * @return boolean
     */
    public function isAjax()
    {
        $value  = $this->server('HTTP_X_REQUESTED_WITH');

        return (strtolower($value) == 'xmlhttprequest') ? true : false;
    }

    /**
     * 是否GET请求
     *
     * @return boolean
     */
    public function isGet()
    {
        return $this->method() === 'GET' ? true : false;
    }

    /**
     * 是否POST请求
     *
     * @return boolean
     */
    public function isPost()
    {
        return $this->method() === 'POST' ? true : false;
    }

    /**
     * 是否PUT请求
     *
     * @return boolean
     */
    public function isPut()
    {
        return $this->method() === 'PUT' ? true : false;
    }

    /**
     * 是否DELETE请求
     *
     * @return boolean
     */
    public function isDelete()
    {
        return $this->method() === 'DELETE' ? true : false;
    }

    /**
     * 是否PATCH请求
     *
     * @return boolean
     */
    public function isPatch()
    {
        return $this->method() === 'PATCH' ? true : false;
    }

    /**
     * 是否HEAD请求
     *
     * @return boolean
     */
    public function isHead()
    {
        return $this->method() === 'HEAD' ? true : false;
    }

    /**
     * 是否OPTIONS请求
     *
     * @return boolean
     */
    public function isOptions()
    {
        return $this->method() === 'OPTIONS' ? true : false;
    }

    /**
     * 检测是否使用手机访问
     *
     * @return boolean
     */
    public function isMobile()
    {
        if (isset($_SERVER['HTTP_VIA']) && stristr($_SERVER['HTTP_VIA'], "wap")) {
            return true;
        } elseif (isset($_SERVER['HTTP_ACCEPT']) && strpos(strtoupper($_SERVER['HTTP_ACCEPT']), "VND.WAP.WML")) {
            return true;
        } elseif (isset($_SERVER['HTTP_X_WAP_PROFILE']) || isset($_SERVER['HTTP_PROFILE'])) {
            return true;
        } elseif (isset($_SERVER['HTTP_USER_AGENT']) && preg_match('/(blackberry|configuration\/cldc|hp |hp-|htc |htc_|htc-|iemobile|kindle|midp|mmp|motorola|mobile|nokia|opera mini|opera |Googlebot-Mobile|YahooSeeker\/M1A1-R2D2|android|iphone|ipod|mobi|palm|palmos|pocket|portalmmm|ppc;|smartphone|sonyericsson|sqh|spv|symbian|treo|up.browser|up.link|vodafone|windows ce|xda |xda_)/i', $_SERVER['HTTP_USER_AGENT'])) {
            return true;
        }

        return false;
    }

    /**
     * 当前是否ssl
     *
     * @return boolean
     */
    public function isSsl()
    {
        if (isset($_SERVER['HTTPS']) && ('1' == $_SERVER['HTTPS'] || 'on' == strtolower($_SERVER['HTTPS']))) {
            return true;
        } elseif (isset($_SERVER['REQUEST_SCHEME']) && 'https' == $_SERVER['REQUEST_SCHEME']) {
            return true;
        } elseif (isset($_SERVER['SERVER_PORT']) && ('443' == $_SERVER['SERVER_PORT'])) {
            return true;
        } elseif (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && 'https' == $_SERVER['HTTP_X_FORWARDED_PROTO']) {
            return true;
        }

        return false;
    }

    /**
     * 获取当前请求的域名
     *
     * @return string
     */
    public function domain()
    {
        if (is_null($this->domain)) {
            $this->domain = $this->scheme() . '://' . $this->host();
        }

        return $this->domain;
    }

    /**
     * 获取当前完整URL,包括QUERY_STRING
     *
     * @return string
     */
    public function url()
    {
        if (is_null($this->url)) {
            if (isset($_SERVER['HTTP_X_REWRITE_URL'])) {
                $this->url = $_SERVER['HTTP_X_REWRITE_URL'];
            } elseif (isset($_SERVER['REQUEST_URI'])) {
                $this->url = $_SERVER['REQUEST_URI'];
            } elseif (isset($_SERVER['ORIG_PATH_INFO'])) {
                $this->url = $_SERVER['ORIG_PATH_INFO'] . (!empty($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : '');
            } else {
                $this->url = '';
            }
        }

        return $this->domain() . $this->url;
    }

    /**
     * 获取当前URL,不包括QUERY_STRING
     *
     * @return string
     */
    public function debaseUrl()
    {
        if (!$this->debaseUrl) {
            $str = $this->url();
            $request = parse_url($str);
            $this->debaseUrl = $request['path'];
        }

        return $this->domain() . $this->debaseUrl;
    }

    /**
     * 获取请求的 URI
     *
     * @return string
     */
    public function uri()
    {
        if (is_null($this->requestUri)) {
            $this->requestUri = $this->detectUrl();
        }

        return $this->requestUri;
    }

    /**
     * 获取请求的 PATH_INFO
     *
     * @return string
     */
    public function pathInfo()
    {
        if (is_null($this->pathInfo)) {
            $pathInfo = $this->detectPathInfo();
            // 去除重复的"/"
            $this->pathInfo = preg_replace('/[\/]+/', '/', $pathInfo);
        }

        return $this->pathInfo ? $this->pathInfo : '/';
    }

    /**
     * 获取根地址
     *
     * 自动检测从请求环境的基本URL
     * 采用了多种标准, 以检测请求的基本URL
     * 例子：/site/demo/index.php
     *
     * @param boolean $raw 是否编码
     * @return string
     */
    public function baseUrl($raw = false)
    {
        if (is_null($this->baseUrl)) {
            $this->baseUrl = rtrim($this->detectBaseUrl(), '/');
        }

        return $raw == false ? urldecode($this->baseUrl) : $this->baseUrl;
    }

    /**
     * 检测 baseURL 和查询字符串之间的 PATH_INFO
     *
     * @return string
     */
    protected function detectPathInfo()
    {
        // 如果已经包含 PATH_INFO
        if (!empty($_SERVER['PATH_INFO'])) {
            return $_SERVER['PATH_INFO'];
        }
        if ('/' === ($requestUri = $this->uri())) {
            return '';
        }

        $baseUrl = $this->baseUrl();
        $baseUrlEncoded = urlencode($baseUrl);

        if ($pos = strpos($requestUri, '?')) {
            $requestUri = substr($requestUri, 0, $pos);
        }

        if (!empty($baseUrl)) {
            if (strpos($requestUri, $baseUrl) === 0) {
                $pathInfo = substr($requestUri, strlen($baseUrl));
            } elseif (strpos($requestUri, $baseUrlEncoded) === 0) {
                $pathInfo = substr($requestUri, strlen($baseUrlEncoded));
            } else {
                $pathInfo = $requestUri;
            }
        } else {
            $pathInfo = $requestUri;
        }

        return $pathInfo;
    }

    /**
     * 自动检测从请求环境的基本 URL
     * 采用了多种标准, 以检测请求的基本 URL
     *
     * @return string
     */
    protected function detectBaseUrl()
    {
        $baseUrl        = '';
        $fileName       = isset($_SERVER['SCRIPT_FILENAME']) ? $_SERVER['SCRIPT_FILENAME'] : '';
        $scriptName     = isset($_SERVER['SCRIPT_NAME']) ? $_SERVER['SCRIPT_NAME'] : null;
        $phpSelf        = isset($_SERVER['PHP_SELF']) ? $_SERVER['PHP_SELF'] : null;
        $origScriptName = isset($_SERVER['ORIG_SCRIPT_NAME']) ? $_SERVER['ORIG_SCRIPT_NAME'] : null;

        if ($scriptName !== null && basename($scriptName) === $fileName) {
            $baseUrl = $scriptName;
        } elseif ($phpSelf !== null && basename($phpSelf) === $fileName) {
            $baseUrl = $phpSelf;
        } elseif ($origScriptName !== null && basename($origScriptName) === $fileName) {
            $baseUrl = $origScriptName;
        } else {
            $baseUrl  = '/';
            $basename = basename($fileName);
            if ($basename) {
                $path     = ($phpSelf ? trim($phpSelf, '/') : '');
                $baseUrl .= substr($path, 0, strpos($path, $basename)) . $basename;
            }
        }

        // 请求的URI
        $requestUri = $this->uri();
        // 与请求的URI一样?
        if (0 === strpos($requestUri, $baseUrl)) {
            return $baseUrl;
        }

        $baseDir = str_replace('\\', '/', dirname($baseUrl));
        if (0 === strpos($requestUri, $baseDir)) {
            return $baseDir;
        }

        $basename = basename($baseUrl);
        if (empty($basename)) {
            return '';
        }

        if (strlen($requestUri) >= strlen($baseUrl) && (false !== ($pos = strpos($requestUri, $baseUrl)) && $pos !== 0)) {
            $baseUrl = substr($requestUri, 0, $pos + strlen($baseUrl));
        }

        return $baseUrl;
    }

    /**
     * 检测请求的URL, 获取URI
     *
     * @return string
     */
    protected function detectUrl()
    {
        if (isset($_SERVER['HTTP_X_ORIGINAL_URL'])) {
            // 带微软重写模块的IIS
            $requestUri = $_SERVER['HTTP_X_ORIGINAL_URL'];
        } elseif (isset($_SERVER['HTTP_X_REWRITE_URL'])) {
            // 带ISAPI_Rewrite的IIS
            $requestUri = $_SERVER['HTTP_X_REWRITE_URL'];
        } elseif (isset($_SERVER['IIS_WasUrlRewritten']) && $_SERVER['IIS_WasUrlRewritten'] == '1' && isset($_SERVER['UNENCODED_URL']) && $_SERVER['UNENCODED_URL'] != '') {
            // URL重写的IIS7：确保我们得到的未编码的URL(双斜杠的问题)
            $requestUri = $_SERVER['UNENCODED_URL'];
        } elseif (isset($_SERVER['REQUEST_URI'])) {
            $requestUri = $_SERVER['REQUEST_URI'];
            // 只使用URL路径, 不包含scheme、主机[和端口]或者http代理
            if ($requestUri) {
                $requestUri = preg_replace('#^[^/:]+://[^/]+#', '', $requestUri);
            }
        } elseif (isset($_SERVER['ORIG_PATH_INFO'])) {
            // IIS 5.0, CGI
            $requestUri = $_SERVER['ORIG_PATH_INFO'];
            if (!empty($_SERVER['QUERY_STRING'])) {
                $requestUri .= '?' . $_SERVER['QUERY_STRING'];
            }
        } else {
            $requestUri = '/';
        }

        return $requestUri;
    }

    /**
     * 获取数据, 支持通过'.'分割获取无限级节点数据
     *
     * @param  array  $data 数据源
     * @param  string $name 字段名
     * @param  mixed  $default 默认值
     * @return mixed
     */
    protected function getData(array $data, $name, $default = null)
    {
        foreach (explode('.', $name) as $val) {
            if (isset($data[$val])) {
                $data = $data[$val];
            } else {
                return $default;
            }
        }

        return $data;
    }

    /**
     * 获取input数据
     *
     * @param string $data input数据集
     * @return array
     */
    protected function getInputData($data = '')
    {
        $data = $data ?: $this->input;
        $contentType = $this->getContentType();
        // 判断请求数据类型，转换数据格式
        if ('application/x-www-form-urlencoded' == $contentType) {
            parse_str($data, $data);
            return $data;
        } elseif (false !== strpos($contentType, 'json')) {
            return (array) json_decode($data, true);
        }

        return [];
    }
}
