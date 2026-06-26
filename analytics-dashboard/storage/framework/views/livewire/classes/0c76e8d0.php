<?php

use App\Models\Source;
use App\Services\AnalyticsService;
use Livewire\Attributes\Computed;
use Livewire\Component;

return new class extends Component
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

    protected function view($data = [])
    {
        return app('view')->file('/Users/panda/Projects/analytics-dashboard/storage/framework/views/livewire/views/0c76e8d0.blade.php', $data);
    }
};
