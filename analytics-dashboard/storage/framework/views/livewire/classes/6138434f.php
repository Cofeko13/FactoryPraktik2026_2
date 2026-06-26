<?php

use Livewire\Component;

return new class extends Component
{
    public int $visits = 1240;

    /** @return array{labels: list<string>, values: list<int>} */
    public function chartData(): array
    {
        return [
            'labels' => ['Пн', 'Вт', 'Ср', 'Чт', 'Пт', 'Сб', 'Вс'],
            'values' => [120, 190, 150, 220, 180, 250, 210],
        ];
    }

    public function increment(): void
    {
        $this->visits++;
    }

    protected function view($data = [])
    {
        return app('view')->file('/Users/panda/Projects/analytics-dashboard/storage/framework/views/livewire/views/6138434f.blade.php', $data);
    }
};
