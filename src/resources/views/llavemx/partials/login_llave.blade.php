	<div class="row justify-content-center">
        <div class="row container-llaveMX">
            <div class="left-section">
                <h1 id="_mcContenido_h1Titulo" class="Titulo" style="margin-top:0px;">{{env('LLAVE_APP_NAME','')}}</h1>
                <img src="{{ asset('images/logo_cfcrl.png') }}" alt="Logo INIFAP">
            </div>
            <div class="boxLogin">
                <div class="right-section">
                    <img class="mb-0" src="{{ asset('images/logo_llaveMX.png') }}" alt="Llave MX">
                    <hr class="w-100" style="border-color: gray"/>
                    <div class="small text-muted mb-4">
                        Al iniciar sesión declaro que he leído los <a class="primary-link" href="https://www.archivos.atdt.gob.mx/storage/app/media/Transparencia/TyC/TerminosLlaveMX.pdf" target="_blank">Términos y Condiciones</a> y nuestro <a class="primary-link" href="https://www.archivos.atdt.gob.mx/storage/app/media/Transparencia/PORTAL%20ATDT/AVISOS%20DE%20PRIVACIDAD/ATDT_Aviso%20de%20Privacidad%20Integral%20Llave%20MX.pdf" target="_blank">Aviso de Privacidad</a>.
                    </div>
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
    </div>	
	<script>
        // Agregamos el fondo al contenedor id='app'
		document.addEventListener('DOMContentLoaded', function () {
			const container_app = document.getElementById('app');
			if (container_app) {
				container_app.classList.add('fondo-llaveMX','d-flex', 'justify-content-center', 'align-items-center');
			}
		});
    </script>
