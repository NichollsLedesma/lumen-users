<?php

/** @var \Laravel\Lumen\Routing\Router $router */

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
*/

use App\Http\Controllers\UserController;

$router->get('/', function () use ($router) {
    return $router->app->version();
});

$router->group(["prefix" => "api/v1"], function () use ($router) {
    $router->post("/register", "AuthController@register");
    $router->get("/confirm-email", "AuthController@confirmEmail");
    $router->get("/resend-email", "AuthController@resendEmail");
    $router->post("/login", "AuthController@login");

    $router->group(["middleware" => "auth"], function () use ($router) {
        $router->get("/me", "AuthController@me");

        $router->group(["prefix" => "users"], function () use ($router) {
            $router->get("/", "UserController@getAll");
            $router->get("/{userId}", "UserController@getOne");
            $router->put("/{userId}", "UserController@update");
            $router->delete("/{userId}", "UserController@remove");
        });
    });
});
