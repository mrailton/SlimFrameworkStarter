<?php

declare(strict_types=1);

use App\Controllers\IndexController;
use Slim\App;

return function (App $app): void {
    $app->get('/', IndexController::class)->setName('home');
};
