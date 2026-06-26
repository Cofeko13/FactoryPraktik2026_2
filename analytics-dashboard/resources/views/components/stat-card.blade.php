@props(['label', 'value', 'color' => 'slate'])

@php
    $colors = [
        'indigo' => 'text-indigo-600',
        'emerald' => 'text-emerald-600',
        'slate' => 'text-slate-800',
    ];
@endphp

<div {{ $attributes->merge(['class' => 'rounded-xl border border-slate-200 bg-white p-5 shadow-sm']) }}>
    <p class="text-sm text-slate-500">{{ $label }}</p>
    <p class="mt-1 text-3xl font-bold tabular-nums {{ $colors[$color] ?? $colors['slate'] }}">
        {{ number_format($value) }}
    </p>
</div>
