<?php

namespace App\Services;

use App\Models\Metric;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Вся аналитика читается через этот класс.
 * Фильтры period + sourceId — одинаковые на дашборде, в Excel и в таблице.
 */
class AnalyticsService
{
    /** @return Builder<Metric> */
    public function query(int $period, ?int $sourceId): Builder
    {
        return Metric::query()->filtered($period, $sourceId);
    }

    public function totalVisits(int $period, ?int $sourceId): int
    {
        return (int) $this->query($period, $sourceId)->sum('visits');
    }

    public function todayVisits(int $period, ?int $sourceId): int
    {
        return (int) $this->query($period, $sourceId)
            ->whereDate('date', today())
            ->sum('visits');
    }

    public function averageVisits(int $period, ?int $sourceId): int
    {
        $total = $this->totalVisits($period, $sourceId);
        $days = max(1, (int) $this->query($period, $sourceId)->clone()->distinct()->count('date'));

        return (int) round($total / $days);
    }

    /** @return array{labels: list<string>, values: list<int>} */
    public function chartByDate(int $period, ?int $sourceId): array
    {
        $rows = $this->query($period, $sourceId)
            ->selectRaw('date, SUM(visits) as total')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return [
            'labels' => $rows->map(fn ($row) => \Carbon\Carbon::parse($row->date)->format('d.m'))->all(),
            'values' => $rows->pluck('total')->map(fn ($value) => (int) $value)->all(),
        ];
    }

    /** @return array{labels: list<string>, values: list<int>} */
    public function chartBySource(int $period, ?int $sourceId): array
    {
        $rows = $this->query($period, $sourceId)
            ->join('sources', 'metrics.source_id', '=', 'sources.id')
            ->selectRaw('sources.name as name, SUM(metrics.visits) as total')
            ->groupBy('sources.name')
            ->orderByDesc('total')
            ->get();

        return [
            'labels' => $rows->pluck('name')->all(),
            'values' => $rows->pluck('total')->map(fn ($value) => (int) $value)->all(),
        ];
    }

    /** @return Collection<int, Metric> */
    public function recentMetrics(int $period, ?int $sourceId, int $limit = 15): Collection
    {
        return $this->query($period, $sourceId)
            ->with('source')
            ->orderByDesc('date')
            ->orderBy('source_id')
            ->limit($limit)
            ->get();
    }

    public function exportUrl(int $period, ?int $sourceId): string
    {
        return route('export', array_filter([
            'period' => $period,
            'source_id' => $sourceId,
        ], fn ($value) => $value !== null && $value !== ''));
    }
}
