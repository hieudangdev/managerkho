<?php


use Illuminate\Routing\Router;

Admin::routes();

Route::group([
    'prefix'        => config('admin.route.prefix'),
    'namespace'     => config('admin.route.namespace'),
    'middleware'    => config('admin.route.middleware'),
    'as'            => config('admin.route.prefix') . '.',
], function (Router $router) {

    $router->get('/', 'HomeController@index')->name('home');
    $router->resource('domains', DomainController::class);
    $router->resource('campaigns', CampaignController::class);
    $router->resource('tvcs', TvcController::class);
    $router->resource('banners', BannerController::class);

    $router->resource('bookmakers', BookmakerController::class);
    $router->resource('categorydomains', CategoryDomainController::class);
    $router->resource('movies', MoviesController::class);
    $router->resource('categories', CategoryController::class);
    $router->resource('episodes', EpisodeController::class);
    $router->resource('netlink', NetlinkController::class);
});
