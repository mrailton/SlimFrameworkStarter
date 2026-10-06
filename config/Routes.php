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

    $app->group('/users', function (RouteCollectorProxy $group) {
        $group->get('', ListUsersController::class)->setName('users.list');
        $group->post('', CreateUserController::class)->setName('users.create');
    });
};
