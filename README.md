# DTICCFCRL/paquete-conexion-api-llave-mx-laravel

Paquete del Centro Federal de Conciliación y Registros Laborales (CFCRL) para integración en aplicaciones Web al servicio de autentificación  LlaveMX.

## Info

- [Repositorio Github](https://github.com/dticcfcrl/paquete-conexion-api-llave-mx-laravel).
- Laravel ^7.0 | ^8.0 | ^9.0 | ^10.0
- PHP ^7.0 | ^8.0

## Estructura

El presente paquete dticcfcrl/paquete-conexion-api-llave-mx-laravel se encuentra integrado de los siguientes directorios:

```    
config/
src/
```

## Instalación

Modificar el composer.json del proyecto y añadir el repositorio del paquete DTICCFCRL/paquete-conexion-api-llave-mx-laravel para poder instalarlo mediante composer.
> **Nota:** Revise si ya tenía previamente la definición "minimum-stability", si es así solo deje una con el valor "dev".
``` bash
    ...
    "repositories": [
    {
        "type": "vcs",
        "url": "git@github.com:dticcfcrl/paquete-conexion-api-llave-mx-laravel.git"
    }
    ],
    "minimum-stability": "dev",
    "prefer-stable": true
}
```
Instalando el paquete LlaveMX via Composer
``` bash
composer require dticcfcrl/paquete-conexion-api-llave-mx-laravel:v0.2.2
```

Si requiere remover el paquete ejecute 
``` bash
composer remove dticcfcrl/paquete-conexion-api-llave-mx-laravel
```
> **Nota:** El proceso de instalación coloca en los directorios de vistas, controladores, rutas, helpers y services archivos de la funcionalidad de LlaveMX preconstruida. Si procede a desinstalar estos no serán removidos por lo que deberá eliminarlos manualmente.

## Uso

- Paso 1:  `npm run build` Ejecutar el comando de compilación del proyecto dado que el paquete integra una hoja de estilos que ya a sido añadida (resources/sass/app.scss).
- Paso 2:  `php artisan config:clear` Ajustar las variables .env (LLAVE_XXXX) y limpiar la cache.
- Paso 3:  `@include('llavemx.partials.login')` Ajustar la vista de login (resources/views/auth/login.blade.php) para cortar el login viejo e incluir el partial al login de LlaveMX.
- Paso 4:  El login viejo colocarlo en la vista login_old de LlaveMX (resources/views/llavemx/partials/login_old.blade.php).
- Paso 5:  Ajustar el header para mostrar la opción de cambiar de usuario si se detecta que tiene varios roles en las vistas admin_header (resources/view/layouts/admin_header.blade.php) y header (resources/view/layouts/header.blade.php).
``` bash
@if (Cookie::get('j7pk19') !== null)
    <a class="dropdown-item-custom" id="custon-selector" href="{{ route('llavemx.selector') }}">
        <svg xmlns="http://www.w3.org/2000/svg" width="24"
            height="24" viewBox="0 0 150 150">
            <circle cx="40.5" cy="28.99" r="13.98"/>
            <path d="M44.71,45.29H36.29A20.29,20.29,0,0,0,16,65.56v7.1A2.33,2.33,0,0,0,18.35,75h44.3A2.33,2.33,0,0,0,65,72.66v-7.1A20.29,20.29,0,0,0,44.71,45.29Z"/>
            <circle cx="109.5" cy="88.99" r="13.98"/>
            <path d="M113.71,105.29h-8.42A20.29,20.29,0,0,0,85,125.56v7.1A2.33,2.33,0,0,0,87.35,135h44.3a2.33,2.33,0,0,0,2.33-2.33v-7.1A20.29,20.29,0,0,0,113.71,105.29Z"/>
            <path d="M120.41,45.16a4,4,0,0,0-5.43,1.6l-.73,1.34a34,34,0,0,0-27-21.16,4,4,0,1,0-1.09,7.92,26,26,0,0,1,20.9,16.91L103.86,50A4,4,0,1,0,100,57l10.46,5.71.18.1.06,0h0a3.56,3.56,0,0,0,.7.29l.21.06a5,5,0,0,0,.56.09l.34,0a1.48,1.48,0,0,0,.21,0,2.85,2.85,0,0,0,.29,0l.27,0a3.7,3.7,0,0,0,.55-.14l.17,0a4,4,0,0,0,2.09-1.83L122,50.59A4,4,0,0,0,120.41,45.16Z"/>
            <path d="M29.59,104.84a4,4,0,0,0,5.43-1.6l.73-1.34a34,34,0,0,0,27,21.16,4,4,0,1,0,1.09-7.92,26,26,0,0,1-20.9-16.91l3.2,1.75A4,4,0,0,0,50,93L39.52,87.25l-.18-.1-.06,0h0a3.56,3.56,0,0,0-.7-.29l-.21-.06a5,5,0,0,0-.56-.09l-.34,0a1.48,1.48,0,0,0-.21,0,2.85,2.85,0,0,0-.29,0l-.27,0a3.7,3.7,0,0,0-.55.14l-.17,0a4,4,0,0,0-2.09,1.83L28,99.41A4,4,0,0,0,29.59,104.84Z"/>
        </svg>
        Cambiar de cuenta
    </a>
@endif
```
- Paso 6:  Modificar el controller ApiLlaveMXController (app/Http/Controller/ApiLlaveMXController.php) revisando y corrigiendo la query de búsqueda de usuarios acorde a la estructura de seguridad del proyecto así como la sección de rutas acorde al rol una vez que se ha autentificado el usuario.
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
## Seguridad

Si descubre alguna vulnerabilidad o fallo de seguridad, favor de enviar un email a jorge.ceyca@centrolaboral.gob.mx para su atención y seguimiento.

## Créditos

- Jorge Ceyca

## Licencia

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.