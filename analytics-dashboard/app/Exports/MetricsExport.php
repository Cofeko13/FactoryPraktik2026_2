<?php

namespace App\Exports;

use App\Services\AnalyticsService;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class MetricsExport implements FromCollection, WithHeadings, WithMapping
{
    public function __construct(
        private int $period = 7,
        private ?int $sourceId = null,
        private ?AnalyticsService $analytics = null,
    ) {
        $this->analytics ??= app(AnalyticsService::class);
    }

    public function collection(): Collection
    {
        return $this->analytics
            ->query($this->period, $this->sourceId)
            ->with('source')
            ->orderByDesc('date')
            ->orderBy('source_id')
            ->get();
    }

    /** @return list<string> */
    public function headings(): array
    {
        return ['Дата', 'Источник', 'Визиты'];
    }

    /** @return list<int|string> */
    public function map($metric): array
    {
        return [
            $metric->date->format('d.m.Y'),
            $metric->source->name,
            $metric->visits,
        ];
    }
}
