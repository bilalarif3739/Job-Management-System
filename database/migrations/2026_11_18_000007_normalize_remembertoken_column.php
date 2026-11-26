<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        $hasRemembertoken = Schema::hasColumn('users', 'remembertoken');
        $hasRememberToken = Schema::hasColumn('users', 'remember_token');

        if ($hasRemembertoken && $hasRememberToken) {
            throw new RuntimeException('Both remembertoken and remember_token exist on users.');
        }

        if (!$hasRemembertoken && $hasRememberToken) {
            Schema::table('users', function (Blueprint $table) {
                $table->renameColumn('remember_token', 'remembertoken');
            });
        }

        if (!Schema::hasColumn('users', 'remembertoken')) {
            throw new RuntimeException('The users table has no remember token column to normalize.');
        }

        Schema::table('users', function (Blueprint $table) {
            $table->string('remembertoken')->nullable()->change();
        });
    }

    public function down(): void
    {
        if (!Schema::hasColumn('users', 'remembertoken')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            $table->renameColumn('remembertoken', 'remember_token');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->string('remember_token')->nullable()->change();
        });
    }
};
