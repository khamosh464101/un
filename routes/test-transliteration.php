<?php

use Illuminate\Support\Facades\Route;
use App\Helpers\Transliterator;

Route::get('/test-transliteration', function () {
    $tests = [
        'Aman' => Transliterator::toPersian('Aman'),
        'Ahmad' => Transliterator::toPersian('Ahmad'),
        'Mohammad' => Transliterator::toPersian('Mohammad'),
        'Abdullah' => Transliterator::toPersian('Abdullah'),
        'Hassan' => Transliterator::toPersian('Hassan'),
        'Khalid' => Transliterator::toPersian('Khalid'),
    ];
    
    return response()->json([
        'message' => 'Transliteration Test',
        'results' => $tests,
        'class_exists' => class_exists('\App\Helpers\Transliterator'),
    ]);
});
