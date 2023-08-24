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


Route::get('/ping', [\App\Http\Controllers\ApiController::class, 'index']);


Route::post('/login_user', [\App\Http\Controllers\AuthController::class, 'login_user'])->name('login_user');
Route::post('/save_stogram', [\App\Http\Controllers\ApiController::class, 'save_stogram'])->name('save_stogram');

Route::post('/login', [\App\Http\Controllers\AuthController::class, 'login'])->name('login');
Route::get('/login', function(){
    return \response()->json(['status' => false,'message' => '403 forbidden Login'],403);
});

Route::get('/download/{image}', [\App\Http\Controllers\Admin\WireGuardController::class, 'download']);

Route::prefix('mobile')->group(function () {
    Route::post('/sign-in', [\App\Http\Controllers\Mobile\AuthController::class, 'sign_in'])->name('sign_in');
    Route::post('/config', [\App\Http\Controllers\Mobile\AuthController::class, 'is_valid_token'])->name('is_valid_token');
    Route::post('/servers', [\App\Http\Controllers\Mobile\AuthController::class, 'get_servers'])->name('get_servers');
    Route::post('/notifications', [\App\Http\Controllers\Mobile\AuthController::class, 'get_notifications'])->name('get_notifications');
    Route::get('/get-ip', [\App\Http\Controllers\Mobile\AuthController::class, 'get_ip'])->name('get_ip');


});

Route::middleware(['auth:api'])->group(function () {



      // User Controller
       Route::prefix('user')->group(function () {

          Route::get('/detial', [\App\Http\Controllers\User\UserController::class, 'index']);
          Route::POST('/edit_password', [\App\Http\Controllers\User\UserController::class, 'edit_password']);
          Route::POST('/edit_detial', [\App\Http\Controllers\User\UserController::class, 'edit_detial']);
          Route::get('/auth_log', [\App\Http\Controllers\User\UserController::class, 'auth_log']);
          Route::get('/get_servers', [\App\Http\Controllers\User\UserController::class, 'get_servers']);
          Route::get('/get_groups', [\App\Http\Controllers\User\UserController::class, 'get_groups']);
          Route::get('/get_group', [\App\Http\Controllers\User\UserController::class, 'get_group']);

          Route::POST('/charge_account', [\App\Http\Controllers\User\UserController::class, 'charge_account']);


           Route::prefix('v2ray')->group(function () {
               Route::POST('/buy_volume', [\App\Http\Controllers\User\V2rayController::class, 'buy_volume']);
               Route::POST('/update_config', [\App\Http\Controllers\User\V2rayController::class, 'update_config']);
           });

              Route::prefix('financial')->group(function () {
              Route::POST('/create', [\App\Http\Controllers\User\FinancialController::class, 'create']);
              Route::get('/list', [\App\Http\Controllers\User\FinancialController::class, 'list']);
              Route::POST('/edit/{id}', [\App\Http\Controllers\User\FinancialController::class, 'edit']);

          });
       });


        Route::get('/user', [\App\Http\Controllers\AuthController::class, 'me']);

    // Admin Route
    Route::middleware(['is_admin'])->group(function () {


        Route::post('/logout', [\App\Http\Controllers\AuthController::class, 'logout'])->name('logout');

        Route::prefix('v2ray')->group(function () {
            Route::get('/status', [\App\Http\Controllers\Admin\AdminsController::class, 'GetRealV2rayServerStatus']);
        });

        Route::get('/getDashboard', [\App\Http\Controllers\Admin\AdminsController::class, 'getDashboard']);
        Route::prefix('wireguard')->group(function () {
            Route::get('/index', [\App\Http\Controllers\Admin\WireGuardController::class, 'index']);
            Route::post('/update_wg/{id}', [\App\Http\Controllers\Admin\WireGuardController::class, 'update']);
            Route::delete('/delete/{id}', [\App\Http\Controllers\Admin\WireGuardController::class, 'delete']);

        });
        Route::prefix('monitor')->group(function () {
            Route::get('/index', [\App\Http\Controllers\Admin\MonitorigController::class, 'index']);
            Route::get('/ether/{ip}', [\App\Http\Controllers\Admin\MonitorigController::class, 'ether']);
        });
        Route::prefix('blog')->group(function () {
            Route::post('/create', [\App\Http\Controllers\Admin\BlogController::class, 'create']);
        });

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

            Route::get('/user_bandwidths', [\App\Http\Controllers\Admin\UserController::class, 'getUserBandwith']);

            Route::POST('/buy_volume/{id}', [\App\Http\Controllers\Admin\UserController::class, 'buy_volume']);
            Route::POST('/buy_day/{id}', [\App\Http\Controllers\Admin\UserController::class, 'buy_day']);

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
            Route::prefix('agents')->group(function () {
                Route::get('/list', [\App\Http\Controllers\Agent\AgentsController::class, 'index']);
                Route::post('/create', [\App\Http\Controllers\Agent\AgentsController::class, 'create']);
                Route::post('/edit/{id}', [\App\Http\Controllers\Agent\AgentsController::class, 'edit']);
                Route::get('/view/{id}', [\App\Http\Controllers\Agent\AgentsController::class, 'view']);
                Route::post('/save_custom_price/{id}', [\App\Http\Controllers\Agent\AgentsController::class, 'save_custom_price']);
            });
            Route::prefix('wireguard')->group(function () {
                Route::get('/index', [\App\Http\Controllers\Agent\WireGuardController::class, 'index']);
                Route::post('/update_wg/{id}', [\App\Http\Controllers\Agent\WireGuardController::class, 'update']);
                Route::delete('/delete/{id}', [\App\Http\Controllers\Agent\WireGuardController::class, 'delete']);

            });


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
                Route::POST('/buy_volume/{id}', [\App\Http\Controllers\Agent\UserController::class, 'buy_volume']);
                Route::POST('/buy_day/{id}', [\App\Http\Controllers\Agent\UserController::class, 'buy_day']);

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
            Route::prefix('groups')->group(function () {
                Route::get('/list', [\App\Http\Controllers\Agent\AgentController::class, 'GetGroups']);
                Route::post('/edit/{group_id}', [\App\Http\Controllers\Agent\AgentController::class, 'edit']);
            });
        });
    });


});
