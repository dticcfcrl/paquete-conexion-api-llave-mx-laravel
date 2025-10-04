<?php

use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;

if (!function_exists('llaveMXGeneraState')){
  /**
   * llaveMXGeneraState: Es un metodo que permite la generación del state para el servicio de Llave MX y la registra 
   * en la tabla de llavemx_states para su posterior validación
   */
  function llaveMXGeneraState($length = 65){
    $state = Str::random(64);
    Session::put('state_csrf', $state);
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
