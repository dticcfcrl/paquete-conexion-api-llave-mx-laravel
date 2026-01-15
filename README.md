# DTICCFCRL/paquete-conexion-api-llave-mx-laravel

Paquete del Centro Federal de Conciliación y Registros Laborales (CFCRL) para integración en aplicaciones Web al servicio de autentificación  LlaveMX.

## Info

- [Repositorio Github](https://github.com/dticcfcrl/paquete-conexion-api-llave-mx-laravel).
- Laravel ^7.0 | ^8.0 | ^9.0 | ^10.0 | ^11.0 | ^12.0 | ^13.0 
- PHP ^7.0 | ^8.0

## Estructura

El presente paquete dticcfcrl/paquete-conexion-api-llave-mx-laravel se encuentra integrado de los siguientes directorios:

```
config/
src/
```

## Instalación

### Instalación desde el repositorio GitHub (¡Recomendada!)

Modifique el composer.json del proyecto y añadir el repositorio del paquete DTICCFCRL/paquete-conexion-api-llave-mx-laravel para poder instalarlo mediante composer.
> **Nota:** Revise si ya tenía previamente la definición "minimum-stability", si es así solo deje una con el valor "dev". Tambien, revise la definición "prefer-stable" que no se duplique y tenga el valor "true". 
``` php
    ...
    "repositories": [
        {
            "type": "vcs",
            "url": "git@github.com:dticcfcrl/paquete-conexion-api-llave-mx-laravel.git"
        }
    ],
    "minimum-stability": "dev",
    "prefer-stable": true,
    ...
```
Ejecute el siguiente comando para instalar el paquete LlaveMX via Composer
``` shell
composer require dticcfcrl/paquete-conexion-api-llave-mx-laravel:v0.2.22
```

### Instalación desde el directorio local del repositorio (En caso de fallar la anterior)

En caso de tener problemas para instalar el paquete directamente desde GitHub, proceda a descargar el repositorio y dejarlo al mismo nivel que el directorio del proyecto en que esta trabajando y desea implementarle LlaveMX.

Modifique el composer.json del proyecto y añada la ruta (directorio) al paquete DTICCFCRL/paquete-conexion-api-llave-mx-laravel para poder instalarlo mediante Composer.
> **Nota:** Revise si ya tenía previamente la definición "minimum-stability", si es así solo deje una con el valor "dev". Tambien, revise la definición "prefer-stable" que no se duplique y tenga el valor "true".
``` php
    ...
    "repositories": {
        "dticcfcrl-local": {
            "type": "path",
            "url": "../paquete-conexion-api-llave-mx-laravel",
            "options": { "symlink": true }
        }
    },
    "minimum-stability": "dev",
    "prefer-stable": true,
    ...
```
Ejecute el siguiente comando para instalar el paquete LlaveMX via Composer
``` shell
composer require dticcfcrl/paquete-conexion-api-llave-mx-laravel
```
> **Nota:** La versión que se instalará dependerá de la versión que se tenga bajada del repositorio del paquete DTICCFCRL/paquete-conexion-api-llave-mx-laravel. 

### Desinstalación del paquete

Si requiere remover el paquete ejecute el comando:
``` bash
composer remove dticcfcrl/paquete-conexion-api-llave-mx-laravel

```
> \*\*Nota:\*\* El proceso de instalación coloca en los directorios de vistas, controladores, rutas, helpers y services archivos de la funcionalidad de LlaveMX preconstruida. Si procede a desinstalar estos no serán removidos por lo que deberá eliminarlos manualmente.

## Uso

Una vez instalado el paquete y que se han desplegado en el proyecto las vistas, controladores, rutas, helpers y services de la funcionalidad de LlaveMX proceda con los siguientes pasos para integrarlo apropiadamente a su proyecto.

- **Paso 1:**  Ejecute el comando de compilación del proyecto dado que el paquete integra una hoja de estilos que ya ha sido añadida (resources/sass/app.scss).
``` shell
npm run build
```
> **Nota:** Sino reconoce el comando "build" use el comando "prod".
- **Paso 2:**  Ajuste las variables .env (estan alginal del archivo e inician con LLAVE_XXXX) y limpiar la cache. 
> **Nota:** Al instalar el paquete por primer vez, se agregan 19 variables al archivo .env que apoyan a la funcionalidad de LlaveMX. De esas variables solo debe ajustar las primeras 6 con los datos de la credencial de la aplicación para LlaveMX. 

| Variable                  | Descripción                                                                                    |
|---------------------------|------------------------------------------------------------------------------------------------|
| LLAVE_APP_NAME            | String del nombre de la aplicación que se mostrará en el login                                 |
| LLAVE_CLIENT_ID           | Valor obtenido al realizar el registro de su aplicación en LlaveMX                             |
| LLAVE_SECRET_CODE         | Valor obtenido al realizar el registro de su aplicación en LlaveMX                             |
| LLAVE_BASICAUTH_USER      | Valor obtenido al realizar el registro de su aplicación en LlaveMX                             |
| LLAVE_BASICAUTH_PASSWORD  | Valor obtenido al realizar el registro de su aplicación en LlaveMX                             |
| LLAVE_URL_REDIRECT        | URL de su aplicación (debe concluir con /llavemx/callback), el callback lo integra el paquete  |

> **Nota:** Si su aplicación maneja comunicación con el CORE revise que las variables en el .env sean CORE_API_URL, CLIENT_ID y CLIENT_SECRET. En caso contrario ajustar las variables LLAVE_CORE_API_URL, LLAVE_CORE_CLIENT_ID y LLAVE_CORE_CLIENT_SECRET a las variables que correspondan o asignar directamente los valores de la credencial Passport.
``` bash
#LlaveMX credenciales al Core
LLAVE_CORE_API_URL="${CORE_API_URL}"
LLAVE_CORE_CLIENT_ID=${CLIENT_ID}
LLAVE_CORE_CLIENT_SECRET=${CLIENT_SECRET}
```
``` shell
php artisan config:clear
```
> **Nota:** Si en su entorno local se producen problemas de SSL por las urls del servidor local tanto de su aplicación como del core puede usar la variable LLAVE_VERIFY_SSL=false con lo cual se forza que Laravel no haga validaciones SSL al consumir servicios en el backend.
- **Paso 3:** Ejecute las migraciones pendientes (se requiere la tabla access_token) para que se pueda guardar el token de acceso al core.
``` shell
php artisan migrate
```
- **Paso 4:** Ajuste la vista del login (resources/views/auth/login.blade.php) para cortar el login viejo e incluir el partial al login de LlaveMX.
``` php
@include('llavemx.partials.login')
```
- **Paso 5:** Guarde el login viejo en la vista login_old de LlaveMX (resources/views/llavemx/partials/login_old.blade.php).
> **Nota:** Si el partial login_old.blade.php tiene información favor de borrarla y colocar su script del login viejo. 
- **Paso 6:** Modifique el controller ApiLlaveMXController (app/Http/Controller/ApiLlaveMXController.php) revisando y corrigiendo la variable $home_login al url del login, el query de búsqueda de usuarios acorde a la estructura de seguridad del proyecto así como la sección de rutas acorde al rol una vez que se ha autentificado el usuario.
> **Nota:** Para facilitar la edición del controller busque los comentarios que indican "MODIFICAR:".
``` php
/*
* MODIFICAR:
* Ajustar a la pagina de inicio o login del sistema
*/
private $home_login = '/';
...
/*
* MODIFICAR:
* Ajustar segun estructura de usuarios del sistema
*/
$data = DB::select("SELECT u.id as user_id
                    FROM public.users u
                    WHERE (UPPER(u.curp) = UPPER(?) OR 
                        (
                            unaccent(UPPER(u.first_name)) = unaccent(UPPER(?)) AND 
                            unaccent(UPPER(u.last_name)) = unaccent(UPPER(?)) AND 
                            unaccent(UPPER(u.second_last_name)) = unaccent(UPPER(?))
                        )
                        )",
                    [$curp, $nombre, $apellido1, $apellido2]);
```
> **Nota:** Elimine la referencia al controller UserController sino existe en su proyecto, así mismo los bloques de código que hacen referencia a él en las líneas 144 a 146 usadas para notificar por correo electrónico la creación de una cuenta nueva y su validación.
``` php
use App\Http\Controllers\UserController;
...
try {
    $classUser = new UserController();
    $classUser->iniciarValidacionDeCorreo($user);
    $message = 'Para continuar con tu registro, deberás confirmar tu correo electrónico dando clic en el enlace que te hemos enviado a "' . $correo . '".';
} catch (Exception $e) {}
```
- **Paso 7:** Registre en el route service provider (app/Providers/RouteServiceProvider.php) el archivo de las rutas LlaveMX.
``` php
    public function map()
    {
        ...
        $this->mapLlaveMXRoutes();
    }
    ...
    protected function mapLlaveMXRoutes()
    {
        Route::prefix('llavemx')
            ->middleware('web')
            ->namespace($this->namespace)
            ->group(base_path('routes/llavemx.php'));
    }
```
Si no tiene el route service provider, favor de revisar si dispone de app (bootstrap/app.php) para integrar las rutas de LlaveMX
``` php
    return Application::configure(basePath: dirname(__DIR__))
        ...
        Route::middleware(['web'])
                ->prefix('llavemx')
                ->group(base_path('routes/llavemx.php'));
```
- **Paso 8:** Registre en el Composer el helper de LlaveMX (composer.json)
``` bash
...
"autoload": {
    ...
    "files": [
        "app/Helpers/llavemx.php"
    ]
},
```
Ejecutar el siguiente comando después del cambio:
``` bash
composer dump-autoload
```
- **Paso 9:**  Desinstale el paquete LlaveMX. Al realizar esta acción las vistas, controladores, rutas, helpers y services de la funcionalidad de LlaveMX preconstruida permanecerán en el proyecto y facilitará su despliegue en producción sin dependencia del paquete.
``` bash
composer remove dticcfcrl/paquete-conexion-api-llave-mx-laravel
```
- **Paso 10:**  **(Si su proyecto soporta varias cuentas de usuario y tiene un menú superior)** Ajuste el header para agregar en el menú la opción de "Cambiar de cuenta" si se detecta que tiene varios roles (Ej. resources/view/layouts/admin_header.blade.php y resources/view/layouts/header.blade.php).
``` php
@if (Session::get('cuentas') !== null)
    <a class="dropdown-item-custom" id="custom-selector" href="{{ route('llavemx.selector') }}">
        <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="24"
            height="24" viewBox="0 0 150 150">
            <defs>
                <g id="opin1rle40">
                    <circle cx="40.5" cy="28.99" r="13.98"/>
                    <path d="M44.71,45.29H36.29A20.29,20.29,0,0,0,16,65.56v7.1A2.33,2.33,0,0,0,18.35,75h44.3A2.33,2.33,0,0,0,65,72.66v-7.1A20.29,20.29,0,0,0,44.71,45.29Z"/>
                    <circle cx="109.5" cy="88.99" r="13.98"/>
                    <path d="M113.71,105.29h-8.42A20.29,20.29,0,0,0,85,125.56v7.1A2.33,2.33,0,0,0,87.35,135h44.3a2.33,2.33,0,0,0,2.33-2.33v-7.1A20.29,20.29,0,0,0,113.71,105.29Z"/>
                    <path d="M120.41,45.16a4,4,0,0,0-5.43,1.6l-.73,1.34a34,34,0,0,0-27-21.16,4,4,0,1,0-1.09,7.92,26,26,0,0,1,20.9,16.91L103.86,50A4,4,0,1,0,100,57l10.46,5.71.18.1.06,0h0a3.56,3.56,0,0,0,.7.29l.21.06a5,5,0,0,0,.56.09l.34,0a1.48,1.48,0,0,0,.21,0,2.85,2.85,0,0,0,.29,0l.27,0a3.7,3.7,0,0,0,.55-.14l.17,0a4,4,0,0,0,2.09-1.83L122,50.59A4,4,0,0,0,120.41,45.16Z"/>
                    <path d="M29.59,104.84a4,4,0,0,0,5.43-1.6l.73-1.34a34,34,0,0,0,27,21.16,4,4,0,1,0,1.09-7.92,26,26,0,0,1-20.9-16.91l3.2,1.75A4,4,0,0,0,50,93L39.52,87.25l-.18-.1-.06,0h0a3.56,3.56,0,0,0-.7-.29l-.21-.06a5,5,0,0,0-.56-.09l-.34,0a1.48,1.48,0,0,0-.21,0,2.85,2.85,0,0,0-.29,0l-.27,0a3.7,3.7,0,0,0-.55.14l-.17,0a4,4,0,0,0-2.09,1.83L28,99.41A4,4,0,0,0,29.59,104.84Z"/>
                </g>
            </defs>
            <g fill="none" fill-rule="evenodd">
                <g>
                    <use fill="#3F4141" xlink:href="#opin1rle40" />
                </g>
            </g>
        </svg>
        Cambiar de cuenta
    </a>
@endif
```
- **Paso 11:**  **(Si su proyecto permite crear cuentas de usuario y tiene un menú superior)** Ajuste el header para agregar en el menú la opción de "Registrar cuenta" (Ej. resources/view/layouts/admin_header.blade.php y resources/view/layouts/header.blade.php).
``` php
<a class="dropdown-item-custom" id="custom-new-account" href="" data-toggle="modal" data-target="#newAccountModal">
    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 100 125">
        <defs>
            <g id="umau7xlo99">
                <path d="M70.2,54.348H53.826c-10.987,0-19.895,8.908-19.895,19.895v1.761c0,2.314,1.875,4.192,4.189,4.192h47.786  c2.316,0,4.191-1.878,4.191-4.192v-1.761C90.095,63.256,81.189,54.348,70.2,54.348z"/>
                <path d="M62.013,50.122c8.372,0,15.16-6.784,15.16-15.158c0-8.372-6.788-15.16-15.16-15.16s-15.16,6.788-15.16,15.16  C46.853,43.338,53.64,50.122,62.013,50.122z"/>
                <path d="M36.768,45.882c0-1.841-1.492-3.333-3.334-3.333h-6.765v-6.765c0-1.841-1.492-3.334-3.333-3.334  c-1.841,0-3.333,1.494-3.333,3.334v6.765h-6.765c-1.842,0-3.334,1.492-3.334,3.333c0,1.841,1.492,3.334,3.334,3.334h6.765v6.765  c0,1.841,1.492,3.333,3.333,3.333c1.841,0,3.333-1.492,3.333-3.333v-6.765h6.765C35.276,49.216,36.768,47.722,36.768,45.882z"/>
            </g>
        </defs>
        <g fill="none" fill-rule="evenodd">
            <g>
                <use fill="#3F4141" xlink:href="#umau7xlo99" />
            </g>
        </g>
    </svg>
    Registrar cuenta
</a>
```
Al final del código del header incluya el partial de la modal para el registro de nueva cuenta
``` php
@include('llavemx.partials.new_account', ['modal_name' => 'newAccountModal'])
```

## Notas adicionales (Respecto al Core)

Revise que el Core este usando la rama "core_llavemx" ya que guarda ahora todos los datos recuperados de LlaveMX, así mismo ejecute las migraciones.
``` shell
php artisan migrate
```
 Y en el psql conectado a la base de datos Core ejecutar:
 ``` shell
> CREATE EXTENSION IF NOT EXISTS unaccent;
```
Proceda a crear su credencial Passport mediante los siguientes comandos:
 ``` shell
php artisan passport:keys
php artisan passport:client --client
```
Use el Client ID y Client Secret para asignarlo en las variables LLAVE_CORE_CLIENT_ID y LLAVE_CORE_CLIENT_SECRET. En cuanto a la url del core asignarlo en la variable LLAVE_CORE_API_URL.

## Fallos de comunicación con cerficados SSL autofirmados (solo en local)

Algunas veces los certificados SSL autofirmados generán problemas de comunicación en ambientes locales. Para solventarlos, se ha incorporado una variable en el .env la cual solo debe usarse en ambientes locales (NO EN PRODUCCIÓN).

``` bash
LLAVE_VERIFY_SSL=false #para ambiente local ssl autofirmado poner en false, sino borrar la variable
```
``` shell
php artisan config:clear
```

## Seguridad

Si descubre alguna vulnerabilidad o fallo de seguridad, favor de enviar un email a jorge.ceyca@centrolaboral.gob.mx para su atención y seguimiento.

## Créditos

- Jorge Ceyca

## Licencia

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.