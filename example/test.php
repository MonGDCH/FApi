<?php

/**
 * 路由DEMO
 */
require '../vendor/autoload.php';

// 获取应用实例
$app = \FApi\App::instance()->init(true);

class A
{
    public function handler()
    {
        var_dump(__CLASS__);
        return true;
    }
}

class T
{
    public function handler()
    {
        var_dump(__FUNCTION__);
        return true;
    }
}

class B
{
    public function handler($result, $app)
    {
        var_dump($app->getResult());
        $app->setResult('abcd');
        return true;
    }
}

$befor = [
    function ($var, $app) {
        $app->setVars(['a' => 12334]);
        var_dump($app->getVars());
        return true;
    },
    T::class
];

$after = [
    function ($res, $app) {
        // var_dump($res);
        return $app->getResult();
    }
];

$app->route->group(['befor' => A::class, 'after' => [B::class]], function ($r) use ($befor, $after) {
    $r->get(['path' => '/', 'befor' => $befor, 'after' => $after], function ($a = 123) {
        return $a;
    });
});

$app->run()->send();
