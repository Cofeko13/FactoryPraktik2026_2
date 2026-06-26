<?php

namespace Database\Seeders;

use App\Models\Source;
use Illuminate\Database\Seeder;

class AnalyticsSeeder extends Seeder
{
    public function run(): void
    {
        $sources = [
            ['name' => 'Google', 'slug' => 'google'],
            ['name' => 'Прямой заход', 'slug' => 'direct'],
            ['name' => 'Соцсети', 'slug' => 'social'],
            ['name' => 'Email', 'slug' => 'email'],
        ];

        foreach ($sources as $sourceData) {
            Source::query()->firstOrCreate(
                ['slug' => $sourceData['slug']],
                $sourceData,
            );
        }
    }
}
