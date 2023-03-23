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
            $table->float('price', 8, 2)->default(0.0);
            $table->text('features')->nullable();
            $table->boolean('is_active')->default(false);
            $table->boolean('is_visible')->default(false);
            $table->boolean('private_profile')->default(false);
            $table->timestamps();
        });

        // Settings table
        Schema::create("settings", function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('value');
        });

        // Invoice table
        Schema::create("invoices", function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users');
            $table->foreignId('plan_id')->constrained('plans');

            $table->boolean('is_paid')->default(false);
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });

        // Subscription table
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users');
            $table->foreignId('plan_id')->constrained('plans');

            $table->integer('image_quota')->default(0);
            $table->boolean('private_profile')->default(false);
            $table->integer('duration')->default(0);
            $table->timestamp('registered_at')->nullable();

            $table->timestamps();
        });

        // Images table
        Schema::create('images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users');

            $table->text('prompt')->nullable();
            $table->string('resolution')->nullable();
            $table->string('path')->nullable();

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
        Schema::dropIfExists('images');
        Schema::dropIfExists('invoices');
        Schema::dropIfExists('settings');
    }
};
