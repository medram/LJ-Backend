<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\UserController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\InstallController;
use App\Http\Controllers\FrontendController;

use App\Http\Middleware\UserRequired;
use App\Http\Middleware\AdminRequired;

use App\Http\Middleware\InstallerMiddleware;


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


# Installer
Route::group(["prefix" => "/install", "middleware" => InstallerMiddleware::class], function () {
    Route::controller(InstallController::class)->group(function (){

        Route::get("/", "index")->name("install.index");
        Route::get("/requirements", "requirements")->name("install.requirements");
        Route::get("/database", "database")->name("install.database");
        Route::post("/database", "database")->name("install.database.post");
        Route::get("/verify", "verify")->name("install.verify");
        Route::post("/verify", "verify")->name("install.verify.post");
        Route::get("/install", "installDatabase")->name("install.database.install");
        Route::get("/completed", "completed")->name("install.completed");

    });
});

# Home page (For Frontent React Project)
Route::fallback([FrontendController::class, "index"])
    ->name("frontend")
    ->middleware(InstallerMiddleware::class);
