<?php

use App\Models\Source;
use App\Services\AnalyticsService;
use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component
{
    public int $period = 7;

    public ?int $sourceId = null;

    public function mount(): void
    {
        $this->dispatchCharts();
    }

    private function analytics(): AnalyticsService
    {
        return app(AnalyticsService::class);
    }

    public function updated($property): void
    {
        if (in_array($property, ['period', 'sourceId'], true)) {
            $this->dispatchCharts();
        }
    }

    private function dispatchCharts(): void
    {
        unset($this->chartByDate, $this->chartBySource);

        $this->dispatch(
            'charts-updated',
            chartByDate: $this->chartByDate,
            chartBySource: $this->chartBySource,
        );
    }

    #[Computed]
    public function sources()
    {
        return Source::query()->orderBy('name')->get();
    }

    #[Computed]
    public function totalVisits(): int
    {
        return $this->analytics()->totalVisits($this->period, $this->sourceId);
    }

    #[Computed]
    public function todayVisits(): int
    {
        return $this->analytics()->todayVisits($this->period, $this->sourceId);
    }

    #[Computed]
    public function averageVisits(): int
    {
        return $this->analytics()->averageVisits($this->period, $this->sourceId);
    }

    #[Computed]
    public function chartByDate(): array
    {
        return $this->analytics()->chartByDate($this->period, $this->sourceId);
    }

    #[Computed]
    public function chartBySource(): array
    {
        return $this->analytics()->chartBySource($this->period, $this->sourceId);
    }

    #[Computed]
    public function recentMetrics()
    {
        return $this->analytics()->recentMetrics($this->period, $this->sourceId);
    }

    public function exportUrl(): string
    {
        return $this->analytics()->exportUrl($this->period, $this->sourceId);
    }
};
?>

<div class="space-y-8">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <h1 class="text-3xl font-bold tracking-tight">Аналитика визитов</h1>
        </div>

        <a
            href="{{ $this->exportUrl() }}"
            class="inline-flex items-center justify-center rounded-lg bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-700"
        >
            Скачать Excel
        </a>
    </div>

    <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label for="period" class="mb-1 block text-sm font-medium text-slate-700">Период</label>
                <select
                    id="period"
                    wire:model.live="period"
                    class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"
                >
                    <option value="7">Последние 7 дней</option>
                    <option value="30">Последние 30 дней</option>
                    <option value="0">Все данные</option>
                </select>
            </div>

            <div>
                <label for="sourceId" class="mb-1 block text-sm font-medium text-slate-700">Источник</label>
                <select
                    id="sourceId"
                    wire:model.live="sourceId"
                    class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"
                >
                    <option value="">Все источники</option>
                    @foreach ($this->sources as $source)
                        <option value="{{ $source->id }}">{{ $source->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </section>

    <section class="grid gap-4 sm:grid-cols-3" wire:poll.15s>
        <x-stat-card label="Визитов за период" :value="$this->totalVisits" color="indigo" />
        <x-stat-card label="Сегодня" :value="$this->todayVisits" color="emerald" />
        <x-stat-card label="В среднем в день" :value="$this->averageVisits" color="slate" />
    </section>

    <section class="grid gap-6 lg:grid-cols-2">
        <x-chart-card title="Визиты по дням" canvas-id="chartByDate" />
        <x-chart-card title="По источникам" canvas-id="chartBySource" />
    </section>

    <section class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-200 px-5 py-4">
            <h2 class="font-semibold">Последние записи</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-slate-50 text-left text-slate-600">
                    <tr>
                        <th class="px-5 py-3 font-medium">Дата</th>
                        <th class="px-5 py-3 font-medium">Источник</th>
                        <th class="px-5 py-3 font-medium text-right">Визиты</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($this->recentMetrics as $metric)
                        <tr class="hover:bg-slate-50" wire:key="metric-{{ $metric->id }}">
                            <td class="px-5 py-3">{{ $metric->date->format('d.m.Y') }}</td>
                            <td class="px-5 py-3">{{ $metric->source->name }}</td>
                            <td class="px-5 py-3 text-right tabular-nums font-medium">{{ number_format($metric->visits) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="px-5 py-8 text-center text-slate-500">Пока нет данных — обнови страницу или добавь визиты командой</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</div>

@script
<script>
    const drawCharts = (byDate, bySource) => {
        if (byDate?.labels?.length) {
            window.initBarChart?.(document.getElementById('chartByDate'), byDate, 'Визиты');
        }

        if (bySource?.labels?.length) {
            window.initDoughnutChart?.(document.getElementById('chartBySource'), bySource);
        }
    };

    drawCharts(@js($this->chartByDate), @js($this->chartBySource));

    $wire.on('charts-updated', ({ chartByDate, chartBySource }) => {
        drawCharts(chartByDate, chartBySource);
    });
</script>
@endscript
