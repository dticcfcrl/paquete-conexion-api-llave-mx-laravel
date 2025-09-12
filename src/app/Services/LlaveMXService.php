<?php

namespace App\Services;

use GuzzleHttp\Client;
use Carbon\Carbon;
use Auth;
use Illuminate\Support\Facades\{Log};

class LlaveMXService
{
    private $http;
    private $url;

    public function __construct()
    {
        $this->http = new Client();
        $this->url = env('LLAVE_ENDPOINT');
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
            if(!isset($token['accessToken']))
                return false;

            return $token['accessToken'];
        }catch(\GuzzleHttp\Exception\ClientException $e) {
            Log::error('ClientException en getToken(): '.$e->getMessage());
            return false;
        } catch (\GuzzleHttp\Exception\BadResponseException $e) {
            Log::error('BadResponseException en getToken(): '.$e->getMessage());
            return false;
        } catch (\Exception $e) {
            Log::error('Exception en getToken(): '.$e->getMessage());
            return false;
        }
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
        }catch(\GuzzleHttp\Exception\ClientException $e) {
            Log::error('ClientException en getUser(): '.$e->getMessage());
            return false;
        } catch (\GuzzleHttp\Exception\BadResponseException $e) {
            Log::error('BadResponseException en getUser(): '.$e->getMessage());
            return false;
        } catch (\Exception $e) {
            Log::error('Exception en getUser(): '.$e->getMessage());
            return false;
        }
        return false;
    }
}
