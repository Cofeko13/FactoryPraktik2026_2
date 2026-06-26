<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames((['label', 'value', 'color' => 'slate']));

foreach ($attributes->all() as $__key => $__value) {
    if (in_array($__key, $__propNames)) {
        $$__key = $$__key ?? $__value;
    } else {
        $__newAttributes[$__key] = $__value;
    }
}

$attributes = new \Illuminate\View\ComponentAttributeBag($__newAttributes);

unset($__propNames);
unset($__newAttributes);

foreach (array_filter((['label', 'value', 'color' => 'slate']), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars, $__key, $__value); ?>

<?php
    $colors = [
        'indigo' => 'text-indigo-600',
        'emerald' => 'text-emerald-600',
        'slate' => 'text-slate-800',
    ];
?>

<div <?php echo e($attributes->merge(['class' => 'rounded-xl border border-slate-200 bg-white p-5 shadow-sm'])); ?>>
    <p class="text-sm text-slate-500"><?php echo e($label); ?></p>
    <p class="mt-1 text-3xl font-bold tabular-nums <?php echo e($colors[$color] ?? $colors['slate']); ?>">
        <?php echo e(number_format($value)); ?>

    </p>
</div>
<?php /**PATH /Users/panda/Projects/analytics-dashboard/resources/views/components/stat-card.blade.php ENDPATH**/ ?>