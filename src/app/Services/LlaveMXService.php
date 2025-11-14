<?php

namespace App\Services;

use GuzzleHttp\Client;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\{Log};
use App\Models\{AccessToken};

class LlaveMXService
{
    private $http;
    private $url;
    private $url_core;
    private $token_core;
    private $client_id;
    private $client_secret;

    public function __construct()
    {
        $this->http = new Client();
        $this->url = env('LLAVE_ENDPOINT');
        $this->url_core = env('LLAVE_CORE_API_URL');
        $this->client_id = env('LLAVE_CORE_CLIENT_ID');
        $this->client_secret = env('LLAVE_CORE_CLIENT_SECRET');
        //Seteamos el token para acceder al core
        $this->token_core = $this->getTokenCore();
    }

    private function getTokenCore()
    {
        $token = '';
        $access_token = AccessToken::whereServicio('core-usuarios')->first();
        if (!$access_token || Carbon::now()->diffInHours($access_token->updated_at) > 2) {
            try{
                $response = $this->http->post($this->url_core . 'oauth/token', [
                    'form_params'       => [
                        'client_id'         => $this->client_id,
                        'client_secret'     => $this->client_secret,
                        'scope'             => '*',
                        'grant_type'        => 'client_credentials'
                    ],
                    'verify' => env('LLAVE_VERIFY_SSL', true)
                ]);
                $result = json_decode((string)$response->getBody(), true);
                if (!isset($result['access_token'])) {
                    return ['code_error' => 401, 'messages' => ['token' => 'error al generar el token']];
                }
                $formato = AccessToken::updateOrCreate(
                    ['servicio' => 'core-usuarios'],
                    [
                        'servicio'  => 'core-usuarios',
                        'token'     => $result['access_token'],
                    ]
                );
                $token = $result['access_token'];
            } catch (Exception $e) {
                $token = '';
            }
        } else {
            $token = $access_token->token;
        }
        return $token;
    }

    /**
     * validar credenciales en el core
     */
    public function loginInCore($form_params)
    {
        try {
            $response = $this->http->request('POST', $this->url. 'api/usuarios/autenticar', [
                'headers' => [
                    'Accept' => 'application/json',
                    'Authorization' => 'Bearer ' . $this->token_core
                ],

                'form_params' => $form_params,
                'verify' => env('LLAVE_VERIFY_SSL', true)
            ]);
            return json_decode((string)$response->getBody(), true);
        } catch (\GuzzleHttp\Exception\ClientException $e) {
            $response = $e->getResponse()->getBody(true);
            return json_decode((string)$response, true);
        }
    }

    /**
     * Crear usuario en el core
     */
    public function registerUserInCore($form_params, $token = '')
    {
        $token = $token == '' ? $this->token : $token;

        try {
            $response = $this->http->post($this->url.'api/usuarios/registro', [
                'headers' => [
                    'Accept'        => 'application/json',
                    'Authorization' => 'Bearer '.$token
                ],
                'form_params' => $form_params,
                'verify' => env('LLAVE_VERIFY_SSL', true)
            ]);
            return json_decode((string)$response->getBody(), true);
        }catch(\GuzzleHttp\Exception\ClientException $e) {
            $response = $e->getResponse()->getBody(true);
            return ['code_error' => $e->getResponse()->getStatusCode(), 'messages' => json_decode((string)$response, true)];
        }
    }

    /**
     * Guardar la información del usuario en el CORE
     */
    public function storeDataAtCore($data_user, $data_morales)
    {
        $data = false;
        if(isset($data)){
            //Solicitar el core el almacenamiento del usuario
            try {
                $csrf_token = csrf_token();
                $response = $this->http->post($this->url_core.'api/llavemx/store-data', [
                    'headers' => [
                        'Accept'        => 'application/json',
                        'Authorization' => 'Bearer '.$this->token_core
                    ],
                    'form_params' => [
                        '_token' => $csrf_token,
                        'user' => $data_user,
                        'personas_morales' => $data_morales
                    ],
                    'verify' => env('LLAVE_VERIFY_SSL', true)
                ]);
                $data = json_decode((string)$response->getBody(), true);
                //Si viene un message ocurrio un error al guardar los datos del usuario en el core
                if (isset($data['message'])) $data = false;
            }catch(Exception $e) {
                $data = false;
            }
        }
        return $data;
    }

    /**
     * Buscar la información del usuario en el CORE
     */
    public function searchUsersAtCore($data_user){
        $data = false;
        if(isset($data)){
            try {
                $csrf_token = csrf_token();
                $response = $this->http->post($this->url_core.'api/llavemx/search-user', [
                    'headers' => [
                        'Accept'        => 'application/json',
                        'Authorization' => 'Bearer '.$this->token_core
                    ],
                    'form_params' => [
                        '_token' => $csrf_token,
                        'curp' => $data_user['curp'],
                        'correo' => $data_user['correo'],
                        'nombre' => $data_user['nombre'],
                        'primerApellido' => $data_user['primerApellido'],
                        'segundoApellido' => $data_user['segundoApellido']
                    ],
                    'verify' => env('LLAVE_VERIFY_SSL', true)
                ]);
                $data = json_decode((string)$response->getBody(), true);
                if (isset($data['message'])) $data = false;
            }catch(Exception $e) {
                $data = false;
            }
        }
        return $data;
    }

    /**
     * Convertir el code en token
     */
    public function getToken($code)
    {
        try {
            $body = [
                    'clientId' => env('LLAVE_CLIENT_ID'), 
                    'clientSecret' => env('LLAVE_SECRET_CODE'),
                    'code'      => $code,
                    'redirectUri' => env('LLAVE_URL_REDIRECT'),
                    'grantType' => 'authorization_code',
            ];
            $response = $this->http->post($this->url.env('LLAVE_ENDPOINT_GETTOKEN'), [
                'auth' => [env('LLAVE_BASICAUTH_USER'), env('LLAVE_BASICAUTH_PASSWORD')],
                'json' => $body,
                'headers' => [
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                ],
            ]);
            $token = json_decode((string)$response->getBody(), true);
            if(!isset($token['accessToken'])) return false;
            return $token['accessToken'];
        }catch(\GuzzleHttp\Exception\ClientException $e) {} catch (\GuzzleHttp\Exception\BadResponseException $e) {} catch (\Exception $e) {}
        return false;
    }

    /**
     * Recuperar la información del usuario con el token
     */
    public function getUser($token)
    {
        try {
            $response = $this->http->get($this->url.env('LLAVE_ENDPOINT_GETUSER'), [
                'auth' => [env('LLAVE_BASICAUTH_USER'), env('LLAVE_BASICAUTH_PASSWORD')],
                'headers' => [
                    'accessToken' => $token
                ]
            ]);
            return json_decode((string)$response->getBody(), true);
        }catch(\GuzzleHttp\Exception\ClientException $e) {} catch (\GuzzleHttp\Exception\BadResponseException $e) {} catch (\Exception $e) {}
        return false;
    }

    /**
     * Recuperar la información de las personas morales vinculadas al usuario con el token
     */
    public function getPersonasMorales($token)
    {
        try {
            $response = $this->http->get($this->url.env('LLAVE_ENDPOINT_GETMORALES'), [
                'auth' => [env('LLAVE_BASICAUTH_USER'), env('LLAVE_BASICAUTH_PASSWORD')],
                'headers' => [
                    'accessToken' => $token
                ]
            ]);
            return json_decode((string)$response->getBody(), true);
        }catch(\GuzzleHttp\Exception\ClientException $e) {
            //Log::error('ClientException en getUser(): '.$e->getMessage());
        } catch (\GuzzleHttp\Exception\BadResponseException $e) {
            //Log::error('BadResponseException en getUser(): '.$e->getMessage());
        } catch (\Exception $e) {
            //Log::error('Exception en getUser(): '.$e->getMessage());
        }
        return false;
    }

    /**
     * Forzar el cierre de sesión del usuario en LlaveMX
     */
    public function closeSession($token)
    {
        try {
            $response = $this->http->post($this->url.env('LLAVE_ENDPOINT_LOGOUT'), [
                'auth' => [env('LLAVE_BASICAUTH_USER'), env('LLAVE_BASICAUTH_PASSWORD')],
                'headers' => [
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                    'accessToken' => $token
                ],
            ]);
            return json_decode((string)$response->getBody(), true);
        }catch(\GuzzleHttp\Exception\ClientException $e) {
            //Log::error('ClientException en getUser(): '.$e->getMessage());
        } catch (\GuzzleHttp\Exception\BadResponseException $e) {
            //Log::error('BadResponseException en getUser(): '.$e->getMessage());
        } catch (\Exception $e) {
            //Log::error('Exception en getUser(): '.$e->getMessage());
        }
        return false;
    }
}
