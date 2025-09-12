            @if (date('Y-m-d') >= DateTime::createFromFormat("Y-m-d", env('LLAVE_VIEJO_LOGIN_DATE', '2025-09-01'))->format("Y-m-d"))
                @include('llavemx.partials.login_llave')
            @else
                @include('llavemx.partials.login_old')
            @endif