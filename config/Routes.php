<?php

declare(strict_types=1);

use App\Controller\Api\HealthCheckController;
use App\Controller\HomeController;
use App\Controller\User\CreateUserController;
use App\Controller\User\ListUsersController;
use Slim\App;
use Slim\Routing\RouteCollectorProxy;

return function (App $app) {
    $app->get('/', HomeController::class)->setName('home');

    // Users (Twig HTML & JSON API support)
    $app->group('/users', function (RouteCollectorProxy $group) {
        $group->get('', ListUsersController::class)->setName('users.list');
        $group->post('', CreateUserController::class)->setName('users.create');
    });

    // API Routes
    $app->group('/api', function (RouteCollectorProxy $group) {
        $group->get('/health', HealthCheckController::class)->setName('api.health');
        $group->get('/users', ListUsersController::class)->setName('api.users.list');
        $group->post('/users', CreateUserController::class)->setName('api.users.create');
    });
};
