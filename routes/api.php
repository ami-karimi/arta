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



Route::post('/login_user', [\App\Http\Controllers\AuthController::class, 'login_user'])->name('login_user');

Route::post('/login', [\App\Http\Controllers\AuthController::class, 'login'])->name('login');
Route::get('/login', function(){
    return \response()->json(['status' => false,'message' => '403 forbidden Login'],403);
});



Route::middleware(['auth:api'])->group(function () {

      // User Controller
       Route::prefix('user')->group(function () {

          Route::get('/detial', [\App\Http\Controllers\User\UserController::class, 'index']);
          Route::POST('/edit_password', [\App\Http\Controllers\User\UserController::class, 'edit_password']);
          Route::get('/auth_log', [\App\Http\Controllers\User\UserController::class, 'auth_log']);
          Route::get('/get_servers', [\App\Http\Controllers\User\UserController::class, 'get_servers']);
          Route::get('/get_groups', [\App\Http\Controllers\User\UserController::class, 'get_groups']);
          Route::get('/get_group', [\App\Http\Controllers\User\UserController::class, 'get_group']);


          Route::prefix('financial')->group(function () {
              Route::POST('/create', [\App\Http\Controllers\User\FinancialController::class, 'create']);
              Route::get('/list', [\App\Http\Controllers\User\FinancialController::class, 'list']);

          });
       });


        Route::get('/user', [\App\Http\Controllers\AuthController::class, 'me']);

    // Admin Route
    Route::middleware(['is_admin'])->group(function () {

        Route::post('/logout', [\App\Http\Controllers\AuthController::class, 'logout'])->name('logout');

        Route::prefix('notifications')->group(function () {
            Route::get('/dashboard', [\App\Http\Controllers\Admin\NotificationController::class, 'dashboard']);
            Route::post('/read', [\App\Http\Controllers\Admin\NotificationController::class, 'read']);
            Route::get('/list', [\App\Http\Controllers\Admin\NotificationController::class, 'list']);
        });


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
            Route::POST('/AcctSavedView', [\App\Http\Controllers\Admin\UserController::class, 'AcctSavedView']);
            Route::POST('/kill_user', [\App\Http\Controllers\Admin\UserController::class, 'kill_user']);

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

        Route::prefix('cards')->group(function () {
            Route::get('/list', [\App\Http\Controllers\Admin\CardsController::class, 'list']);
            Route::post('/create', [\App\Http\Controllers\Admin\CardsController::class, 'create']);
            Route::post('/edit/{id}', [\App\Http\Controllers\Admin\CardsController::class, 'edit']);
            Route::delete('/delete/{id}', [\App\Http\Controllers\Admin\CardsController::class, 'delete']);

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
                Route::get('/AcctSaveds', [\App\Http\Controllers\Agent\UserController::class, 'AcctSaved']);
                Route::POST('/AcctSavedView', [\App\Http\Controllers\Agent\UserController::class, 'AcctSavedView']);
                Route::POST('/kill_user', [\App\Http\Controllers\Agent\UserController::class, 'kill_user']);

            });
            Route::prefix('radius')->group(function () {
                Route::post('/radlog', [\App\Http\Controllers\Admin\RadiusController::class, 'radlog']);
                Route::post('/radauth', [\App\Http\Controllers\Admin\RadiusController::class, 'radauth']);
                Route::post('/user_report', [\App\Http\Controllers\Admin\RadiusController::class, 'radUserReport']);
            });

            Route::prefix('notifications')->group(function () {
                Route::get('/dashboard', [\App\Http\Controllers\Agent\NotificationController::class, 'dashboard']);
                Route::post('/read', [\App\Http\Controllers\Agent\NotificationController::class, 'read']);
                Route::get('/list', [\App\Http\Controllers\Agent\NotificationController::class, 'list']);
            });

            Route::prefix('cards')->group(function () {
                Route::get('/list', [\App\Http\Controllers\Agent\CardsController::class, 'list']);
                Route::post('/create', [\App\Http\Controllers\Agent\CardsController::class, 'create']);
                Route::post('/edit/{id}', [\App\Http\Controllers\Agent\CardsController::class, 'edit']);
                Route::delete('/delete/{id}', [\App\Http\Controllers\Agent\CardsController::class, 'delete']);

            });

        });
    });


});
