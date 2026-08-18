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
        Schema::create('slack_threads', function (Blueprint $table) {
            $table->id();
            $table->string('channel');
            $table->string('thread_ts');
            $table->string('conversation_id', 36);
            $table->timestamps();

            $table->unique(['channel', 'thread_ts']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('slack_threads');
    }
};
