<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\UserController;
use App\Http\Middleware\AdminMiddleware;

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

Route::post('login', [AuthController::class, 'login']);

Route::get('validateToken', [AuthController::class, 'validateToken']);
Route::post('recoverPassword', [UserController::class, 'passwordRecovery']);
Route::post('updatePassword', [UserController::class, 'updatePassword']);


Route::get('validateToken', [AuthController::class, 'validateToken']);

Route::middleware('jwt')->group(function(){

    Route::post('logout', [AuthController::class, 'logout']);

    Route::prefix('user')->group(function(){
                
        Route::post('create', [UserController::class, 'create']);
        Route::get('me', [UserController::class, 'getUser']);
        Route::patch('{id}', [UserController::class, 'update']);

        Route::middleware(AdminMiddleware::class)->group(function(){
            Route::get('all', [UserController::class, 'all']);
            Route::get('search', [UserController::class, 'search']);            
            Route::post('block/{id}', [UserController::class, 'userBlock']);
            Route::post('change-limit/{id}', [UserController::class, 'changeLimit']);
        });
    });

    Route::prefix('setting')->group(function(){
        Route::get('search', [SettingController::class, 'search']);
        Route::patch('/', [SettingController::class, 'update']);
    });
});