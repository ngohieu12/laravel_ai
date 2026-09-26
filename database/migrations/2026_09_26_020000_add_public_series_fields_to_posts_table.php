<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Promotes a series from a free-text label to a real record.
 *
 * A series used to be "posts whose `series_title` happens to be equal", which
 * made the title the identity: renaming it silently forked the series, and two
 * unrelated series could collide on the same string. Series now live in their
 * own table and posts point at them by id, so the title is only a label that
 * can change freely.
 *
 * Existing series are migrated: one row per distinct non-empty title, and each
 * post is re-pointed at the row its title produced. The manual "long form"
 * flag arrives here too, because it is a property of a post's place in a series.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('series', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();

            $table->index('title');
        });

        Schema::table('posts', function (Blueprint $table) {
            $table->foreignId('series_id')->nullable()->after('series_part')->constrained()->nullOnDelete();
            $table->boolean('is_long_form')->default(false);

            $table->index(['series_id', 'series_part']);
        });

        $this->migrateExistingSeries();

        Schema::table('posts', function (Blueprint $table) {
            $table->dropIndex(['series_title', 'series_part']);
            $table->dropColumn('series_title');
        });
    }

    public function down(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->string('series_title')->nullable()->after('series_part');
        });

        // The index on (series_title, series_part) belonged to the old scheme
        // and was dropped in up(); restore it so the migration can run again.
        Schema::table('posts', function (Blueprint $table) {
            $table->index(['series_title', 'series_part']);
        });

        // Copy the title back down so no series information is lost on rollback.
        DB::table('posts')
            ->join('series', 'series.id', '=', 'posts.series_id')
            ->select('posts.id', 'series.title')
            ->orderBy('posts.id')
            ->get()
            ->each(function (object $row): void {
                DB::table('posts')->where('id', $row->id)->update(['series_title' => $row->title]);
            });

        Schema::table('posts', function (Blueprint $table) {
            $table->dropForeign(['series_id']);
            $table->dropIndex(['series_id', 'series_part']);
            $table->dropColumn(['series_id', 'is_long_form']);
        });

        Schema::dropIfExists('series');
    }

    /**
     * Turn every distinct series title into a row, then re-point the posts.
     */
    private function migrateExistingSeries(): void
    {
        DB::table('posts')
            ->whereNotNull('series_title')
            ->where('series_title', '!=', '')
            ->select('series_title')
            ->distinct()
            ->orderBy('series_title')
            ->pluck('series_title')
            ->each(function (string $title): void {
                $seriesId = DB::table('series')->insertGetId([
                    'title' => $title,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                DB::table('posts')->where('series_title', $title)->update(['series_id' => $seriesId]);
            });
    }
};
