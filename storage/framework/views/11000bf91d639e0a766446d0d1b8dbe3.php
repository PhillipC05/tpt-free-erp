<!DOCTYPE html>
<html lang="<?php echo e(str_replace('_', '-', app()->getLocale())); ?>">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
        <meta name="mobile-web-app-capable" content="yes">
        <meta name="apple-mobile-web-app-capable" content="yes">
        <meta name="apple-mobile-web-app-status-bar-style" content="default">
        <meta name="theme-color" content="#2563eb">
        <title><?php echo e(config('app.name', 'TPT Free ERP')); ?></title>
        <link rel="manifest" href="/manifest.json">
        <?php echo app('Illuminate\Foundation\Vite')(['resources/css/app.css', 'resources/js/main.ts']); ?>
    </head>
    <body>
        <div id="app"></div>
    </body>
</html>
<?php /**PATH C:\Programming\2 WIP\tpt-free-erp\resources\views/app.blade.php ENDPATH**/ ?>