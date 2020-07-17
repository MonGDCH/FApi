<?php

namespace FApi;

use Closure;
use ReflectionFunction;
use FastRoute\RouteCollector;
use FastRoute\RouteParser\Std;
use FastRoute\DataGenerator\GroupCountBased;
use FastRoute\Dispatcher\GroupCountBased as Dispatcher;

/**
 * 路由封装
 *
 * @author Mon <985558837@qq.com>
 * @version v3.0 2019-12-21 支持数组定义多个中间件定义
 */
class Route
{
    /**
     * 对象单例
     *
     * @var Route
     */
    protected static $instance;

    /**
     * fast-route路由容器
     *
     * @var RouteCollector
     */
    protected $collector;

    /**
     * 路由信息
     *
     * @var array
     */
    protected $data = [];

    /**
     * 路由组前缀
     *
     * @var string
     */
    protected $groupPrefix = '';

    /**
     * 回调命名空间前缀
     *
     * @var string
     */
    protected $prefix = '';

    /**
     * 路由前置回调(前置中间件)
     *
     * @var array
     */
    protected $befor = [];

    /**
     * 路由后置回调(后置中间件)
     *
     * @var array
     */
    protected $after = [];

    /**
     * 私有化构造方法
     */
    private function __construct()
    {
    }

    /**
     * 获取实例
     *
     * @return Route
     */
    public static function instance()
    {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * 设置路由数据
     *
     * @param array $data 路由数据
     * @return void
     */
    public function setData(array $data)
    {
        $this->data = $data;
    }

    /**
     * 获取路由数据
     *
     * @return array
     */
    public function getData()
    {
        return $this->data ?: $this->collector()->getData();
    }

    /**
     * 获取fast-route路由容器
     *
     * @return RouteCollector
     */
    public function collector()
    {
        if (is_null($this->collector)) {
            $this->collector = new RouteCollector(new Std, new GroupCountBased);
        }

        return $this->collector;
    }

    /**
     * 注册GET路由
     *
     * @param  string $pattern  请求模式
     * @param  mixed  $callback 路由回调
     * @return Route
     */
    public function get($pattern, $callback)
    {
        return $this->map(['GET'], $pattern, $callback);
    }

    /**
     * 注册POST路由
     *
     * @param  string $pattern  请求模式
     * @param  mixed  $callback 路由回调
     * @return Route
     */
    public function post($pattern, $callback)
    {
        return $this->map(['POST'], $pattern, $callback);
    }

    /**
     * 注册PUT路由
     *
     * @param  string $pattern  请求模式
     * @param  mixed  $callback 路由回调
     * @return Route
     */
    public function put($pattern, $callback)
    {
        return $this->map(['PUT'], $pattern, $callback);
    }

    /**
     * 注册PATCH路由
     *
     * @param  string $pattern  请求模式
     * @param  mixed  $callback 路由回调
     * @return Route
     */
    public function patch($pattern, $callback)
    {
        return $this->map(['PATCH'], $pattern, $callback);
    }

    /**
     * 注册DELETE路由
     *
     * @param  string $pattern  请求模式
     * @param  mixed  $callback 路由回调
     * @return Route
     */
    public function delete($pattern, $callback)
    {
        return $this->map(['DELETE'], $pattern, $callback);
    }

    /**
     * 注册OPTIONS路由
     *
     * @param  string $pattern  请求模式
     * @param  mixed  $callback 路由回调
     * @return Route
     */
    public function options($pattern, $callback)
    {
        return $this->map(['OPTIONS'], $pattern, $callback);
    }

    /**
     * 注册任意请求方式的路由
     *
     * @param  string $pattern  请求模式
     * @param  mixed  $callback 路由回调
     * @return Route
     */
    public function any($pattern, $callback)
    {
        return $this->map(['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'], $pattern, $callback);
    }

    /**
     * 注册组别路由
     *
     * @param  string  $pattern  路由前缀
     * @param  Closure $callback 路由回调
     * @return void
     */
    public function group($pattern, Closure $callback)
    {
        $groupPrefix = $this->groupPrefix;
        $prefix = $this->prefix;
        $befor  = $this->befor;
        $after  = $this->after;

        $parse = $this->parsePattern($pattern);
        $this->groupPrefix .= $parse['path'];
        $this->prefix = $parse['namespace'];
        $this->befor  = $parse['befor'];
        $this->after  = $parse['after'];

        call_user_func($callback, $this);

        $this->groupPrefix = $groupPrefix;
        $this->prefix = $prefix;
        $this->befor  = $befor;
        $this->after  = $after;
    }

    /**
     * 注册路由方法
     *
     * @param  array $method   请求方式
     * @param  mixed $pattern  请求模式
     * @param  mixed $callback 路由回调
     * @return Route
     */
    public function map(array $method, $pattern, $callback)
    {
        $parse = $this->parsePattern($pattern);
        // 获取请求路径
        $path = $this->groupPrefix . $parse['path'];
        // 获取请求回调
        if (is_string($callback)) {
            $callback = (!empty($parse['namespace']) ? $parse['namespace'] : $this->prefix) . $callback;
        }
        // 所有值转大写
        $method = array_map('strtoupper', $method);

        $result = [
            'befor'    => $parse['befor'],
            'callback' => $callback,
            'after'    => $parse['after']
        ];
        // 注册fast-route路由表
        $this->collector()->addRoute($method, $path, $result);

        return $this;
    }

    /**
     * 解析请求模式
     *
     * @param  mixed $pattern 路由参数
     * @return array
     */
    protected function parsePattern($pattern)
    {
        $res = [
            // 路由路径或者路由前缀
            'path'      => '',
            // 命名空间
            'namespace' => $this->prefix,
            // 中间件
            'befor'     => $this->befor,
            // 后置件
            'after'     => $this->after,
        ];
        if (is_string($pattern)) {
            // 字符串，标示请求路径
            $res['path'] = $pattern;
        } elseif (is_array($pattern)) {
            // 数组，解析配置
            if (isset($pattern['path']) && !empty($pattern['path'])) {
                $res['path'] = $pattern['path'];
            }
            if (isset($pattern['namespace']) && !empty($pattern['namespace'])) {
                $res['namespace'] = $pattern['namespace'];
            }
            if (isset($pattern['befor']) && !empty($pattern['befor'])) {
                $res['befor'] = array_merge((array) $this->befor, (array) $pattern['befor']);
            }
            if (isset($pattern['after']) && !empty($pattern['after'])) {
                $res['after'] = array_merge((array) $this->after, (array) $pattern['after']);
            }
        }

        return $res;
    }

    /**
     * 执行路由
     *
     * @param  string $method 请求类型
     * @param  string $path   请求路径
     * @return array
     */
    public function dispatch($method, $path)
    {
        if (empty($this->data)) {
            $this->data = $this->collector()->getData();
        }
        $dispatch = new Dispatcher($this->data);

        return $dispatch->dispatch($method, $path);
    }

    /**
     * 获取路由缓存结果集,或者缓存路由
     *
     * @param  string $path 缓存文件路径，存在缓存路径则输出缓存文件
     * @return mixed
     */
    public function cache($path = '')
    {
        $data = $this->getData();
        array_walk_recursive($data, [$this, 'buildClosure']);
        $content = var_export($data, true);
        $content = str_replace(['\'[__start__', '__end__]\''], '', stripcslashes($content));
        // 不存在缓存文件路径，返回缓存结果集
        if (empty($path)) {
            return $content;
        }
        // 缓存路由文件
        $cache = '<?php ' . PHP_EOL . 'return ' . $content . ';';
        return file_put_contents($path, $cache);
    }

    /**
     * 生成路由内容
     *
     * @param  mixed  &$value 路由内容
     * @return void
     */
    protected function buildClosure(&$value)
    {
        if ($value instanceof Closure) {
            $reflection = new ReflectionFunction($value);
            $startLine  = $reflection->getStartLine();
            $endLine    = $reflection->getEndLine();
            $file       = $reflection->getFileName();
            $item       = file($file);
            $content    = '';
            for ($i = $startLine - 1, $j = $endLine - 1; $i <= $j; $i++) {
                $content .= $item[$i];
            }
            $start = strpos($content, 'function');
            $end   = strrpos($content, '}');
            $value = '[__start__' . substr($content, $start, $end - $start + 1) . '__end__]';
        }
    }
}
