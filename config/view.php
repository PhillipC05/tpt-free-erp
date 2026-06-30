<?php

return [

    /*
    |--------------------------------------------------------------------------
    | View Storage Paths
    |--------------------------------------------------------------------------
    |
    | Most templating systems load templates from disk. Here you may specify
    | an array of paths that should be checked for your views. Of course
    | the usual Laravel view path has already been registered for you.
    |
    */

    'paths' => [
        resource_path('views'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Compiled View Path
    |--------------------------------------------------------------------------
    |
    | This option determines where all the compiled Blade templates will be
    | stored for your application. Typically, this is within the storage
    | directory. However, as usual, you are free to change this value.
    |
    */

    'compiled' => env(
        'VIEW_COMPILED_PATH',
        storage_path('framework/views')
    ),

    /*
    |--------------------------------------------------------------------------
    | Cache
    |--------------------------------------------------------------------------
    |
    | This option determines if Blade templates should be cached. Setting
    | this to false will disable caching and recompile templates on every
    | request. This can be useful during development.
    |
    */

    'cache' => env('BLADE_CACHE', true),

    /*
    |--------------------------------------------------------------------------
    | Compiled Extension
    |--------------------------------------------------------------------------
    |
    | This option determines the file extension for compiled Blade templates.
    | The default is 'php', which works well with the standard setup.
    |
    */

    'compiled_extension' => env('VIEW_COMPILED_EXTENSION', 'php'),

    /*
    |--------------------------------------------------------------------------
    | Check Timestamps
    |--------------------------------------------------------------------------
    |
    | When enabled, Blade will check if the original view file has been modified
    | since the compiled version was created. If so, the template will be
    | recompiled. You may disable this in production for better performance.
    |
    */

    'check_cache_timestamps' => env('VIEW_CHECK_CACHE_TIMESTAMPS', true),

    /*
    |--------------------------------------------------------------------------
    | Relative Hash
    |--------------------------------------------------------------------------
    |
    | When enabled, Blade will generate hashes relative to the application
    | base path, rather than using the full absolute path. This can be
    | useful when deploying to multiple environments.
    |
    */

    'relative_hash' => env('VIEW_RELATIVE_HASH', false),

];
