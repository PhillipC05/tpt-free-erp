<?php

use Illuminate\Support\Facades\Route;

Route::middleware('csrf.protect')->group(function () {
    Route::get('/', function () {
        return view('welcome');
    });
});
