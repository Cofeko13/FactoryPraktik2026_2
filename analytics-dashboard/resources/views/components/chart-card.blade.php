@props(['title', 'canvasId'])

<div {{ $attributes->merge(['class' => 'rounded-xl border border-slate-200 bg-white p-5 shadow-sm']) }}>
    <h2 class="font-semibold">{{ $title }}</h2>
    <div wire:ignore class="mt-4 h-64">
        <canvas id="{{ $canvasId }}"></canvas>
    </div>
</div>
