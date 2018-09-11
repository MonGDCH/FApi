<?php
require '../vendor/autoload.php';

/**
 * 对象支持
 */
class TestHook
{
	public function handler($err)
	{
		var_dump($err);
		exit();
	}
}

// 增加钩子
\FApi\Hook::register([
	'bootstrap'	=> function(){echo 'bootstrap';},
	'run'		=> function(){echo 'run';},
	'action_befor' => '',
	'action_after' => '',
	'send'		=> function($data){var_dump($data);},
	'end'		=> function(){echo 'end';},
	'error'		=> 'TestHook',
]);

$app = \FApi\App::instance(true);

$app->route->get('/', function($id = 1){
	return $id;
});

$app->run()->send();