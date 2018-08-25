<?php 
return array (
  'fast' => 
  array (
    0 => 
    array (
      'GET' => 
      array (
        '/' => '9bdf95c21d27a1d3e27f6c29c74e05b7',
        '*' => '1c74feb72f145f8cfdf8dd259f1d12c4',
      ),
      'POST' => 
      array (
        '*' => '1c74feb72f145f8cfdf8dd259f1d12c4',
      ),
      'PUT' => 
      array (
        '*' => '1c74feb72f145f8cfdf8dd259f1d12c4',
      ),
      'PATCH' => 
      array (
        '*' => '1c74feb72f145f8cfdf8dd259f1d12c4',
      ),
      'DELETE' => 
      array (
        '*' => '1c74feb72f145f8cfdf8dd259f1d12c4',
      ),
      'OPTIONS' => 
      array (
        '*' => '1c74feb72f145f8cfdf8dd259f1d12c4',
      ),
    ),
    1 => 
    array (
    ),
  ),
  'api' => 
  array (
    '9bdf95c21d27a1d3e27f6c29c74e05b7' => 
    array (
      'middleware' => 'midd',
      'callback' => 'Demo@index',
      'append' => NULL,
    ),
    '1c74feb72f145f8cfdf8dd259f1d12c4' => 
    array (
      'middleware' => NULL,
      'callback' => function(){
    	echo '*';
    },
      'append' => NULL,
    ),
  ),
);