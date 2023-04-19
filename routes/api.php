<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/
Route::post('/login', [\App\Http\Controllers\AuthController::class, 'login'])->name('login');
Route::post('/logout', [\App\Http\Controllers\AuthController::class, 'logout'])->name('logout');

Route::middleware(['api'])->group(function () {

    Route::get('/user', [\App\Http\Controllers\AuthController::class, 'me']);

    Route::prefix('ras')->group(function () {
        Route::get('/list', [\App\Http\Controllers\Admin\RasController::class, 'index']);
        Route::post('/create', [\App\Http\Controllers\Admin\RasController::class, 'create']);
        Route::post('/edit/{id}', [\App\Http\Controllers\Admin\RasController::class, 'edit']);
    });

    Route::prefix('groups')->group(function () {
        Route::get('/list', [\App\Http\Controllers\Admin\GroupsController::class, 'index']);
        Route::post('/create', [\App\Http\Controllers\Admin\GroupsController::class, 'create']);
        Route::post('/edit/{id}', [\App\Http\Controllers\Admin\GroupsController::class, 'edit']);
    });

    Route::prefix('users')->group(function () {
        Route::get('/list', [\App\Http\Controllers\Admin\UserController::class, 'index']);
        Route::post('/create', [\App\Http\Controllers\Admin\UserController::class, 'create']);
        Route::post('/edit/{id}', [\App\Http\Controllers\Admin\UserController::class, 'edit']);
        Route::get('/show/{id}', [\App\Http\Controllers\Admin\UserController::class, 'show']);
        Route::post('/ReChargeAccount/{username}', [\App\Http\Controllers\Admin\UserController::class, 'ReChargeAccount']);
        Route::post('/groupdelete', [\App\Http\Controllers\Admin\UserController::class, 'groupdelete']);
        Route::post('/group_recharge', [\App\Http\Controllers\Admin\UserController::class, 'group_recharge']);
        Route::post('/group_deactive', [\App\Http\Controllers\Admin\UserController::class, 'group_deactive']);
        Route::post('/group_active', [\App\Http\Controllers\Admin\UserController::class, 'group_active']);
        Route::post('/change_group_id', [\App\Http\Controllers\Admin\UserController::class, 'change_group_id']);
        Route::post('/change_creator', [\App\Http\Controllers\Admin\UserController::class, 'change_creator']);

    });

    Route::prefix('radius')->group(function () {
        Route::post('/radlog', [\App\Http\Controllers\Admin\RadiusController::class, 'radlog']);
        Route::post('/radauth', [\App\Http\Controllers\Admin\RadiusController::class, 'radauth']);
        Route::post('/user_report', [\App\Http\Controllers\Admin\RadiusController::class, 'radUserReport']);
    });

});
