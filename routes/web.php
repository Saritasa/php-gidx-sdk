<?php

use Illuminate\Support\Facades\Route;

Route::post('webhook', 'GidxController@handleWebhook')->name('webhook');
