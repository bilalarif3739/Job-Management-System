<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedBigInteger('last_activity_unix')->nullable()->after('last_activity');
        });

        DB::table('users')
            ->whereNotNull('last_activity')
            ->update(['last_activity_unix' => DB::raw('UNIX_TIMESTAMP(last_activity)')]);

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('last_activity');
            $table->renameColumn('last_activity_unix', 'last_activity');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('last_activity_datetime')->nullable()->after('last_activity');
        });

        DB::table('users')
            ->whereNotNull('last_activity')
            ->update(['last_activity_datetime' => DB::raw('FROM_UNIXTIME(last_activity)')]);

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('last_activity');
            $table->renameColumn('last_activity_datetime', 'last_activity');
        });
    }
};
