<?php

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response()->file(public_path('index.html'));
});

Route::fallback(function () {
    $index = public_path('index.html');

    if (File::exists($index)) {
        return response()->file($index);
    }

    abort(404);
});

// Route::get('/', function () {
//     return file_get_contents(public_path('dist/index.html'));
// })->where('any', '.*');

// Route::get('/{any}', function () {
//     return file_get_contents(public_path('dist/index.html'));
// })->where('any', '.*');
