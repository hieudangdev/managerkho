<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});
use App\Admin\Controllers\CampaignController;
use App\Admin\Controllers\TvcController;
use App\Admin\Controllers\BannerController;
use App\Admin\Controllers\NetlinkController;

Route::get('admin/campaigns/run/{id}', [CampaignController::class, 'runCampaign'])->name('campaign.run');
Route::get('netlink/{id}', [NetlinkController::class, 'run'])->name('netlink.run');
Route::get('tvcs/run/{id}', [TvcController::class, 'run'])->name('tvcs.run');
Route::get('tvcs/stop/{id}', [TvcController::class, 'stop'])->name('tvcs.stop');

Route::get('/banners/{id}/run', [BannerController::class, 'run'])->name('banner.run');
Route::get('/banners/{id}/stop', [BannerController::class, 'stop'])->name('banner.stop');


use App\Admin\Controllers\BookmakerController;
Route::post('/admin/bookmakers', [BookmakerController::class, 'createBookmaker']);


use Alexusmai\LaravelFileManager\Controllers\FileManagerController;
use App\Admin\Controllers\MoviesController;

Route::middleware(['web', 'admin.auth'])->group(function () {


    Route::post('file-manager/initialize', [FileManagerController::class, 'initialize']);
    Route::post('file-manager/upload', [FileManagerController::class, 'upload']);
    Route::post('file-manager/destroy', [FileManagerController::class, 'destroy']);
    Route::post('file-manager/paste', [FileManagerController::class, 'paste']);
    Route::post('file-manager/rename', [FileManagerController::class, 'rename']);
    Route::post('file-manager/copy', [FileManagerController::class, 'copy']);
    Route::post('file-manager/move', [FileManagerController::class, 'move']);
    Route::post('file-manager/add-folder', [FileManagerController::class, 'addFolder']);
    Route::post('file-manager/download', [FileManagerController::class, 'download']);
    Route::post('file-manager/compress', [FileManagerController::class, 'compress']);
    Route::post('file-manager/extract', [FileManagerController::class, 'extract']);
    Route::post('file-manager/change-disk', [FileManagerController::class, 'changeDisk']);
});
Route::get('movies/upload/{id}', [MoviesController::class, 'upload'])->name('movie.upload');
