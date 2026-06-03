<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
$routes->get('/', 'Home::index');

$routes->options('api/(:any)', static function () {
    return service('response')
        ->setStatusCode(204)
        ->setHeader('Access-Control-Allow-Origin', $_SERVER['HTTP_ORIGIN'] ?? 'http://127.0.0.1:5173')
        ->setHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With, X-API-Key')
        ->setHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, PATCH, DELETE, OPTIONS')
        ->setHeader('Access-Control-Max-Age', '7200');
});

$routes->group('api', ['filter' => 'cors'], static function ($routes) {
    $routes->group('', ['filter' => 'apiKey'], static function ($routes) {
        $routes->get('categories', 'Api\CategoriesController::index');
        $routes->get('categories/(:num)', 'Api\CategoriesController::show/$1');
        $routes->post('categories', 'Api\CategoriesController::create');
        $routes->put('categories/(:num)', 'Api\CategoriesController::update/$1');
        $routes->patch('categories/(:num)', 'Api\CategoriesController::update/$1');
        $routes->delete('categories/(:num)', 'Api\CategoriesController::delete/$1');
        $routes->post('categories/(:num)/unlock', 'Api\CategoriesController::unlock/$1');

        $routes->get('todos', 'Api\TodosController::index');
        $routes->get('todos/(:num)', 'Api\TodosController::show/$1');
        $routes->post('todos', 'Api\TodosController::create');
        $routes->put('todos/(:num)', 'Api\TodosController::update/$1');
        $routes->patch('todos/(:num)', 'Api\TodosController::update/$1');
        $routes->delete('todos/(:num)', 'Api\TodosController::delete/$1');

        $routes->post('auth/login', 'Api\AuthController::login');
        $routes->get('protected', 'Api\ProtectedController::index', ['filter' => 'jwtAuth']);
    });
});
