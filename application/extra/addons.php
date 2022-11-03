<?php

return [
    'autoload' => false,
    'hooks' => [
        'app_init' => [
            'banip',
            'epay',
        ],
        'config_init' => [
            'summernote',
        ],
    ],
    'route' => [],
    'priority' => [],
    'domain' => '',
];
