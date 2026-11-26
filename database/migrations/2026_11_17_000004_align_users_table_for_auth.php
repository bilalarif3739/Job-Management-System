<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->timestamps();
            $table->renameColumn('remembertoken', 'remember_token');
            $table->string('remember_token')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropTimestamps();
            $table->renameColumn('remember_token', 'remembertoken');
            $table->string('remembertoken')->nullable(false)->change();
        });
    }
};
