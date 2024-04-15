<?php
return [
    'uploads' => [
        'path' => env('UPLOADS_PATH', '../uploads/files'),
    ],
    'images'  => [
        'path'              => env('IMAGES_PATH', '../uploads/images'),
        'resized_path'      => env('IMAGES_RESIZED_PATH', '../cache/images'),
        'supported_sizes'   => env('IMAGES_SUPPORTED_SIZES', '300,600,900'),
        'remove_metadata'   => env('IMAGES_REMOVE_METADATA', TRUE),
        'auto_resize'       => env('IMAGES_AUTO_RESIZE', FALSE),
        'auto_resize_width' => env('IMAGES_AUTO_RESIZE_WIDTH', 2048),
    ]
];
