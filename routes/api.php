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

});
