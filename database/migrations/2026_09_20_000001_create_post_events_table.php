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
        Schema::create('post_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('post_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type', 30);
            $table->json('meta')->nullable();
            $table->string('session_hash', 64)->nullable();
            $table->string('ip_hash', 64)->nullable();
            $table->timestamps();

            $table->index(['post_id', 'type', 'created_at']);
            $table->index(['type', 'created_at']);
            $table->index(['post_id', 'session_hash', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('post_events');
    }
};
