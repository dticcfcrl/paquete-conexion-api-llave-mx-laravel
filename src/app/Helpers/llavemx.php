<?php

use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;

if (!function_exists('llaveMXGeneraState')){
  /**
   * llaveMXGeneraState: Es un método que permite la generación del state para el servicio de Llave MX y la registra 
   * en la tabla de llavemx_states para su posterior validación
   */
  function llaveMXGeneraState(){
    $state = Str::random(64);
    Session::put('state_csrf', $state);
    return $state;
  }
}

use Illuminate\Support\Facades\Crypt;

if (!function_exists('llaveMXEncryptString')){
  /**
   * llaveMXEncryptString: Es un método que encripta un dato usando Crypt
   */
  function llaveMXEncryptString($data){
    return Crypt::encryptString($data);
  }
}

if (!function_exists('llaveMXAccountEnabled')){
  /**
   * llaveMXAccountEnabled: Es un método que valida si la cuenta tiene un correo verificado
   */
  function llaveMXAccountEnabled($correo)
  {
    $result = true;
    try{
      $solicitud = \App\Models\UsuarioSolicitud::whereCorreo($correo)->first();
      if (isset($solicitud->id)) {
        if (!$solicitud->se_registro){
          $result = false;
        }
      }
    } catch (\Exception $e) {}
    return $result;
  }
}