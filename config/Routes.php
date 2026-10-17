<?php

declare(strict_types=1);

use App\Controller\Auth\LoginController;
use App\Controller\Auth\LogoutController;
use App\Controller\Auth\StoreLoginController;
use App\Controller\IndexController;
use Slim\App;

return function (App $app): void {
    $app->get('/', IndexController::class)->setName('home');

    $app->get('/login', LoginController::class)->setName('login');
    $app->post('/login', StoreLoginController::class)->setName('login.post');
    $app->get('/logout', LogoutController::class)->setName('logout');
};
