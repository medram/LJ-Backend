<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        /*
        Schema::table('chats', function (Blueprint $table) {
            $table->renameColumn('name', 'title');
            $table->renameColumn('hash', 'uuid');
            $table->string('path')->nullable()->change();

            $table->longText("chat_history");

            $table->unique("uuid");
            $table->index("uuid");
        });
        */
        Schema::table('chats', function (Blueprint $table) {
            $table->dropColumn('name');
            $table->dropColumn('hash');

            $table->string('title')->nullable();
            $table->string('uuid')->nullable();
            $table->string('path')->nullable()->change();
            $table->longText('chat_history')->nullable();
            $table->unique('uuid');
            $table->index('uuid');
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

            //$table->dropColumn("chat_history");
            $table->dropColumn("title");
            $table->dropColumn("uuid");
            $table->dropColumn("path");

            $table->string('name')->nullable();
            $table->string('hash')->nullable();

            //$table->renameColumn('title', 'name');
            //$table->renameColumn('uuid', 'hash');
        });
    }
};
