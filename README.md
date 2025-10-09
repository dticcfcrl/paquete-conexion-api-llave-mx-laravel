# DTICCFCRL/paquete-conexion-api-llave-mx-laravel

Paquete del Centro Federal de Conciliación y Registros Laborales (CFCRL) para integración en aplicaciones Web al servicio de autentificación  LlaveMX.

## Info

- [Repositorio Github](https://github.com/dticcfcrl/paquete-conexion-api-llave-mx-laravel).
- Laravel ^7.0 | ^8.0 | ^9.0 | ^10.0 | ^11.0 | ^12.0 
- PHP ^7.0 | ^8.0

## Estructura

El presente paquete dticcfcrl/paquete-conexion-api-llave-mx-laravel se encuentra integrado de los siguientes directorios:

```    
config/
src/
```

## Instalación

**Instalación desde el repositorio GitHub**  
Modificaque el composer.json del proyecto y añadir el repositorio del paquete DTICCFCRL/paquete-conexion-api-llave-mx-laravel para poder instalarlo mediante composer.
> **Nota:** Revise si ya tenía previamente la definición "minimum-stability", si es así solo deje una con el valor "dev". Tambien, revise la definición "prefer-stable" que no se duplique y tenga el valor "true". 
``` bash
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
``` bash
composer require dticcfcrl/paquete-conexion-api-llave-mx-laravel:v0.2.9
```

**Instalación desde el directorio local del repositorio**  
En caso de tener problemas para instalar el paquete directamente desde GitHub, proceda a descargar el repositorio y dejarlo al mismo nivel que el directorio del proyecto en que esta trabajando y desea implementarle LlaveMX.
Modificaque el composer.json del proyecto y añadir la ruta al paquete DTICCFCRL/paquete-conexion-api-llave-mx-laravel para poder instalarlo mediante composer.
``` bash
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
``` bash
composer require dticcfcrl/paquete-conexion-api-llave-mx-laravel
```
> **Nota:** La versión que se instalará dependerá de la versión que se tenga bajada del repositorio del paquete DTICCFCRL/paquete-conexion-api-llave-mx-laravel. 

**Desinstalando el paquete**  
Si requiere remover el paquete ejecute el comando:
``` bash
composer remove dticcfcrl/paquete-conexion-api-llave-mx-laravel
```
> **Nota:** El proceso de instalación coloca en los directorios de vistas, controladores, rutas, helpers y services archivos de la funcionalidad de LlaveMX preconstruida. Si procede a desinstalar estos no serán removidos por lo que deberá eliminarlos manualmente.

## Uso

- Paso 1:  `npm run build` Ejecutar el comando de compilación del proyecto dado que el paquete integra una hoja de estilos que ya a sido añadida (resources/sass/app.scss).
- Paso 2:  `php artisan config:clear` Ajustar las variables .env (LLAVE_XXXX) y limpiar la cache.
- Paso 3:  `@include('llavemx.partials.login')` Ajustar la vista de login (resources/views/auth/login.blade.php) para cortar el login viejo e incluir el partial al login de LlaveMX.
- Paso 4:  El login viejo colocarlo en la vista login_old de LlaveMX (resources/views/llavemx/partials/login_old.blade.php).
- Paso 5:  Ajustar el header para agregar en el menú la opción de "Cambiar de cuenta" si se detecta que tiene varios roles (Ej. resources/view/layouts/admin_header.blade.php y resources/view/layouts/header.blade.php).
``` bash
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
- Paso 6:  Ajustar el header para agregar en el menú la opción de "Registrar cuenta" (Ej. resources/view/layouts/admin_header.blade.php y resources/view/layouts/header.blade.php).
``` bash
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
``` bash
@include('llavemx.partials.new_account', ['modal_name' => 'newAccountModal'])
```
- Paso 7:  Modificar el controller ApiLlaveMXController (app/Http/Controller/ApiLlaveMXController.php) revisando y corrigiendo la query de búsqueda de usuarios acorde a la estructura de seguridad del proyecto así como la sección de rutas acorde al rol una vez que se ha autentificado el usuario.
> **Nota:** Elimine el modelo UsuarioSolicitud sino existe en su proyecto, así mismo los bloques de código que hacen referencia a él en las líneas 126 a 146.
``` bash
use App\Models\UsuarioSolicitud;
...
try {
    $solicitud = UsuarioSolicitud::whereCorreo($correo)->first();
    if (isset($solicitud->id)) {
        if (strpos($correo, 'core_') === false)
            $this->sendMailValidarcorreo($correo, $solicitud->token_solicitud);
    }else{
            $token = Str::uuid();
            $solicitud = UsuarioSolicitud::create([
                'correo' => $correo,
                'token_solicitud' => $token,
                'fecha_solicitud' => date('Y-m-d H:i:s'),
            ]);
            if (strpos($correo, 'core_') === false)
                $this->sendMailValidarcorreo($correo, $token);
    }
    $message = 'Para continuar con tu registro, deberás confirmar tu correo electrónico dando clic en el enlace que te hemos enviado a "' . $correo . '".';
} catch (Exception $e) {}
```
> **Nota:** Para facilitar la edición del controller busque los comentarios que indican "MODIFICAR:".
``` bash
    /*
    * MODIFICAR:
    * Ajustar segun estructura de usuarios del sistema
    */
    $data = DB::select("SELECT u.id as user_id
                        FROM public.users u
                        WHERE (UPPER(u.email) = UPPER(?) OR UPPER(u.curp) = UPPER(?) OR 
                            (
                                unaccent(UPPER(u.first_name)) = unaccent(UPPER(?)) AND 
                                unaccent(UPPER(u.last_name)) = unaccent(UPPER(?)) AND 
                                unaccent(UPPER(u.second_last_name)) = unaccent(UPPER(?))
                            )
                            )",
                        [$correo, $curp, $nombre, $apellido1, $apellido2]);
```
- Paso 8:  Revise que el Core este usando la rama "origin/core_llavemx" ya que guarda ahora todos los datos recuperados de LlaveMX, así mismo ejecute las migraciones (`php artisan migrate`).

## Notas adicionales

En caso de que la ruta "[SERVIDOR]/llavemx/callback" no responda, proceda ha incorporar manualmente la ruta llavemx en el route service provider (app/Providers/RouteServiceProvider.php)
``` bash
    public function map()
    {
        ...
        $this->mapLlaveMXRoutes();
    }
    ...
    protected function mapLlaveMXRoutes()
    {
        Route::prefix('llavemx')
            ->middleware('system')
            ->namespace($this->namespace)
            ->group(base_path('routes/llavemx.php'));
    }
```
## Seguridad

Si descubre alguna vulnerabilidad o fallo de seguridad, favor de enviar un email a jorge.ceyca@centrolaboral.gob.mx para su atención y seguimiento.

## Créditos

- Jorge Ceyca

## Licencia

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.