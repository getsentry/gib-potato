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
        Schema::create('messages', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('type')->default('message');
            $table->foreignUuid('sender_user_id');
            $table->foreignUuid('receiver_user_id');
            $table->text('text');
            $table->string('channel');
            $table->integer('count')->default(1);
            $table->timestamps();
            
            $table->index(['sender_user_id', 'receiver_user_id']);
            $table->index('created_at');
            $table->foreign('sender_user_id')->references('id')->on('users');
            $table->foreign('receiver_user_id')->references('id')->on('users');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('messages');
    }
};
