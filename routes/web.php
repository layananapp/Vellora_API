<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Mail;

Route::get('/test-mail', function () {

    Mail::raw(
        'Test Email Vellora',
        function ($message) {

            $message->to('mriangame1@gmail.com')
                    ->subject('Test SMTP');

        }
    );

    return 'Email terkirim';

});

Route::get('/', function () {
    return response()->json([
        'status' => true,
        'message' => 'Vellora API Running'
    ]);
});