<?php

use App\Http\Controllers\ApiLlaveMXController;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;

Route::get('callback', [ApiLlaveMXController::class, 'callback']);
Route::get('selector', [ApiLlaveMXController::class, 'selector'])->name('llavemx.selector');
Route::get('login/{hash_user_id}', [ApiLlaveMXController::class, 'loginSelector'])->name('llavemx.loginSelector');
Route::get('login', [ApiLlaveMXController::class, 'login'])->name('llavemx.login');
Route::get('register', [ApiLlaveMXController::class, 'register'])->name('llavemx.register');
Route::post('new-account', [ApiLlaveMXController::class, 'newAccount'])->name('llavemx.newAccount');
