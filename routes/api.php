<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\UserController;
use App\Http\Controllers\CommonController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\PagesController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\UploadController;
use App\Http\Controllers\PlansController;

use App\Http\Middleware\UserRequired;
use App\Http\Middleware\AdminRequired;

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

Route::prefix('v1')->group(function (){

    // Auth stuff!
    Route::post('/auth', [UserController::class, 'login'])->name('auth.login');
    Route::post('/auth/logout', [UserController::class, 'logout'])->name('auth.logout');
    Route::post('/auth/register', [UserController::class, "register"])->name('auth.register');

    // Global date
    Route::get('/plans', [CommonController::class, "plans"])->name('plans');
    Route::get('/settings', [SettingsController::class, "publicSettings"])->name('settings');
    Route::get('/payment-methods', [CommonController::class, "paymentMethods"])->name('payment_methods');

    // Users section
    Route::prefix('user')->middleware([UserRequired::class])->group(function (){
        Route::get('/', [UserController::class, 'dashboard']);

        Route::get('profile', [UserController::class, 'profile']);
        Route::post('profile', [UserController::class, 'updateProfile']);

        Route::get('subscription', [UserController::class, 'subscription']);
    });

    // Admin section
    Route::prefix('admin')->middleware([AdminRequired::class])->group(function (){
        Route::get('customers', [CustomerController::class, 'customers']);
        Route::post('customers/add', [CustomerController::class, 'add']);
        Route::post('customers/edit/{id}', [CustomerController::class, 'edit'])->where('id', "[0-9]+");
        Route::get('customers/details/{id}', [CustomerController::class, 'details'])->where('id', "[0-9]+");
        Route::post('customers/delete', [CustomerController::class, 'delete']);

        # managing site settings.
        Route::get('settings', [SettingsController::class, 'list']);
        Route::post('settings', [SettingsController::class, 'update']);

        # Managing pages.
        Route::get('pages', [PagesController::class, 'list']);
        Route::post('pages/add', [PagesController::class, 'add']);
        Route::post('pages/edit/{id}', [PagesController::class, 'edit'])->where('id', "[0-9]+");
        Route::get('pages/details/{id}', [PagesController::class, 'details'])->where('id', "[0-9]+");
        Route::post('pages/delete', [PagesController::class, 'delete']);

        # Upload images / files.
        Route::post('upload', [UploadController::class, 'upload']);

        # Manage plans
        Route::get('plans', [PlansController::class, 'list']);
        Route::post('plans/add', [PlansController::class, 'add']);
        Route::post('plans/edit/{id}', [PlansController::class, 'edit'])->where('id', "[0-9]+");
        #Route::get('plans/details/{id}', [PlansController::class, 'details'])->where('id', "[0-9]+");
        Route::post('plans/delete', [PlansController::class, 'delete']);
    });
});
