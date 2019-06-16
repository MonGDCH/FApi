<?php 
return array (
  0 => 
  array (
    'GET' => 
    array (
      '' => 
      array (
        'middleware' => 'midd',
        'callback' => 'Demo@index',
        'append' => NULL,
      ),
      '*' => 
      array (
        'middleware' => NULL,
        'callback' => function(){
    	echo '*';
    },
        'append' => NULL,
      ),
    ),
    'POST' => 
    array (
      '*' => 
      array (
        'middleware' => NULL,
        'callback' => function(){
    	echo '*';
    },
        'append' => NULL,
      ),
    ),
    'PUT' => 
    array (
      '*' => 
      array (
        'middleware' => NULL,
        'callback' => function(){
    	echo '*';
    },
        'append' => NULL,
      ),
    ),
    'PATCH' => 
    array (
      '*' => 
      array (
        'middleware' => NULL,
        'callback' => function(){
    	echo '*';
    },
        'append' => NULL,
      ),
    ),
    'DELETE' => 
    array (
      '*' => 
      array (
        'middleware' => NULL,
        'callback' => function(){
    	echo '*';
    },
        'append' => NULL,
      ),
    ),
    'OPTIONS' => 
    array (
      '*' => 
      array (
        'middleware' => NULL,
        'callback' => function(){
    	echo '*';
    },
        'append' => NULL,
      ),
    ),
  ),
  1 => 
  array (
  ),
);