<?php

return [

    'paths' => [

        'api/*',
        'broadcasting/auth'

    ],

    'allowed_methods' => ['*'],

    'allowed_origins' => [

       'http://localhost:8100',
        'http://127.0.0.1:8100',
        
        'http://localhost:8101',
        'http://127.0.0.1:8101',
        
        'https://localhost',
        'capacitor://localhost',

        'https://layananapp.my.id',
        'https://www.layananapp.my.id',

    ],

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => true,

];