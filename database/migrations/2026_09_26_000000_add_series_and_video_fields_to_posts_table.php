<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->string('content_type', 20)->default('text')->after('summary');
            $table->string('video_url', 2048)->nullable()->after('content');
            $table->string('series_title')->nullable()->after('video_url');
            $table->unsignedInteger('series_part')->nullable()->after('series_title');

            $table->index(['series_title', 'series_part']);
            $table->index('content_type');
        });
    }

    public function down(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->dropIndex(['series_title', 'series_part']);
            $table->dropIndex(['content_type']);
            $table->dropColumn(['content_type', 'video_url', 'series_title', 'series_part']);
        });
    }
};
