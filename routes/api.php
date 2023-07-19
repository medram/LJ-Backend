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
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\SubscriptionController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\WebhookController;
use App\Http\Controllers\AnalyticsController;

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
    Route::post('/auth/forget-password', [UserController::class, "forgetPassword"])->name('auth.forget.password');
    Route::post('/auth/reset-password', [UserController::class, "resetPassword"])->name('auth.reset.password');

    // Global data
    Route::get('/plans', [CommonController::class, "plans"])->name('plans');
    Route::get('/settings', [SettingsController::class, "publicSettings"])->name('settings');
    Route::get('/payment-methods', [CommonController::class, "paymentMethods"])->name('payment_methods');
    Route::get('/pages', [CommonController::class, "getPages"]);
    Route::get('/page/{slug}', [CommonController::class, "getPage"])->where("slug", "[a-zA-Z0-9\-\_]+");
    Route::get('/lc/check', [CommonController::class, "LC"]);

    // Contact Us
    Route::post('/contact', [CommonController::class, "contactUs"])->name("contact_us");

    // Webhooks registration
    Route::post("/webhook/paypal", [WebhookController::class, "paypal"]);

    // Checkout section
    Route::middleware([UserRequired::class])->group(function (){

        Route::post('/checkout', [CheckoutController::class, "index"])->name("checkout");
        Route::post('/auth/user', [UserController::class, 'currentUser'])->name('auth.user');
    });

    // Users section
    Route::prefix('user')->middleware([UserRequired::class])->group(function (){
        Route::get('/', [UserController::class, 'dashboard']);

        Route::get('profile', [UserController::class, 'profile']);
        Route::post('profile', [UserController::class, 'updateProfile']);

        Route::get('subscription', [UserController::class, 'subscription']);
        Route::get('invoices', [UserController::class, 'invoices']);

        Route::post('update-password', [UserController::class, 'updatePassword']);

        Route::get('chat-list', [ChatController::class, "list"]);

        // Chat / Playground section
        Route::post('chat', [ChatController::class, "upload"]);
        Route::post('chat/register-openai-key', [ChatController::class, "registerOpenAIKey"]);
        Route::get('chat/{uuid}', [ChatController::class, "details"])->where("uuid", "[a-zA-Z0-9\-]+");
        Route::post('chat/{uuid}', [ChatController::class, "send"])->where("uuid", "[a-zA-Z0-9\-]+");
        Route::post('chat/{uuid}/clear-history', [ChatController::class, "clearHistory"])->where("uuid", "[a-zA-Z0-9\-]+");
        Route::delete('chat/{uuid}/delete', [ChatController::class, "delete"])->where("uuid", "[a-zA-Z0-9\-]+");
        Route::post('chat/{uuid}/stop', [ChatController::class, "stop"])->where("uuid", "[a-zA-Z0-9\-]+");
    });

    // Admin section
    Route::prefix('admin')->middleware([AdminRequired::class])->group(function (){
        Route::get('analytics', [AnalyticsController::class, "analytics"]);

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
        Route::post('plans/delete', [PlansController::class, 'delete']);

        # Manage subscriptions
        Route::get('subscriptions', [SubscriptionController::class, "list"]);
        Route::post('subscriptions/{sub_id}/cancel', [SubscriptionController::class, "cancel"]);

        # Send a test email (SMTP)
        Route::post('send-test-email', [CommonController::class, "sendTestEmail"]);

        # Webhooks registration
        Route::post("/register-paypal-webhook", [WebhookController::class, "registerPayPalWebhook"]);
    });
});
