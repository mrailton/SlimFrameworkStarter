<?php

declare(strict_types=1);

use App\Action\Api\HealthCheckAction;
use App\Action\HomeAction;
use App\Action\User\CreateUserAction;
use App\Action\User\ListUsersAction;
use Slim\App;
use Slim\Routing\RouteCollectorProxy;

return function (App $app) {
    $app->get('/', HomeAction::class)->setName('home');

    // Users (Twig HTML & JSON API support)
    $app->group('/users', function (RouteCollectorProxy $group) {
        $group->get('', ListUsersAction::class)->setName('users.list');
        $group->post('', CreateUserAction::class)->setName('users.create');
    });

    // API Routes
    $app->group('/api', function (RouteCollectorProxy $group) {
        $group->get('/health', HealthCheckAction::class)->setName('api.health');
        $group->get('/users', ListUsersAction::class)->setName('api.users.list');
        $group->post('/users', CreateUserAction::class)->setName('api.users.create');
    });
};
