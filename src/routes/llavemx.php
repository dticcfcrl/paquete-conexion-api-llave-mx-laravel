<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;

Route::get('callback', 'ApiLlaveMXController@callback');
Route::get('selector', 'ApiLlaveMXController@selector')->name('llavemx.selector');
Route::get('login/{hash_user_id}', 'ApiLlaveMXController@loginSelector')->name('llavemx.loginSelector');
Route::get('logout', 'ApiLlaveMXController@logout')->name('llavemx.logout');
