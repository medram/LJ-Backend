<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // altering Users Table
        Schema::table('users', function(Blueprint $table){
            $table->dropColumn('name');
            $table->string('username');
            $table->string('avatar')->nullable();
            $table->integer('role')->default(0);    // 0 = user, 1 = admin
            $table->boolean('is_active')->default(false);  // 0 = inactive, 1 = active
            $table->string('api_token', 80)->after('password')
                        ->unique()
                        ->nullable()
                        ->default(null);
        });

        // Create Plans table
        Schema::create('plans', function(Blueprint $table){
            $table->id();
            $table->string('name');
            $table->string('description')->nullable();
            $table->float('price', 8, 2)->default(0.0);
            $table->text('features')->nullable();

            $table->boolean('status')->default(false);
            $table->boolean('is_free')->default(false);
            $table->boolean('is_popular')->default(false);
            $table->string('billing_cycle')->default("monthly"); // monthly | yearly

            $table->integer('pdfs')->default(0);
            $table->integer('questions')->default(0);
            $table->integer('pdf_size')->default(0);
            $table->integer('pdf_pages')->default(0);

            $table->timestamps();
        });

        // Settings table
        Schema::create("settings", function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('value')->nullable()->default("");
            $table->string('type')->default("string"); # string | int | integer | float | boolean
        });

        // Subscription table
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users');
            $table->foreignId('plan_id')->constrained('plans');

            $table->boolean('status')->default(false);
            $table->timestamp('expiring_at')->nullable();

            $table->integer('pdfs')->default(0);
            $table->integer('questions')->default(0);
            $table->integer('pdf_size')->default(0);
            $table->integer('pdf_pages')->default(0);

            $table->timestamps();
        });

        // Invoice table
        Schema::create("invoices", function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users');
            $table->foreignId('plan_id')->constrained('plans');
            $table->foreignId('subscription_id')->constrained('subscriptions');

            $table->boolean('is_paid')->default(false);
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });

        // PDFs table
        Schema::create('pdfs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users');

            $table->string('name');
            $table->string('path');
            $table->string('hash')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subscriptions');
        Schema::dropIfExists('plans');
        Schema::dropIfExists('pdfs');
        Schema::dropIfExists('invoices');
        Schema::dropIfExists('settings');
    }
};
