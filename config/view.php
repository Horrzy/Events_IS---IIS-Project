<?php

return [

    // Directories searched for view templates
    'paths' => [
        resource_path('views'),
    ],

    // Directory for compiled Blade templates
    'compiled' => env(
        'VIEW_COMPILED_PATH',
        realpath(storage_path('framework/views'))
    ),

];
