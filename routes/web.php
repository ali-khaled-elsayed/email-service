<?php

use Illuminate\Support\Facades\Route;

Route::get('/up', function () {
    return response('OK', 200);
});

Route::get('/', function () {
    return redirect('/admin');
});
