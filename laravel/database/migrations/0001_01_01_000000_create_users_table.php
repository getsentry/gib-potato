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
        Schema::create('progression', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->integer('level');
            $table->string('description');
            $table->integer('potatoes_needed');
            $table->timestamps();
        });

        Schema::create('users', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('progression_id')->nullable();
            $table->string('status')->default('active');
            $table->string('role')->default('user');
            $table->string('slack_user_id')->unique();
            $table->string('slack_name');
            $table->string('slack_picture');
            $table->string('slack_time_zone')->nullable();
            $table->boolean('slack_is_bot')->default(false);
            $table->json('notifications')->nullable();
            $table->timestamps();
            
            $table->index('slack_user_id');
            $table->foreign('progression_id')->references('id')->on('progression');
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignUuid('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('users');
        Schema::dropIfExists('progression');
    }
};
