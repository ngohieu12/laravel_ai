<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

/**
 * Creates the only two categories that ship with the app: the buckets video
 * and MP3 posts are filed under by default.
 */
class CategorySeeder extends Seeder
{
    public function run(): void
    {
        Category::ensureDefaults();
    }
}
