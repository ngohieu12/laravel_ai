<?php

use App\Support\VietnameseText;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Public reading surface for the existing post-based series.
 *
 * A series is a group of posts sharing `series_title`, ordered by `series_part`.
 * This adds the two things those posts still lacked:
 *
 *  - `series_slug`: a stable, shareable identifier for the series so it can have
 *    public list and detail screens. Derived from the title, so every part of the
 *    same series resolves to the same slug.
 *  - `is_long_form`: a manual flag marking the parts worth reading in depth,
 *    rather than inferring it from the content length.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->string('series_slug', 160)->nullable()->after('series_part');
            $table->boolean('is_long_form')->default(false);

            $table->index('series_slug');
            $table->index(['series_slug', 'series_part']);
        });

        $this->backfillSeriesSlugs();
    }

    public function down(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->dropIndex(['series_slug', 'series_part']);
            $table->dropIndex(['series_slug']);
            $table->dropColumn(['series_slug', 'is_long_form']);
        });
    }

    /**
     * Give every existing series a slug, grouping rows by title so that all the
     * parts of one series end up sharing a single value.
     */
    private function backfillSeriesSlugs(): void
    {
        DB::table('posts')
            ->whereNotNull('series_title')
            ->where('series_title', '!=', '')
            ->select('series_title')
            ->distinct()
            ->pluck('series_title')
            ->each(function (string $title): void {
                DB::table('posts')
                    ->where('series_title', $title)
                    ->update(['series_slug' => $this->slugFor($title)]);
            });
    }

    /**
     * Slug for a series title, disambiguated when a different title folds onto
     * the same ASCII form ("Lập Trình" vs "Lap Trinh").
     */
    private function slugFor(string $title): string
    {
        $base = Str::slug(VietnameseText::toAscii($title));

        if ($base === '') {
            $base = 'chuoi-bai-viet';
        }

        $slug = $base;
        $suffix = 1;

        while (DB::table('posts')
            ->where('series_slug', $slug)
            ->where('series_title', '!=', $title)
            ->exists()) {
            $slug = $base.'-'.(++$suffix);
        }

        return $slug;
    }
};
