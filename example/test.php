<?php
/**
 * 路由DEMO
 */
require '../vendor/autoload.php';

// 获取应用实例
$app = \FApi\App::instance()->init(true);

class T{
    public function handler()
    {
        return true;
    }
}

$befor = [
    function($var, $app){
        $app->vars['a'] = 333;
        return true;
    },
    T::class
];

$after = [
    function($res, $app){
        var_dump($res);
        return $res;
    }
];

$app->route->get(['path' => '/', 'befor' => $befor, 'after' => $after], function($a){
    return $a;
});

$app->run()->send();