<?php

$routes->group('admin',['namespace' => '\Modules\admin\Controllers'],function($routes){

    $routes->get('/','Dashboard_controller::dashboard' );  
    $routes->get('dashboard','Dashboard_controller::dashboard' ); 

});