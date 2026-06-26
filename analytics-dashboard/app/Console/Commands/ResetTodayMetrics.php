<?php

namespace App\Console\Commands;

use App\Models\Metric;
use Illuminate\Console\Command;

class ResetTodayMetrics extends Command
{
    protected $signature = 'analytics:reset-today';

    protected $description = 'Удалить все визиты за сегодня (сброс счётчика «Сегодня»)';

    public function handle(): int
    {
        $today = today()->toDateString();
        $deleted = Metric::query()->whereDate('date', today())->delete();

        $this->info("Сегодня: {$today}");
        $this->info("Удалено записей: {$deleted}");

        return self::SUCCESS;
    }
}
