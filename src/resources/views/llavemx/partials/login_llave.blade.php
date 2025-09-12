                <div class="col-lg-4 col-md-12 continer-login-form">
					<div class="text-center w-100 mb-0">
						<img src="{{ asset('images/logo_llaveMX.svg') }}" style="width: 150px; margin-bottom: 20px;">
						<h2 class="titulo-seccion">Te damos la bienvenida</h2>
						<div class="form-group w-100 mb-0">
							<a href="{{env('LLAVE_ENDPOINT')?(env('LLAVE_ENDPOINT').env('LLAVE_ENDPOINT_LOGIN').'?client_id='.env('LLAVE_CLIENT_ID').'&redirect_url='.env('LLAVE_URL_REDIRECT').'&state='.llaveMXGeneraState()):'#'}}" class="btn btn-stps">
								{{ __('Iniciar sesión') }}
							</a>
							<br/><br/>
							<span class="texto">¿Aún no tienes una cuenta?</span>
							<br/>
							<a href="{{env('LLAVE_ENDPOINT')?(env('LLAVE_ENDPOINT').env('LLAVE_ENDPOINT_CREATEACCOUNT')):'#'}}" class="btn btn-stps">
								{{ __('Crear cuenta') }}
							</a>
						</div>
					</div>
                </div>