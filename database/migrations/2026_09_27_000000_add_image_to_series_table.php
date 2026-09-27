<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Give a series its own cover image.
     *
     * The columns mirror `posts.image` / `posts.image_alt` on purpose: a series
     * looks like a post to the reader, so it deserves the same cover treatment
     * and the same alt-text contract. The cover belongs to the series, not to
     * any of its parts — the opening part may change without the identity of
     * the series changing.
     */
    public function up(): void
    {
        Schema::table('series', function (Blueprint $table) {
            $table->string('image')->nullable()->after('description');
            $table->string('image_alt')->nullable()->after('image');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('series', function (Blueprint $table) {
            $table->dropColumn(['image', 'image_alt']);
        });
    }
};
