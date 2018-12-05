<?php

use Illuminate\Routing\Router;
use App\Models\User;
use App\Models\MinaCategory;
use App\Models\MinaInfo;

Admin::registerAuthRoutes();

Route::group([
    'prefix'        => config('admin.route.prefix'),
    'namespace'     => config('admin.route.namespace'),
    'middleware'    => config('admin.route.middleware'),
], function (Router $router) {
    $router->get('/', 'HomeController@index');
    $router->resource("users",UsersController::Class);
    $router->resource("mina_info",MinaInfoController::Class);
    $router->resource("invite_info",InviteInfoController::Class);
    $router->resource("complain",ComplainController::Class);
    $router->resource("advert",AdvertController::class);
});
Route::get('/api/userNickName', function () {
    return User::select('id','nick_name AS text')->get()->toArray();
});
Route::get('/api/minaCategoryName', function () {
    return MinaCategory::select('id','name AS text')->get()->toArray();
});
Route::get('/api/minaInfoName', function () {
    return MinaInfo::select('id','name AS text')->get()->toArray();
});