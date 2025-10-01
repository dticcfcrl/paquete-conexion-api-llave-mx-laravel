	<div class="row justify-content-center">
        <form id="form-login-stps" method="POST" action="{{ route('login.store') }}">
            @csrf
            <div class="row container-llaveMX">
                <div class="left-section">
                    <h1 id="_mcContenido_h1Titulo" class="Titulo" style="margin-top:0px;">{{env('LLAVE_APP_NAME','')}}</h1>
                    <img src="{{ asset('images/logo_cfcrl.png') }}" alt="Logo INIFAP">
                </div>
                <div class="boxLogin">
                    <div class="right-section">
                        <img src="{{ asset('images/logo_llaveMX.png') }}" alt="Llave MX">
                        <div class="acciones" style="">
                            <div class="col-6">
                                <a href="{{route('llavemx.login')}}" class="login-button">
                                    Iniciar sesión
                                </a>
                            </div>
                            <div class="col-6">
								<a href="{{route('llavemx.register')}}" class="create-button TextoNoto">
                                    Crear cuenta
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>	
	<script>
        // Agregamos el fondo al contenedor id='app'
		document.addEventListener('DOMContentLoaded', function () {
			const container_app = document.getElementById('app');
			if (container_app) {
				container_app.classList.add('fondo-llaveMX');
			}
		});
    </script>
