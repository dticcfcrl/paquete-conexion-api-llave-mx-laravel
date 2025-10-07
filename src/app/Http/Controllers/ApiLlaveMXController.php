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

use App\Services\{LlaveMXService, Users, CoreService};
use App\Models\{User, Role, Bitacora};

//--------------------------------------------------------------------------------------------------------------------------------------------------
// MODIFICAR: (Si no aplica eliminar todo el segmento)
use App\Models\UsuarioSolicitud;
//--------------------------------------------------------------------------------------------------------------------------------------------------

class ApiLlaveMXController extends Controller
{
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
        if (!env('LLAVE_ENDPOINT')) return redirect()->route('inicio')->with('error','El servicio de autenticación LlaveMX no está disponible en este momento. Inténtelo más tarde.');

        return Redirect::to($url);
    }

    public function register()
    {
        $url = env('LLAVE_ENDPOINT').env('LLAVE_ENDPOINT_CREATEACCOUNT');
        
        //Si no se tiene configurado el endpoint de llaveMX, regresar al inicio con mensaje de error
        if (!env('LLAVE_ENDPOINT')) return redirect()->route('inicio')->with('error','El servicio de autenticación LlaveMX no está disponible en este momento. Inténtelo más tarde.');

        return Redirect::to($url);
    }

    private function sendMailValidarcorreo($email, $token)
    {
        if (strpos($email, 'core_') !== false) return false;
        $subject = 'Confirmación de correo electrónico';
        $description = '<p>Hola.</p>';
        $description .= '<p>Para completar el proceso de registro, necesitamos verificar tu dirección de correo electrónico. Por favor, sigue el enlace a continuación para validar tu cuenta:</p>';
        $link = url('registro/validar-correo/' . $token);
        $description .= '<p><a href="' . $link . '">Haz clic aquí</a></p>';
        try {
            config(['mail.default' => 'secondary']); // Cambia al mailer secundario
            Mail::send([], [], function($message) use ($email, $subject, $description) {
                $fromAddress = config('mail.mailers.secondary.from.address');
                $fromName = config('mail.mailers.secondary.from.name');
                $message->from($fromAddress, $fromName);
                $message->to($email);
                $message->subject($subject);
                $message->html($description);

            });
        } catch (\Throwable $th) {
            return false;
        }
    }

    public function newAccount(Request $request)
    {   
        //Recuperamos los datos de la cuenta
        $curp = Session::get('curp');
        $correo = $request->correo_newAccount;
        $nombre = Session::get('nombre');
        $apellido1 = Session::get('apellido1');
        $apellido2 = Session::get('apellido2');
        $core_user_id = Session::get('core_user_id');
        $core_token_session = Session::get('core_token_session');
        //Validar que el correo para el mismo usuario no este previamente registrado
        $preregistrado = User::where('email',$correo)
                        ->whereRaw("unaccent(UPPER(first_name)) = unaccent(UPPER('".$nombre."')) AND unaccent(UPPER(last_name)) = unaccent(UPPER('".$apellido1."')) AND unaccent(UPPER(second_last_name)) = unaccent(UPPER('".$apellido2."'))")
                        ->exists();
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
        $data_user = [
            'first_name' => $nombre,
            'last_name' => $apellido1,
            'second_last_name' => $apellido2,
            'email' => $correo,
            'user_core_id' => isset($core_user_id)?$core_user_id:null,
            'token_session' => isset($core_token_session)?$core_token_session:null
        ];
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
        //--------------------------------------------------------------------------------------------------------------------------------------------------
        //Limpiamos la session de selección de cuenta si existen multiples
        Session::forget('cuentas');
        /*
        * MODIFICAR:
        * Ajustar segun estructura de usuarios del sistema
        */
        $data = DB::select("SELECT u.id as user_id
                            FROM public.users u
                            LEFT JOIN public.usuarios_solicitudes us ON us.usuario_id = u.id
                            WHERE (UPPER(u.email) = UPPER(?) OR UPPER(us.curp) = UPPER(?) OR 
                                (
                                    unaccent(UPPER(u.first_name)) = unaccent(UPPER(?)) AND 
                                    unaccent(UPPER(u.last_name)) = unaccent(UPPER(?)) AND 
                                    unaccent(UPPER(u.second_last_name)) = unaccent(UPPER(?))
                                )
                                ) AND u.deleted_at IS NULL AND u.activo = true",
                            [$correo, $curp, $nombre, $apellido1, $apellido2]);
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
        //Sino encuentra cuentas manda a la bandeja por default al rol
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
            return Redirect::to('/')->withErrors(['msg' => 'Error de validación de seguridad. Inténtelo de nuevo.']);
        }
        $llave = new LlaveMXService();
        //PASO 02. Transformar el CODE por un TOKEN de LlaveMX
        $token = $llave->getToken($request->code);
        if(!$token){
            //Regresamos al login con error de obtención de token
            return Redirect::to('/')->withErrors(['msg' => 'Error al obtener el token de autenticación. Inténtelo de nuevo.']);
        }
        //PASO 03. Recuperar los datos del usuario y persona moral usando el token
        $data_user = $llave->getUser($token);
        if(!$data_user){
            //Regresamos al login con error de obtención de datos del usuario
            return Redirect::to('/')->withErrors(['msg' => 'Error al obtener los datos del usuario desde LlaveMX. Inténtelo de nuevo.']);
        }
        $data_morales = $llave->getPersonasMorales($token);
        //Registrar/Actualizar la info del usuario al core
        $core_user = $llave->storeDataAtCore($data_user, $data_morales);

        //PASO 04. Pasar datos a variables internas
        /* REAL */
        $curp = $data_user['curp'];
        $correo = $data_user['correo'];
        $nombre = $data_user['nombre'];
        $apellido1 = $data_user['primerApellido'];
        $apellido2 = $data_user['segundoApellido'];
        
        /* EJEMPLO USUARIO 2 CUENTAS */
        
        $curp = 'TOJA650527HDFRRM08';
        $correo = 'core_armandoatj@gmail.com';
        $nombre = 'ARMANDO';
        $apellido1 = 'TORRES';
        $apellido2 = 'JÚAREZ';
        
        /*
        $curp = 'PALE650527MDFLPL00';
        $correo = 'elvia@fesamsindicato.com.mx';
        $nombre = 'ELVIA';
        $apellido1 = 'PALANCARES';
        $apellido2 = 'LÓPEZ';
        */
        
        /* EJEMPLO USUARIO 1 CUENTAS (Representante legal) */
        /*
        $curp = 'SAAJ750514HJCNGS02';
        $correo = 'jesus.sanchez.centrolaboral@gmail.com';
        $nombre = 'JOSÉ DE JESÚS';
        $apellido1 = 'SANCHEZ';
        $apellido2 = 'AGUILERA';
        */
        /* EJEMPLO USUARIO 0 CUENTAS */
        /*
        $curp = 'CECJ770822HSLYSR04';
        $correo = 'jceyca@gmail.com';
        $nombre = 'JORGE OMAR';
        $apellido1 = 'CEYCA';
        $apellido2 = 'CASTRO';
        */

        //Guardamos en sesión los datos de la cuenta por si queremos crear nueva cuenta
        Session::put('curp', $curp);
        Session::put('nombre', $nombre);
        Session::put('apellido1', $apellido1);
        Session::put('apellido2', $apellido2);
        Session::put('core_user_id', @$core_user['id']);
        Session::put('core_token_session', @$core_user['token_session']);

        //PASO 05. Revisamos si existe el usuario previamente
        /*
        * MODIFICAR:
        * Ajustar segun estructura de usuarios del sistema
        */
        $data = DB::select("SELECT u.id as user_id
                            FROM public.users u
                            LEFT JOIN public.usuarios_solicitudes us ON us.usuario_id = u.id
                            WHERE (UPPER(u.email) = UPPER(?) OR UPPER(us.curp) = UPPER(?) OR 
                                (
                                    unaccent(UPPER(u.first_name)) = unaccent(UPPER(?)) AND 
                                    unaccent(UPPER(u.last_name)) = unaccent(UPPER(?)) AND 
                                    unaccent(UPPER(u.second_last_name)) = unaccent(UPPER(?))
                                )
                                ) AND u.deleted_at IS NULL AND u.activo = true",
                            [$correo, $curp, $nombre, $apellido1, $apellido2]);
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
                $core_service = new CoreService();
                $data_core = [
                    'email' => $user->email,
                    'user_core_id' => $user->user_core_id
                ];
                $response = $core_service->loginInCore($data_core);
                //Destruimos la session de state
                Session::forget('state_csrf');
            }
        }else{
            //PASO 06b. Si no existe, crear el usuario y luego iniciar sesión
            $role_name = 'representante_legal';
            $data_user = [
                'first_name' => $nombre,
                'last_name' => $apellido1,
                'second_last_name' => $apellido2,
                'email' => $correo,
                'user_core_id' => isset($core_user)?@$core_user['id']:null,
                'token_session' => isset($core_user)?@$core_user['token_session']:null
            ];
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
            //Abrimos sesión en la plataforma
            Auth::login($user, true);
            //Abrimos sesión en registro
            $core_service = new CoreService();
            $data_core = [
                'email' => $user->email,
                'user_core_id' => $user->user_core_id
            ];
            $response = $core_service->loginInCore($data_core);
            //Destruimos la session de state
            Session::forget('state_csrf');
        }
        //PASO 07. Redirigir al usuario a la página principal del sistema acorde a su rol
        if (!Auth::check()) return redirect()->route('inicio')->with('error','No se logro iniciar sesión con el usuario. Inténtelo de nuevo.');
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
        //return Redirect::to('/');
    }

    public function loginSelector($hash_user_id)
    {
        $user_id = Crypt::decryptString($hash_user_id);
        $user = User::find($user_id);
        if(!isset($user->id)){
            return redirect()->route('inicio')->with('error','No se encontró el usuario seleccionado. Inténtelo de nuevo.');
        }
        Auth::login($user, true);
        //Abrimos sesión en registro
        $core_service = new CoreService();
        $data_core = [
            'email' => $user->email,
            'user_core_id' => $user->user_core_id
        ];
        $response = $core_service->loginInCore($data_core);
        //Destruimos la session de state
        Session::forget('state_csrf');
        //PASO 07. Redirigir al usuario a la página principal del sistema acorde a su rol
        if (!Auth::check()) return redirect()->route('inicio')->with('error','No se logro iniciar sesión con el usuario seleccionado. Inténtelo de nuevo.');
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
        //return Redirect::to('/');
    }

    public function selector()
    {
        if (Session::has('cuentas')) {
            $users_id = explode(',', Session::get('cuentas'));
            $users = User::whereIn('id',$users_id)->get();
            if(!isset($users)){
                return redirect()->route('inicio')->with('error','Error al recuperar las cuentas de usuario. Inténtelo de nuevo.');
            }
            return view('llavemx/selector', compact('users'));
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
        //return Redirect::to('/');
    }
}
