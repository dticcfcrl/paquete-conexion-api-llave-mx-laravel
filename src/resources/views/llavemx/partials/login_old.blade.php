                <div class="col-lg-4 col-md-12 continer-login-form">
                    <h2 class="titulo-seccion">Iniciar sesión</h2>
                    <div class="separador"><span></span></div>
                    @error('login')
                    <span class="invalid-feedback" role="alert">
                        <strong>{{ $message }}</strong>
                    </span>
                    @enderror
                    <div class="form-group mb-3">
                        <label for="email" class="">{{ __('Usuario') }}</label>
                        <input id="email" type="email" class="form-control @error('email') is-invalid @enderror" name="email" value="{{ old('email') }}" placeholder="Ingresa tu usuario" required autocomplete="email" autofocus>
                        @error('email')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                        @enderror
                    </div>

                    <div class="form-group mb-3">
                        <label for="password" class="">{{ __('Contraseña') }}</label>
                        <input id="password" type="password" class="form-control @error('password') is-invalid @enderror" name="password" required autocomplete="current-password" placeholder="Ingresa tu contraseña">
                        @error('password')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                        @enderror
                    </div>

                    <input type="hidden" id="token_captcha" name="token_captcha" value="">

                    <div class="form-group row">
                        <div class="form-check col-5 pl-4">
                            <input class="form-check-input" type="checkbox" name="remember" id="remember" {{ old('remember') ? 'checked' : '' }}>

                            <label class="form-check-label" for="remember">
                                {{ __('Recordarme') }}
                            </label>
                        </div>
                        <div class="col-7 text-right">
                            <a href="{{ url('/contrasena/restablecer') }}" class="olvide-contrasena">Olvidé mi contraseña</a>
                        </div>
                    </div>

                    <div class="form-group row">
                        <div class="col-12 text-right text-sm">
                            No tengo cuenta <a href="{{ url('/registro') }}" class="olvide-contrasena">Crear una</a>
                        </div>
                    </div>

                    <div class="form-group mb-0">
                        <button type="submit" class="btn btn-stps float-right btn-click-login">
                            {{ __('Ingresar') }}
                        </button>
                    </div>
                </div>