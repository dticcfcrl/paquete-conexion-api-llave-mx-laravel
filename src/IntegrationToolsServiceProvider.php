<?php

namespace LlaveMX\IntegrationTools;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;

use LlaveMX\IntegrationTools\Http\Controllers\LlaveMXController;

class IntegrationToolsServiceProvider extends ServiceProvider
{
    /**
     * GUIA DE INTEGRACION
     * 
     * PASO 1. Instalar el paquete via composer
     *    > composer require cfcrl/llavemx-tools:@dev
     * PASO 2. Ejecutar el comando para migraciones
     *    > php artisan migrate
     * PASO 3. (AUTOMÁTICO) Incluir la hoja de estilo _llavemx.scss en resources/sass/app.scss
     *   ...
     *   @import 'llavemx';
     * PASO 4. Ejecutar el comando de compilación de assets
     *    > npm run build
     * PASO 5. (AUTOMÁTICO) Agregar las variables de entorno al archivo .env
     *   #LlaveMX BASE
     *   LLAVE_CLIENT_ID=202508101834311980
     *   LLAVE_SECRET_CODE=GHMfq4DijIh8y1VlXiJ5Q19LTt8LHz
     *   LLAVE_BASICAUTH_USER=admin
     *   LLAVE_BASICAUTH_PASSWORD=admin
     *   LLAVE_URL_REDIRECT=http://registrolocal.centrolaboral.gob.mx/llavemx/callback #http://127.0.0.1:8000/llavemx/callback
     *   LLAVE_ENDPOINT=https://val-llave.infotec.mx/
     *   #LlaveMX viejo login
     *   LLAVE_VIEJO_LOGIN_DATE=2025-08-01
     *   LLAVE_VIEJO_LOGIN_SOLICITANTE=true
     *   LLAVE_VIEJO_LOGIN_FUNCIONARIO=true
     *   #LlaveMX endpoints
     *   LLAVE_ENDPOINT_LOGIN=oauth.xhtml
     *   LLAVE_ENDPOINT_GETTOKEN=ws/rest/oauth/obtenerToken
     *   LLAVE_ENDPOINT_GETUSER=ws/rest/oauth/datosUsuario
     *   LLAVE_ENDPOINT_GETROLES=ws/rest/oauth/getRolesUsuarioLogueado
     *   LLAVE_ENDPOINT_LOGOUT=ws/rest/oauth/cerrarSesion
     *   LLAVE_ENDPOINT_CREATEACCOUNT=RegistroCiudadano.xhtml
     * PASO 6. Ajustar la vista de login (resources/views/auth/login.blade.php) para cortar el login viejo e incluir el partial login de llavemx 
     *   ...
     *   @include('llavemx.partials.login')
     * PASO 7. El login viejo colocarlo en la vista login_old de llavemx (resources/views/llavemx/partials/login_old.blade.php)
     * PASO 8. Incorporar la ruta llavemx en el route service provider (app/Providers/RouteServiceProvider.php)
     *    dentro del metodo map()
     *   public function map()
     *   {
     *     ...
     *     $this->mapLlaveMXRoutes();
     * 	 }
     *   ...
     * 	 protected function mapLlaveMXRoutes()
     *   {
     *       Route::prefix('llavemx')
     *           ->middleware('web')
     *           ->namespace($this->namespace)
     *           ->group(base_path('routes/llavemx.php'));
     *   }
     * PASO 9. Agregar el helper de LlaveMX en composer.json
     *   ...
     *   "autoload": {
     *       ...
     *       "files": [
     *          "app/Helpers/llavemx.php"
     *       ]
     *    },
     * PASO 10. Ajustar el header para mostrar la opción de cambiar de usuario si se detecta que tiene varios roles
     *          en las vistas admin_header (resources/view/layouts/admin_header.blade.php) y header (resources/view/layouts/header.blade.php)
     * PASO 11. (NO APLICAR EN EL NUEVO CORE) Ajuste en el service core (app/Services/CoreService.php) para encapsular en try-catch la obtención del usuario autenticado del core
     *  ...
     *  public function __construct()
     *  {
     *  ...
     *  if (!$token || Carbon::now()->diffInHours($token->updated_at) > 2) {
     *          try{
     *            $response = $this->http->post($this->url . 'oauth/token', [
     *            ...
     *          } catch (Exception $e) {
     *              $this->token = '';
     *          }
     *      } else {
     *          $this->token = $token->token;
     *      }
     *  }
     */

    public function register()
    {
        
    }

    public function boot()
    {
        $helper_llavemx = base_path('app/Helpers/llavemx.php');
        if (!file_exists($helper_llavemx)) {
            $this->integrateFiles();

            //$this->loadViewsFrom(__DIR__.'/resources/views/llavemx', 'llavemx-views');
            //$this->loadRoutesFrom(__DIR__.'/routes/llavemx.php');

            $this->addEnvData();
            $this->addSassData();
        }
        $this->registerRoutes();
        $this->loadHelpers();
    }

    private function registerRoutes()
    {
        Route::prefix('llavemx')
            ->middleware('web')
            ->namespace('App\Http\Controllers')
            ->group(base_path('routes/llavemx.php'));
    }
    
    protected function loadHelpers()
    {
        foreach (glob(base_path('app/Helpers').'/*.php') as $helper) {
            require_once $helper;
        }
    }

    private function integrateFiles(){
        //Helpers
        $source = __DIR__.'/app/Helpers';
        $destination = base_path('app/Helpers');
        (new \Illuminate\Filesystem\Filesystem)->copyDirectory($source, $destination);
        //Controllers
        $source = __DIR__.'/app/Http/Controllers';
        $destination = base_path('app/Http/Controllers');
        (new \Illuminate\Filesystem\Filesystem)->copyDirectory($source, $destination);
        /*
        //Models
        $source = __DIR__.'/app/Models';
        $destination = base_path('app/Models');
        (new \Illuminate\Filesystem\Filesystem)->copyDirectory($source, $destination);
        //Migrations
        $source = __DIR__.'/database/migrations';   
        $destination = base_path('database/migrations');
        (new \Illuminate\Filesystem\Filesystem)->copyDirectory($source, $destination);
        */
        //Services
        $source = __DIR__.'/app/Services';
        $destination = base_path('app/Services');
        (new \Illuminate\Filesystem\Filesystem)->copyDirectory($source, $destination);
        //Public assets
        $source = __DIR__.'/public/images';
        $destination = base_path('public/images');
        (new \Illuminate\Filesystem\Filesystem)->copyDirectory($source, $destination);
        //Views
        $source = __DIR__.'/resources/views';
        $destination = resource_path('views');
        (new \Illuminate\Filesystem\Filesystem)->copyDirectory($source, $destination);
        //SASS
        $source = __DIR__.'/resources/sass';
        $destination = resource_path('sass');
        (new \Illuminate\Filesystem\Filesystem)->copyDirectory($source, $destination);
        //Routes
        $source = __DIR__.'/routes';
        $destination = base_path('routes');
        (new \Illuminate\Filesystem\Filesystem)->copyDirectory($source, $destination);
    }

    private function registerPublishing()
    {
        if ($this->app->runningInConsole()) {
            //Publicando las vistas
            $this->publishes([
                __DIR__.'/resources/views/llavemx' => resource_path('views/llavemx'), // Esto va directo a resources/views
            ], 'llavemx-views');
            //Publicando los assets
            $this->publishes([
                __DIR__.'/resources/sass' => base_path('resources/sass'),
            ], 'llavemx-sass');
            /*
            //Publicando las migraciones
            $this->publishes([
                __DIR__.'/database/migrations' => base_path('database/migrations'),
            ], 'llavemx-migrations');
            //Publicando los modelos
            $this->publishes([
                __DIR__.'/app/Models' => base_path('app/Models'),
            ], 'llavemx-models');
            */
            //Publicando los servicios
            $this->publishes([
                __DIR__.'/app/Services' => base_path('app/Services'),
            ], 'llavemx-services');
            //Publicando los controllers
            $this->publishes([
                __DIR__.'/app/Http/Controllers' => base_path('app/Http/Controllers'),
            ], 'llavemx-controllers');
            //Publicando los public assets
            $this->publishes([
                __DIR__.'/public/images' => base_path('public/images'),
            ], 'llavemx-public');
            //Publicando helpers
            $this->publishes([
                __DIR__.'/helpers/helpers.php' => base_path('app/Helpers/integrationtools.php'),
            ], 'llavemx-helpers');
        }
    }

    protected function addEnvData()
    {
        $envPath = base_path('.env');
        if (!file_exists($envPath)) {
            return; // Evita errores si el archivo no existe
        }

        $envContent = file_get_contents($envPath);
        $vars = [
            'LLAVE_APP_NAME' => '',
            'LLAVE_CLIENT_ID' => '',
            'LLAVE_SECRET_CODE' => '',
            'LLAVE_BASICAUTH_USER' => '',
            'LLAVE_BASICAUTH_PASSWORD' => '',
            'LLAVE_URL_REDIRECT' => 'http://[MIAPP].centrolaboral.gob.mx/llavemx/callback',
            'LLAVE_ENDPOINT' => 'https://val-llave.infotec.mx/',
            'LLAVE_VIEJO_LOGIN_DATE' => '2025-08-01',
            'LLAVE_ENDPOINT_LOGIN' => 'oauth.xhtml',
            'LLAVE_ENDPOINT_GETTOKEN' => 'ws/rest/oauth/obtenerToken',
            'LLAVE_ENDPOINT_GETUSER' => 'ws/rest/oauth/datosUsuario',
            'LLAVE_ENDPOINT_GETROLES' => 'ws/rest/oauth/getRolesUsuarioLogueado',
            'LLAVE_ENDPOINT_GETMORALES' => 'ws/rest/perfil/moral',
            'LLAVE_ENDPOINT_LOGOUT' => 'ws/rest/oauth/cerrarSesion',
            'LLAVE_ENDPOINT_CREATEACCOUNT' => 'RegistroCiudadano.xhtml',
            'LLAVE_ACCOUNT_LIMIT' => '20',
            'LLAVE_CORE_API_URL' => '"${CORE_API_URL}"',
            'LLAVE_CORE_CLIENT_ID' => '${CLIENT_ID}',
            'LLAVE_CORE_CLIENT_SECRET' => '${CLIENT_SECRET}',
        ];

        foreach ($vars as $key => $default) {
            if (!str_contains($envContent, $key)) {
                $envContent .= "\n{$key}={$default}";
            }
        }
        file_put_contents($envPath, $envContent);
    }

    protected function addSassData()
    {
        $sassPath = base_path('resources/sass/app.scss');
        if (!file_exists($sassPath)) {
            return; // Evita errores si el archivo no existe
        }

        $sassContent = file_get_contents($sassPath);
        if (!str_contains($sassContent, 'llavemx')) {
            $sassContent .= "\n@import 'llavemx';";
        }
        file_put_contents($sassPath, $sassContent);
    }
}