<?php

use Illuminate\Support\Facades\Route;

Route::post('webhook', 'GidxController@handleWebhook')->name('webhook');
//Route::post('documents', 'GidxController@uploadDocument')->name('documents');
