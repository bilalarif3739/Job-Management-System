<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('user_tokens', function (Blueprint $table) {
            $table->id()->index();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('token_hash', 100)->unique();
            $table->timestamps();
            $table->timestamp('expires_at')->nullable();
            $table->index(['user_id', 'token_hash']);
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->id()->index();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('token')->unique();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
            $table->index('expires_at');

        });
        Schema::create('account_verify_tokens', function (Blueprint $table) {
            $table->id()->index();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('token')->unique();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
            $table->index('expires_at');

        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_tokens');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('account_verify_tokens');
    }
};
