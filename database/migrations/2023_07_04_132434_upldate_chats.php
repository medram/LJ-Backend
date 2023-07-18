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
        Schema::table('chats', function (Blueprint $table) {
            $table->renameColumn('name', 'title');
            $table->renameColumn('hash', 'uuid');
            $table->string('path')->nullable()->change();

            $table->longText("chat_history");

            $table->unique("uuid");
            $table->index("uuid");
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('chats', function (Blueprint $table) {
            $table->dropUnique("uuid");
            $table->dropIndex("uuid");

            $table->dropColumn("chat_history");

            $table->renameColumn('title', 'name');
            $table->renameColumn('uuid', 'hash');
        });
    }
};
