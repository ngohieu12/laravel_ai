<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->unsignedBigInteger('views_count')->default(0)->after('is_published');
            $table->unsignedBigInteger('shares_count')->default(0)->after('views_count');
            $table->unsignedBigInteger('favorites_count')->default(0)->after('shares_count');
            $table->unsignedBigInteger('comments_count')->default(0)->after('favorites_count');

            $table->index(['is_published', 'views_count']);
            $table->index(['is_published', 'shares_count']);
            $table->index(['is_published', 'favorites_count']);
        });

        // Backfill counters from data that already exists.
        DB::table('posts')->update([
            'favorites_count' => DB::raw('(SELECT COUNT(*) FROM favorites WHERE favorites.post_id = posts.id)'),
            'comments_count' => DB::raw('(SELECT COUNT(*) FROM comments WHERE comments.post_id = posts.id)'),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->dropIndex(['is_published', 'views_count']);
            $table->dropIndex(['is_published', 'shares_count']);
            $table->dropIndex(['is_published', 'favorites_count']);

            $table->dropColumn(['views_count', 'shares_count', 'favorites_count', 'comments_count']);
        });
    }
};
