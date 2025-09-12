<?php

use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Str;

if (!function_exists('llaveMXGeneraState')){
  /**
   * llaveMXGeneraState: Es un metodo que permite la generación del state para el servicio de Llave MX y la registra 
   * en la tabla de llavemx_states para su posterior validación
   */
  function llaveMXGeneraState($length = 65){
    $state = Str::random(64);
    Cookie::queue('state_csrf', $state, 1440);
    \App\Models\LlaveMXState::create(['application_return' => url('/').'/llavemx/login', 'state' => $state, 'is_used' => false]);
    return $state;
  }
}

use Illuminate\Support\Facades\Crypt;

if (!function_exists('llaveMXEncryptString')){
  /**
   * llaveMXEncryptString: Es un metodo que encripta un dato usando Crypt
   */
  function llaveMXEncryptString($data){
    return Crypt::encryptString($data);
  }
}
