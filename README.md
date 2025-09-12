# RENPE

Paquete CFCRL para integración de aplicaciones Web con LlaveMX.

## Info

- [Documentación Servicios](https://documenter.getpostman.com/view/10617529/U16htS86).
- [Repositorio Github](https://github.com/dticcfcrl/paquete-conexion-api-llave-mx-laravel).
- Laravel ^7.0 | ^8.0 | ^9.0 | ^10.0
- PHP ^7.0 | ^8.0

## Structure

El presente paquete cfcrl/llavemx-tools se encuentra integrado de los siguientes directorios:

```    
config/
src/
tests/
vendor/
```

## Instalación

Via Composer
``` bash
$ composer require cfcrl/llavemx-tools
```

si requiere remover el paquete
``` bash
$ composer remove cfcrl/llavemx-tools
```

El proceso de instalación coloca en los directorios de vistas, controladores, modelos, migraciones, rutas, helpers y services archivos de la funcionalidad de LlaveMX. Si procede a desinstalar estos no serán removidos por lo que deberá eliminarlos manualmente.

## Uso

- Paso 1:  `php artisan migrate`  Ejecutar las migraciones relacionadas al paquete LlaveMX
- Paso 2:  `npm run build` Ejecutar el comando de compilación de assets
- Paso 3:  `php artisan config:clear` Ajustar las variables .env (LLAVE_XXXX) y limpiar la cache
- Paso 4:  `@include('llavemx.partials.login')` Ajustar la vista de login (resources/views/auth/login.blade.php) para cortar el login viejo e incluir el partial login de llavemx
- Paso 4:  El login viejo colocarlo en la vista login_old de llavemx (resources/views/llavemx/partials/login_old.blade.php)
- Paso 5:  Ajustar el header para mostrar la opción de cambiar de usuario si se detecta que tiene varios roles en las vistas admin_header (resources/view/layouts/admin_header.blade.php) y header (resources/view/layouts/header.blade.php)

## Seguridad

Si descubre alguna vulnerabilidad o fallo de seguridad, favor de enviar un email a jorge.ceyca@centrolaboral.gob.mx para su atención y seguimiento.

## Créditos

- [Jorge Ceyca][link-author]

## Licencia

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.