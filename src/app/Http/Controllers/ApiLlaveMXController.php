<?php

namespace App\Http\Controllers;

use Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Cookie;

use App\Services\{LlaveMXService, Users, CoreService};
use App\Models\{User, Role, Bitacora};

class ApiLlaveMXController extends Controller
{
    public function login()
    {
        //Generar el state y guardarlo en una cookie por 10 minutos
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

    public function callback(Request $request)
    {
        //Limpiamos la cookie de selección de cuenta si existen multiples
        Cookie::queue(Cookie::forget('j7pk19'));
        //PASO 01. Validar el state
        /* 
        *  NOTA DE SEGURIDAD: 
        *  Cuando la aplicación cliente identifique la redirección con los parámetros antes 
        *  mencionados, deberá validar que el “state” sea el mismo que envió a Llave MX para cada solicitud de 
        *  inicio de sesión de un usuario, ya que este parámetro sirve para mitigar ataques CSRF.
        */
        $state_csrf = Cookie::get('state_csrf');
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
        /*
        $curp = 'TOJA650527HDFRRM08';
        $correo = 'core_armandoatj@gmail.com';
        $nombre = 'ARMANDO';
        $apellido1 = 'TORRES';
        $apellido2 = 'JÚAREZ';
        */
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
                //Guardamos los users en la cookie 'j7pk19'
                Cookie::queue('j7pk19', Crypt::encryptString(implode(',', $users_id)), 1440);
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
                //Destruimos la cookie de state
                Cookie::queue(Cookie::forget('state_csrf'));
            }
        }else{
            //PASO 06b. Si no existe, crear el usuario y luego iniciar sesión
            $role_name = 'representante_legal';
            $data_user = [
                'first_name' => $nombre,
                'last_name' => $apellido1,
                'second_last_name' => $apellido2,
                'email' => $correo,
                'user_core_id' => isset($core_user)?$core_user->id:null,
                'token_session' => isset($core_user)?$core_user->token_session:null
            ];
            $user = User::create($data_user);
            $role = Role::where('name', $role_name)->first();
            $user->assignRole($role->id);
            $user->password = bcrypt(Str::random(16)); //Hash::make('hola centro'); //Contraseña aleatoria Str::random(16)
            $user->save();
            /*
            * MODIFICAR:
            * Revisar si requiere bitacorización
            */
            Bitacora::create([
                'usuario_id' => $user->id,
                'code' => 'admin',
                'subcode' => 'usuarios',
                'descripcion' => 'Se restablecio el usuario o contraseña',
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
            //Destruimos la cookie de state
            Cookie::queue(Cookie::forget('state_csrf'));
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
        //Destruimos la cookie de state
        Cookie::queue(Cookie::forget('state_csrf'));
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
        $users_id = explode(',', Crypt::decryptString(Cookie::get('j7pk19')));
        $users = User::whereIn('id',$users_id)->get();
        if(!isset($users)){
            return redirect()->route('inicio')->with('error','Error al recuperar las cuentas de usuario. Inténtelo de nuevo.');
        }
        return view('llavemx/selector', compact('users'));
    }
}
