<?php

declare(strict_types=1);
use App\Controller\Auth\LoginController;
use App\Controller\Auth\LogoutController;
use App\Controller\Auth\ShowLoginController;
use App\Controller\HomeController;
use App\Controller\User\CreateUserController;
use App\Controller\User\ListUsersController;
use App\Middleware\AuthMiddleware;
use Slim\App;
use Slim\Routing\RouteCollectorProxy;

return function (App $app): void {
    $app->get('/', HomeController::class)->setName('home');

    $app->get('/login', ShowLoginController::class)->setName('auth.login.show');
    $app->post('/login', LoginController::class)->setName('auth.login');
    $app->map(['GET', 'POST'], '/logout', LogoutController::class)->setName('auth.logout');

    $app->group('/users', function (RouteCollectorProxy $group): void {
        $group->get('', ListUsersController::class)->setName('users.list');
        $group->post('', CreateUserController::class)->setName('users.create');
    })->add(AuthMiddleware::class);
};
