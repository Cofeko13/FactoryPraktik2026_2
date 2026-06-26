<?php

namespace App\Console\Commands;

use App\Models\Metric;
use App\Models\Source;
use Illuminate\Console\Command;

class AddTestVisits extends Command
{
    protected $signature = 'analytics:add-visits
                            {count=10 : Сколько визитов добавить}
                            {--source=direct : slug: google, direct, social, email}
                            {--date= : Дата YYYY-MM-DD (по умолчанию — сегодня)}';

    protected $description = 'Тестово добавить визиты (та же логика, что при заходе на сайт)';

    public function handle(): int
    {
        $source = Source::query()->where('slug', $this->option('source'))->first();

        if (! $source) {
            $this->error('Источник не найден. Доступны: google, direct, social, email');

            return self::FAILURE;
        }

        $date = $this->option('date') ?: today()->toDateString();
        $count = max(1, (int) $this->argument('count'));

        Metric::incrementVisit($source->id, $count, $date);

        $total = Metric::query()
            ->where('source_id', $source->id)
            ->whereDate('date', $date)
            ->value('visits');

        $this->info("Добавлено +{$count} визитов");
        $this->info("Источник: {$source->name} ({$source->slug})");
        $this->info("Дата: {$date}");
        $this->info("Итого за этот день: {$total}");

        return self::SUCCESS;
    }
}
