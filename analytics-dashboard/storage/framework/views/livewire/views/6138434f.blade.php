<?php
use Livewire\Component;
?>

<div class="space-y-6">
    {{-- Livewire: счётчик обновляется на сервере без перезагрузки страницы --}}
    <section class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
        <h2 class="text-lg font-semibold">Livewire — счётчик визитов</h2>
        <p class="mt-1 text-sm text-slate-500">Клик отправляет запрос на сервер, PHP меняет свойство, Blade перерисовывается.</p>

        <div class="mt-4 flex items-center gap-4">
            <span class="text-4xl font-bold tabular-nums text-indigo-600">{{ number_format($visits) }}</span>
            <button
                type="button"
                wire:click="increment"
                class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700"
            >
                +1 визит
            </button>
        </div>
    </section>

    {{-- Alpine.js: чисто на клиенте, без запроса на сервер --}}
    <section
        x-data="{ open: true }"
        class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm"
    >
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-lg font-semibold">Alpine.js — показать / скрыть</h2>
                <p class="mt-1 text-sm text-slate-500">Работает только в браузере, сервер не участвует.</p>
            </div>
            <button
                type="button"
                @click="open = !open"
                class="rounded-lg border border-slate-300 px-3 py-1.5 text-sm hover:bg-slate-50"
                x-text="open ? 'Скрыть' : 'Показать'"
            ></button>
        </div>

        <div x-show="open" x-transition class="mt-4 rounded-lg bg-emerald-50 p-4 text-emerald-800">
            Этот блок управляется Alpine.js через <code class="rounded bg-emerald-100 px-1">x-data</code> и <code class="rounded bg-emerald-100 px-1">x-show</code>.
        </div>
    </section>

    {{-- Chart.js: график из данных PHP --}}
    <section class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
        <h2 class="text-lg font-semibold">Chart.js — график визитов за неделю</h2>
        <p class="mt-1 text-sm text-slate-500">Данные приходят из PHP-метода <code class="rounded bg-slate-100 px-1">chartData()</code>, рисует Chart.js.</p>

        <div wire:ignore class="mt-4 h-64">
            <canvas id="visitsChart"></canvas>
        </div>
    </section>
</div>

@script
<script>
    const chartPayload = $wire.chartData();
    const canvas = document.getElementById('visitsChart');

    if (canvas && window.initVisitsChart) {
        window.initVisitsChart(canvas, chartPayload);
    }
</script>
@endscript