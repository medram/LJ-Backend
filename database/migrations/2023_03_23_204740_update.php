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
        Schema::table('plans', function (Blueprint $table) {
            $table->dropColumn('is_visible');
            $table->dropColumn('private_profile');
            $table->dropColumn('is_active');

            $table->string('description')->nullable();
            $table->boolean('status')->default(false);
            $table->boolean('is_free')->default(false);
            $table->boolean('is_popular')->default(false);
            $table->string('billing_cycle')->default("Monthly");
            $table->integer('images_quota')->default(0);
            $table->string('image_sizes')->default("256x256"); // 256x256, 512x512, 1024x1024
        });

        Schema::table('subscriptions', function (Blueprint $table){
            $table->dropColumn('image_quota');
            $table->dropColumn('private_profile');
            $table->dropColumn('duration');
            $table->dropColumn('registered_at');

            $table->boolean('status')->default(false);
            $table->timestamp('expiring_at')->nullable();
            $table->integer('images_quota')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
