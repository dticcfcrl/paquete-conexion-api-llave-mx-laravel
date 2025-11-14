<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Mail;
use Auth;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Session;

use App\Services\{LlaveMXService};
use App\Models\{User, Role, Bitacora};

//--------------------------------------------------------------------------------------------------------------------------------------------------
// MODIFICAR: (Si no aplica eliminar todo el segmento)
/*
use App\Http\Controllers\UserController;
*/
//--------------------------------------------------------------------------------------------------------------------------------------------------

class ApiLlaveMXController extends Controller
{
    /*
    * MODIFICAR:
    * Ajustar a la pagina de inicio o login del sistema
    */
    private $home_login = '/';
    public function login()
    {
        //Generar el state y guardarlo en una session
        $params = [
            'client_id' => env('LLAVE_CLIENT_ID'),
            'redirect_url' => env('LLAVE_URL_REDIRECT'),
            'state' => llaveMXGeneraState()
        ];
        $url = env('LLAVE_ENDPOINT').env('LLAVE_ENDPOINT_LOGIN').'?'.http_build_query($params);
        //Si no se tiene configurado el endpoint de llaveMX, regresar al inicio con mensaje de error
        if (!env('LLAVE_ENDPOINT')) return Redirect::to($this->home_login)->withErrors(['msg' => 'El servicio de autenticación LlaveMX no está disponible en este momento. Inténtelo más tarde.']);
        
        return Redirect::to($url);
    }

    public function register()
    {
        $url = env('LLAVE_ENDPOINT').env('LLAVE_ENDPOINT_CREATEACCOUNT');
        
        //Si no se tiene configurado el endpoint de llaveMX, regresar al inicio con mensaje de error
        if (!env('LLAVE_ENDPOINT')) return Redirect::to($this->home_login)->withErrors(['msg' => 'El servicio de autenticación LlaveMX no está disponible en este momento. Inténtelo más tarde.']);

        return Redirect::to($url);
    }

    public function newAccount(Request $request)
    {  
        if ($request->isMethod('get')) {
            return redirect()->back();
        }

        if (Session::has('curp')) {
            //Recuperamos los datos de la cuenta
            $curp = Session::get('curp');
            $correo_llavemx = Session::get('correo');
            $correo = $request->correo_newAccount;
            $nombre = Session::get('nombre');
            $apellido1 = Session::get('apellido1');
            $apellido2 = Session::get('apellido2');
            $sexo = Session::get('sexo');
            $telefono = Session::get('telefono');
            //Datos extras
            $es_extranjero = Session::get('es_extranjero');
            $nacimiento_estado = Session::get('nacimiento_estado');
            $nacimiento_estado_id = Session::get('nacimiento_estado_id');
            $nacimiento_fecha = Session::get('nacimiento_fecha');
            $usuario_llave_id = Session::get('usuario_llave_id');
            $tiene_firmamx = Session::get('tiene_firmamx');
            //Validar que el correo para el mismo usuario no este previamente registrado
            $preregistrado = User::where('email',$correo)->exists();
            if ($preregistrado){
                return redirect()->route('llavemx.selector')->with('error', 'Ya existe una cuenta con el correo "'.$correo.'".');
            }
            //Validar cuantas cuentas ya tiene creado el usuario y limitarlas con LLAVE_ACCOUNT_LIMIT
            $cuentas = 1;
            if (Session::has('cuentas')) {
                $cuentas = count(explode(',', Session::get('cuentas')));
            }
            if ($cuentas+1 > env('LLAVE_ACCOUNT_LIMIT',20)){
                return redirect()->route('llavemx.selector')->with('error', 'Ya tienes la cantidad máxima de cuentas permitidas.');
            }
            //Registramos la cuenta tomando los datos del usuario
            $role_name = 'representante_legal';
            $pass = bcrypt(Str::random(16));
            //phone, curp, usuario_llave_id,
            $data_user = [
                'first_name' => $nombre,
                'last_name' => $apellido1,
                'second_last_name' => $apellido2,
                'email' => $correo,
                'password' => $pass,
                'password_confirmation' => $pass,
                'phone' => $telefono,
                'curp' => $curp,
                'sexo' => $sexo,
                'es_extranjero' => $es_extranjero??false,
                'nacimiento_estado' => $nacimiento_estado??null,
                'nacimiento_estado_id' => $nacimiento_estado_id??null,
                'nacimiento_fecha' => $nacimiento_fecha??null,
                'usuario_llave_id' => $usuario_llave_id??null,
                'tiene_firmamx' => $tiene_firmamx??false
            ];
            $llavemx_services = new LlaveMXService();
            $user_core = $llavemx_services->registerUserInCore($data_user);
            if (isset($user_core['id'])) {
                $data_user['token_session'] = $user_core['token_session'];
                $data_user['user_core_id'] = $user_core['id'];
                $data_user['update_data_at'] = $user_core['update_data_at'];
            }
            $user = User::create($data_user);
            $role = Role::where('name', $role_name)->first();
            $user->assignRole($role->id);
            /*
            * MODIFICAR:
            * Revisar si requiere bitacorización
            */
            Bitacora::create([
                'usuario_id' => $user->id,
                'code' => 'admin',
                'subcode' => 'usuarios',
                'descripcion' => 'Se registró el usuario',
                'referencia_id' => $user->id,
                'tipo_referencia' => 'usuario'
            ]);
            $message = 'Cuenta registrada exitosamente.';
            //--------------------------------------------------------------------------------------------------------------------------------------------------
            // MODIFICAR: (Si no aplica eliminar todo el segmento)
            // Crear el registro de validación de correo
            try {
                /*
                $classUser = new UserController();
                $classUser->iniciarValidacionDeCorreo($user);
                $message = 'Para continuar con tu registro, deberás confirmar tu correo electrónico dando clic en el enlace que te hemos enviado a "' . $correo . '".';
                */
            } catch (Exception $e) {}
            //--------------------------------------------------------------------------------------------------------------------------------------------------
            //Limpiamos la session de selección de cuenta si existen multiples
            Session::forget('cuentas');
            /*
            * MODIFICAR:
            * Ajustar segun estructura de usuarios del sistema
            */
            $todos_roles = Session::has('funcionario_activo')?Session::get('funcionario_activo'):true;
            if ($todos_roles){
                $data = DB::select("SELECT u.id as user_id
                                    FROM public.users u
                                    LEFT JOIN public.usuarios_solicitudes us ON us.usuario_id = u.id
                                    WHERE (UPPER(u.email) = UPPER(?) OR UPPER(us.curp) = UPPER(?) OR 
                                        (
                                            unaccent(UPPER(u.first_name)) = unaccent(UPPER(?)) AND 
                                            unaccent(UPPER(u.last_name)) = unaccent(UPPER(?)) AND 
                                            unaccent(UPPER(u.second_last_name)) = unaccent(UPPER(?))
                                        ) OR 
                                        (
                                            unaccent(UPPER(us.nombre)) = unaccent(UPPER(?)) AND 
                                            unaccent(UPPER(us.primer_apellido)) = unaccent(UPPER(?)) AND 
                                            unaccent(UPPER(us.segundo_apellido)) = unaccent(UPPER(?))
                                        )
                                        ) AND u.deleted_at IS NULL AND u.activo = true",
                                    [$correo_llavemx, $curp, $nombre, $apellido1, $apellido2, $nombre, $apellido1, $apellido2]);
            }else{
                $data = DB::select("SELECT u.id as user_id
                                FROM public.users u
                                LEFT JOIN public.usuarios_solicitudes us ON us.usuario_id = u.id
                                INNER JOIN public.user_roles ur ON ur.user_id = u.id
                                INNER JOIN public.roles r ON r.id = ur.role_id
                                WHERE (UPPER(u.email) = UPPER(?) OR UPPER(us.curp) = UPPER(?) OR 
                                    (
                                        unaccent(UPPER(u.first_name)) = unaccent(UPPER(?)) AND 
                                        unaccent(UPPER(u.last_name)) = unaccent(UPPER(?)) AND 
                                        unaccent(UPPER(u.second_last_name)) = unaccent(UPPER(?))
                                    ) OR 
                                    (
                                        unaccent(UPPER(us.nombre)) = unaccent(UPPER(?)) AND 
                                        unaccent(UPPER(us.primer_apellido)) = unaccent(UPPER(?)) AND 
                                        unaccent(UPPER(us.segundo_apellido)) = unaccent(UPPER(?))
                                    )
                                    ) AND u.deleted_at IS NULL AND u.activo = true AND r.name = 'representante_legal'",
                                [$correo_llavemx, $curp, $nombre, $apellido1, $apellido2, $nombre, $apellido1, $apellido2]);
            }
            //Recuperando los ids de las cuentas de usuario encontradas
            $users_id = array_map(fn($row) => $row->user_id, $data);
            //Recuperando los usuarios
            $users = User::whereIn('id',$users_id)->get();
            if($users->count() > 0){
                //Guardamos los users en la session 'cuentas'
                Session::put('cuentas', implode(',', $users_id));
                //Redireccionamos a una vista para que el usuario seleccione la cuenta con la que desea ingresar
                return redirect()->route('llavemx.selector')->with('success', $message);
            }
        }
        //Sino encuentra cuentas manda a la bandeja por default al rol
        /*
        * MODIFICAR:
        * Ajustar segun roles del sistema la bandeja a la que manda
        */
        if (Auth::check()){
            if(Auth::user()->hasRole('admin')){
                return redirect('admin/resoluciones');
            }else if (Auth::user()->hasRole('representante_legal')) {
                return redirect('/mis-tramites');
            }
            return redirect('/revision-tramites');
        }
        return Redirect::to($this->home_login);
    }

    public function callback(Request $request)
    {
        //Limpiamos la session de selección de cuenta si existen multiples
        Session::forget('cuentas');
        //PASO 01. Validar el state
        /* 
        *  NOTA DE SEGURIDAD: 
        *  Cuando la aplicación cliente identifique la redirección con los parámetros antes 
        *  mencionados, deberá validar que el “state” sea el mismo que envió a Llave MX para cada solicitud de 
        *  inicio de sesión de un usuario, ya que este parámetro sirve para mitigar ataques CSRF.
        */
        $state_csrf = Session::get('state_csrf');
        if($state_csrf != $request->state){
            //Regresamos al login con error de state inválido
            return Redirect::to($this->home_login)->withErrors(['msg' => 'Error de validación de seguridad. Inténtelo de nuevo.']);
        }
        $llavemx_services = new LlaveMXService();
        //PASO 02. Transformar el CODE por un TOKEN de LlaveMX
        $token = $llavemx_services->getToken($request->code);
        if(!$token){
            //Regresamos al login con error de obtención de token
            return Redirect::to($this->home_login)->withErrors(['msg' => 'Error al obtener el token de autenticación. Inténtelo de nuevo.']);
        }
        //PASO 03. Recuperar los datos del usuario y persona moral usando el token
        $data_user = $llavemx_services->getUser($token);
        if(!$data_user){
            //Regresamos al login con error de obtención de datos del usuario
            return Redirect::to($this->home_login)->withErrors(['msg' => 'Error al obtener los datos del usuario desde LlaveMX. Inténtelo de nuevo.']);
        }
        $data_morales = $llavemx_services->getPersonasMorales($token);
        //Registrar/Actualizar la info del usuario al core
        $core_user = $llavemx_services->storeDataAtCore($data_user, $data_morales);

        //PASO 04. Pasar datos a variables internas
        $curp = $data_user['curp'];
        $correo = @$data_user['correo'];
        $nombre = $data_user['nombre'];
        $apellido1 = $data_user['primerApellido'];
        $apellido2 = $data_user['segundoApellido'];
        $sexo = $data_user['sexo'];
        $telefono = $data_user['telVigente'];
        /* EJEMPLO PARA SOBREESCRIBIR USUARIO*/
        /*
        $curp = 'CECJ770822HSLYSR04';
        $correo = 'jceyca@gmail.com';
        $nombre = 'JORGE OMAR';
        $apellido1 = 'CEYCA';
        $apellido2 = 'CASTRO';
        */
        //Datos extras
        $es_extranjero = $data_user['esExtranjero']??false;
        $nacimiento_estado = @$data_user['estadoNacimiento'];
        $nacimiento_estado_id = @$data_user['idEstadoNacimiento'];
        $nacimiento_fecha = @$data_user['fechaNacimiento'];
        $usuario_llave_id = $data_user['idUsuario'];
        $tiene_firmamx = $data_user['tieneFirmaMX']??false;

        //Guardamos en sesión los datos de la cuenta por si queremos crear nueva cuenta
        Session::put('curp', $curp);
        Session::put('correo', $correo);
        Session::put('nombre', $nombre);
        Session::put('apellido1', $apellido1);
        Session::put('apellido2', $apellido2);
        Session::put('sexo', $sexo);
        Session::put('telefono', $telefono);
        Session::put('es_extranjero', $es_extranjero);
        Session::put('nacimiento_estado', $nacimiento_estado);
        Session::put('nacimiento_estado_id', $nacimiento_estado_id);
        Session::put('nacimiento_fecha', $nacimiento_fecha);
        Session::put('usuario_llave_id', $usuario_llave_id);
        Session::put('tiene_firmamx', $tiene_firmamx);

        //PASO 05. Revisamos si existe el usuario previamente
        /*
        * MODIFICAR:
        * Ajustar query segun estructura de usuarios del sistema... se puede usar $core_user['funcionario_activo'] == true
        * para validar que el core nos indica que sigue activo como funcionario y habiliar el acceso a otros roles
        */
        $todos_roles = isset($core_user['funcionario_activo'])?$core_user['funcionario_activo']:true;
        Session::put('funcionario_activo', $todos_roles);

        if ($todos_roles){
            $data = DB::select("SELECT u.id as user_id
                                FROM public.users u
                                LEFT JOIN public.usuarios_solicitudes us ON us.usuario_id = u.id
                                WHERE (UPPER(u.email) = UPPER(?) OR UPPER(us.curp) = UPPER(?) OR 
                                    (
                                        unaccent(UPPER(u.first_name)) = unaccent(UPPER(?)) AND 
                                        unaccent(UPPER(u.last_name)) = unaccent(UPPER(?)) AND 
                                        unaccent(UPPER(u.second_last_name)) = unaccent(UPPER(?))
                                    ) OR 
                                    (
                                        unaccent(UPPER(us.nombre)) = unaccent(UPPER(?)) AND 
                                        unaccent(UPPER(us.primer_apellido)) = unaccent(UPPER(?)) AND 
                                        unaccent(UPPER(us.segundo_apellido)) = unaccent(UPPER(?))
                                    )
                                    ) AND u.deleted_at IS NULL AND u.activo = true",
                                [$correo, $curp, $nombre, $apellido1, $apellido2, $nombre, $apellido1, $apellido2]);
        }else{
            //Recuperamos las cuentas de otros roles que no sean representante_legal para deshabilitarlas
            $data = DB::select("SELECT u.id as user_id
                                FROM public.users u
                                LEFT JOIN public.usuarios_solicitudes us ON us.usuario_id = u.id
                                INNER JOIN public.user_roles ur ON ur.user_id = u.id
                                INNER JOIN public.roles r ON r.id = ur.role_id
                                WHERE (UPPER(us.curp) = UPPER(?) OR 
                                    (
                                        unaccent(UPPER(u.first_name)) = unaccent(UPPER(?)) AND 
                                        unaccent(UPPER(u.last_name)) = unaccent(UPPER(?)) AND 
                                        unaccent(UPPER(u.second_last_name)) = unaccent(UPPER(?))
                                    ) OR 
                                    (
                                        unaccent(UPPER(us.nombre)) = unaccent(UPPER(?)) AND 
                                        unaccent(UPPER(us.primer_apellido)) = unaccent(UPPER(?)) AND 
                                        unaccent(UPPER(us.segundo_apellido)) = unaccent(UPPER(?))
                                    )
                                    ) AND u.deleted_at IS NULL AND u.activo = true AND r.name != 'representante_legal'
                                GROUP BY u.id",
                                [$curp, $nombre, $apellido1, $apellido2, $nombre, $apellido1, $apellido2]);
            $users_id = array_unique(array_map(fn($row) => $row->user_id, $data));
            User::whereIn('id',$users_id)->get()->each(function ($user) {
                $user->activo = false;
                $user->save();
                /*
                * MODIFICAR:
                * Revisar si requiere bitacorización
                */
                Bitacora::create([
                    'usuario_id' => $user->id,
                    'code' => 'admin',
                    'subcode' => 'usuarios',
                    'descripcion' => 'Deshabilitado usuario desde el Core',
                    'referencia_id' => $user->id,
                    'tipo_referencia' => 'usuario'
                ]);
            });
            $data = DB::select("SELECT u.id as user_id
                                FROM public.users u
                                LEFT JOIN public.usuarios_solicitudes us ON us.usuario_id = u.id
                                INNER JOIN public.user_roles ur ON ur.user_id = u.id
                                INNER JOIN public.roles r ON r.id = ur.role_id
                                WHERE (UPPER(u.email) = UPPER(?) OR UPPER(us.curp) = UPPER(?) OR 
                                    (
                                        unaccent(UPPER(u.first_name)) = unaccent(UPPER(?)) AND 
                                        unaccent(UPPER(u.last_name)) = unaccent(UPPER(?)) AND 
                                        unaccent(UPPER(u.second_last_name)) = unaccent(UPPER(?))
                                    ) OR 
                                    (
                                        unaccent(UPPER(us.nombre)) = unaccent(UPPER(?)) AND 
                                        unaccent(UPPER(us.primer_apellido)) = unaccent(UPPER(?)) AND 
                                        unaccent(UPPER(us.segundo_apellido)) = unaccent(UPPER(?))
                                    )
                                    ) AND u.deleted_at IS NULL AND u.activo = true AND r.name = 'representante_legal'",
                                [$correo, $curp, $nombre, $apellido1, $apellido2, $nombre, $apellido1, $apellido2]);
        }
        //Recuperando los ids de las cuentas de usuario encontradas
        $users_id = array_map(fn($row) => $row->user_id, $data);
        //Recuperando los usuarios
        $users = User::whereIn('id',$users_id)->get();
        if($users->count() > 0){
            //PASO 06a. Si son varias cuentas manda al selector de cuentas sino inicia sesión con la única cuenta encontrada
            if ($users->count() > 1) {
                //Guardamos los users en la session 'cuentas'
                Session::put('cuentas', implode(',', $users_id));
                //Redireccionamos a una vista para que el usuario seleccione la cuenta con la que desea ingresar
                return redirect()->route('llavemx.selector');
            }else{
                $user = $users->first();
                //Abrimos sesión en la plataforma
                Auth::login($user, true);
                //Abrimos sesión en registro
                $data_core = [
                    'email' => $user->email,
                    'user_core_id' => $user->user_core_id
                ];
                $response = $llavemx_services->loginInCore($data_core);
                //Destruimos la session de state
                Session::forget('state_csrf');
            }
        }else{
            //PASO 06b. Si no existe, crear el usuario y luego iniciar sesión

            if(!isset($correo) || empty($correo)){
                //Regresamos al login con error de necesita el correo electrónico
                return Redirect::to($this->home_login)->withErrors(['msg' => 'Es necesario que su cuenta Llave MX cuente con un correo electrónico para poder registrar su usuario en la plataforma debido a las notificaciones que se generan.']);
            }

            $role_name = 'representante_legal';
            $role = Role::where('name', $role_name)->first();
            /*
            * MODIFICAR:
            * Revisar si se tiene el atributo activo en el usuario para reactivarlo en caso de existir pero estar inactivo 
            * y actualizar sus datos
            */
            $user = User::where('email',$correo)->where('activo',false)->first();
            $bitacora_message = '';
            if (isset($user)){
                //Reactivar el usuario
                $user->first_name = $nombre;
                $user->last_name = $apellido1;
                $user->second_last_name = $apellido2;
                $user->user_core_id = isset($core_user)?@$core_user['id']:null;
                $user->token_session = isset($core_user)?@$core_user['token_session']:null;
                $user->activo = true;
                $user->save();
                $user->assignRole($role->id);
                $bitacora_message = 'Se habilitó el usuario nuevamente';
            }else{
                //Crear nuevo usuario
                $data_user = [
                    'first_name' => $nombre,
                    'last_name' => $apellido1,
                    'second_last_name' => $apellido2,
                    'email' => $correo,
                    'user_core_id' => isset($core_user)?@$core_user['id']:null,
                    'token_session' => isset($core_user)?@$core_user['token_session']:null
                ];
                try {
                    $user = User::create($data_user);
                    $user->assignRole($role->id);
                    $bitacora_message = 'Se registró el usuario';
                } catch (Exception $e) {
                    return Redirect::to($this->home_login)->withErrors(['msg' => 'Existe un conflicto con tu cuenta de correo electrónico ya que esta vinculada a otro usuario. Si tiene dudas o desea realizar una aclaración, comuníquese al siguiente correo: mesadeservicio@centrolaboral.gob.mx']);
                }
            }
            /*
            * MODIFICAR:
            * Revisar si requiere bitacorización
            */
            Bitacora::create([
                'usuario_id' => $user->id,
                'code' => 'admin',
                'subcode' => 'usuarios',
                'descripcion' => $bitacora_message,
                'referencia_id' => $user->id,
                'tipo_referencia' => 'usuario'
            ]);
            //Destruimos la session de state
            Session::forget('state_csrf');

            //Abrimos sesión en la plataforma
            Auth::login($user, true);
            //Abrimos sesión en registro
            $data_core = [
                'email' => $user->email,
                'user_core_id' => $user->user_core_id
            ];
            $response = $llavemx_services->loginInCore($data_core);
        }
        //PASO 07. Redirigir al usuario a la página principal del sistema acorde a su rol
        if (!Auth::check()) {
            //Forzar el cierre de sesión para que el usuario pueda acceder con otra cuenta
            $llavemx_services->closeSession($token);
            return Redirect::to($this->home_login)->withErrors(['msg' => 'No se logro iniciar sesión con el usuario. Inténtelo de nuevo.']);
        }
        /*
        * MODIFICAR:
        * Ajustar segun roles del sistema la bandeja a la que manda
        */
        if(Auth::user()->hasRole('admin')){
            return redirect('admin/resoluciones');
        }else if (Auth::user()->hasRole('representante_legal')) {
            return redirect('/mis-tramites');
        }
        return redirect('/revision-tramites');
    }

    public function loginSelector($hash_user_id)
    {
        if (Session::has('cuentas')) {
            $user_id = Crypt::decryptString($hash_user_id);
            $user = User::find($user_id);
            if(!isset($user->id)){
                return Redirect::to($this->home_login)->withErrors(['msg' => 'No se encontró el usuario seleccionado. Inténtelo de nuevo.']);
            }
            Auth::login($user, true);
            //Abrimos sesión en registro
            $llavemx_services = new LlaveMXService();
            $data_core = [
                'email' => $user->email,
                'user_core_id' => $user->user_core_id
            ];
            $response = $llavemx_services->loginInCore($data_core);
            //Destruimos la session de state
            Session::forget('state_csrf');
            //PASO 07. Redirigir al usuario a la página principal del sistema acorde a su rol
            if (!Auth::check()) return Redirect::to($this->home_login)->withErrors(['msg' => 'No se logro iniciar sesión con el usuario seleccionado. Inténtelo de nuevo.']);
        }
        /*
        * MODIFICAR:
        * Ajustar segun roles del sistema la bandeja a la que manda
        */
        if (Auth::check()){
            if(Auth::user()->hasRole('admin')){
                return redirect('admin/resoluciones');
            }else if (Auth::user()->hasRole('representante_legal')) {
                return redirect('/mis-tramites');
            }
            return redirect('/revision-tramites');
        }
        return Redirect::to($this->home_login);
    }

    public function selector()
    {
        if (Session::has('cuentas')) {
            $users_id = explode(',', Session::get('cuentas'));
            $users = User::whereIn('id',$users_id)->get();
            if(!isset($users)){
                return Redirect::to($this->home_login)->withErrors(['msg' => 'Error al recuperar las cuentas de usuario. Inténtelo de nuevo.']);
            }
            return view('llavemx/selector', compact('users'));
        }

        /*
        * MODIFICAR:
        * Ajustar segun roles del sistema la bandeja a la que manda
        */
        if (Auth::check()){
            if(Auth::user()->hasRole('admin')){
                return redirect('admin/resoluciones');
            }else if (Auth::user()->hasRole('representante_legal')) {
                return redirect('/mis-tramites');
            }
            return redirect('/revision-tramites');
        }
        return Redirect::to($this->home_login);
    }
}
