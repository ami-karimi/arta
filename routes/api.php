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
Route::get('/login', function(){
    return \response()->json(['status' => false,'message' => '403 forbidden Login'],403);

});



Route::middleware(['auth:api'])->group(function () {
    Route::get('/user', [\App\Http\Controllers\AuthController::class, 'me']);

    // Admin Route
    Route::middleware(['is_admin'])->group(function () {

        Route::post('/logout', [\App\Http\Controllers\AuthController::class, 'logout'])->name('logout');



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
            Route::get('/activity/{id}', [\App\Http\Controllers\Admin\UserController::class, 'getActivity']);
            Route::post('/ReChargeAccount/{username}', [\App\Http\Controllers\Admin\UserController::class, 'ReChargeAccount']);
            Route::post('/groupdelete', [\App\Http\Controllers\Admin\UserController::class, 'groupdelete']);
            Route::post('/group_recharge', [\App\Http\Controllers\Admin\UserController::class, 'group_recharge']);
            Route::post('/group_deactive', [\App\Http\Controllers\Admin\UserController::class, 'group_deactive']);
            Route::post('/group_active', [\App\Http\Controllers\Admin\UserController::class, 'group_active']);
            Route::post('/change_group_id', [\App\Http\Controllers\Admin\UserController::class, 'change_group_id']);
            Route::post('/change_creator', [\App\Http\Controllers\Admin\UserController::class, 'change_creator']);
            Route::get('/activitys', [\App\Http\Controllers\Admin\UserController::class, 'getActivityAll']);
            Route::get('/AcctSaveds', [\App\Http\Controllers\Admin\UserController::class, 'AcctSaved']);
            Route::get('/AcctSavedView', [\App\Http\Controllers\Admin\UserController::class, 'AcctSavedView']);

        });

        Route::prefix('radius')->group(function () {
            Route::post('/radlog', [\App\Http\Controllers\Admin\RadiusController::class, 'radlog']);
            Route::post('/radauth', [\App\Http\Controllers\Admin\RadiusController::class, 'radauth']);
            Route::post('/user_report', [\App\Http\Controllers\Admin\RadiusController::class, 'radUserReport']);
        });

        Route::prefix('admins')->group(function () {
            Route::get('/list', [\App\Http\Controllers\Admin\AdminsController::class, 'index']);
            Route::get('/view/{id}', [\App\Http\Controllers\Admin\AdminsController::class, 'view']);
            Route::post('/create', [\App\Http\Controllers\Admin\AdminsController::class, 'create']);
            Route::post('/edit/{id}', [\App\Http\Controllers\Admin\AdminsController::class, 'edit']);
        });

        Route::prefix('financial')->group(function () {
            Route::get('/list', [\App\Http\Controllers\Admin\FinancialController::class, 'index']);
            Route::post('/create', [\App\Http\Controllers\Admin\FinancialController::class, 'create']);
            Route::post('/edit/{id}', [\App\Http\Controllers\Admin\FinancialController::class, 'edit']);
            Route::post('/save_custom_price/{id}', [\App\Http\Controllers\Admin\FinancialController::class, 'save_custom_price']);

        });
    });


    // Agent Routing
    Route::middleware(['is_agent'])->group(function () {
        Route::prefix('agent')->group(function () {

            Route::get('/panel', [\App\Http\Controllers\Agent\AgentController::class, 'index']);
            Route::prefix('financial')->group(function () {
                Route::get('/list', [\App\Http\Controllers\Agent\FinancialController::class, 'index']);
                Route::post('/create', [\App\Http\Controllers\Agent\FinancialController::class, 'create']);
                Route::post('/edit/{id}', [\App\Http\Controllers\Agent\FinancialController::class, 'edit']);
            });
            Route::prefix('users')->group(function () {
                Route::get('/list', [\App\Http\Controllers\Agent\UserController::class, 'index']);
                Route::post('/group_deactive', [\App\Http\Controllers\Agent\UserController::class, 'group_deactive']);
                Route::post('/group_active', [\App\Http\Controllers\Agent\UserController::class, 'group_active']);
                Route::get('/show/{id}', [\App\Http\Controllers\Agent\UserController::class, 'show']);
                Route::post('/edit/{id}', [\App\Http\Controllers\Agent\UserController::class, 'edit']);
                Route::post('/create', [\App\Http\Controllers\Agent\UserController::class, 'create']);
                Route::get('/activity/{id}', [\App\Http\Controllers\Agent\UserController::class, 'getActivity']);
                Route::POST('/ReChargeAccount/{username}', [\App\Http\Controllers\Agent\UserController::class, 'ReChargeAccount']);
                Route::get('/activitys', [\App\Http\Controllers\Agent\UserController::class, 'getActivityAll']);

            });
            Route::prefix('radius')->group(function () {
                Route::post('/radlog', [\App\Http\Controllers\Admin\RadiusController::class, 'radlog']);
                Route::post('/radauth', [\App\Http\Controllers\Admin\RadiusController::class, 'radauth']);
                Route::post('/user_report', [\App\Http\Controllers\Admin\RadiusController::class, 'radUserReport']);
            });


        });
    });


});
