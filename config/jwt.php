<?php

return [
    /*
    |--------------------------------------------------------------------------
    | JWT Secret Key
    |--------------------------------------------------------------------------
    | Kunci rahasia yang digunakan untuk sign dan verify JWT token.
    | Set nilai ini di file .env sebagai JWT_SECRET_KEY=your-secret-key
    */
    'secret' => env('JWT_SECRET_KEY'),

    /*
    |--------------------------------------------------------------------------
    | JWT TTL (Time To Live) dalam jam
    |--------------------------------------------------------------------------
    | Berapa lama token berlaku setelah diterbitkan, dalam satuan jam.
    | Default: 2 jam jika JWT_TTL_HOURS tidak di-set di .env
    */
    'ttl_hours' => env('JWT_TTL_HOURS', 2),
];
