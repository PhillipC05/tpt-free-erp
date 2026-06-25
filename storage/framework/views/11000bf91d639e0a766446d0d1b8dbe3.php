<!DOCTYPE html>
<html lang="<?php echo e(str_replace('_', '-', app()->getLocale())); ?>">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title><?php echo e(config('app.name', 'TPT Free ERP')); ?></title>
        <?php echo app('Illuminate\Foundation\Vite')(['resources/css/app.css', 'resources/js/main.ts']); ?>
    </head>
    <body>
        <div id="app"></div>
    </body>
</html>
<?php /**PATH C:\Programming\2 WIP\tpt-free-erp\resources\views/app.blade.php ENDPATH**/ ?>