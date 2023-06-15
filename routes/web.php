<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\UserController;
use App\Http\Controllers\CheckoutController;

use App\Http\Middleware\UserRequired;
use App\Http\Middleware\AdminRequired;


/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/


# For subscription activation (after payment).
Route::get('/checkout/validate/subscription/{id}/{user_id}', [CheckoutController::class, "validateSubscription"])->name("checkout.validate");


# For user account verification.
Route::get('/verify/{token}', [UserController::class, "verifyAccount"])
        ->where("token", "[a-zA-Z0-9]+")
        ->name('verify_account');
