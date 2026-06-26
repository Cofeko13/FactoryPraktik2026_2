<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo $__env->yieldContent('title', 'Analytics Dashboard'); ?></title>
    <?php echo app('Illuminate\Foundation\Vite')(['resources/css/app.css', 'resources/js/app.js']); ?>
    <?php echo \Livewire\Mechanisms\FrontendAssets\FrontendAssets::styles(); ?>

</head>
<body class="min-h-screen bg-slate-50 text-slate-900 antialiased">
    <header class="border-b border-slate-200 bg-white">
        <div class="mx-auto flex max-w-6xl items-center justify-between px-6 py-4">
            <a href="<?php echo e(route('dashboard')); ?>" class="text-lg font-semibold text-slate-900">
                Analytics Dashboard
            </a>
            <p class="text-sm text-slate-500">Laravel · Livewire · Chart.js</p>
        </div>
    </header>

    <main class="mx-auto max-w-6xl px-6 py-10">
        <?php echo $__env->yieldContent('content'); ?>
    </main>

    <?php echo \Livewire\Mechanisms\FrontendAssets\FrontendAssets::scripts(); ?>

</body>
</html>
<?php /**PATH /Users/panda/Projects/analytics-dashboard/resources/views/layouts/app.blade.php ENDPATH**/ ?>