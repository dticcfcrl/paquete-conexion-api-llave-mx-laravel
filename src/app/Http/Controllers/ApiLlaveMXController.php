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

    public function callback(Request $request)
    {
        error_log('LlaveMX callback');
        error_log('code: '.$request->code);
        //Limpiamos la cookie de selección de cuenta si existe
        Cookie::queue(Cookie::forget('j7pk19'));
        //PASO 01. Validar el state
        /* 
        *  Nota de seguridad: 
        *  Cuando la aplicación cliente identifique la redirección con los parámetros antes 
        *  mencionados, deberá validar que el “state” sea el mismo que envió a Llave MX para cada solicitud de 
        *  inicio de sesión de un usuario, ya que este parámetro sirve para mitigar ataques CSRF.
        */
        $state_csrf = Cookie::get('state_csrf');
        error_log('state request: '.$request->state);
        error_log('state cookie: '.$state_csrf);
        if($state_csrf != $request->state){
            //Regresamos al login con error de state inválido
            return Redirect::to('/')->withErrors(['msg' => 'Error de validación de seguridad. Inténtelo de nuevo.']);
        }
        //PASO 02. Obtener el token
        $llave = new LlaveMXService();
        $token = $llave->getToken($request->code);
        error_log('token: '.$token);
        if(!$token){
            //Regresamos al login con error de obtención de token
            return Redirect::to('/')->withErrors(['msg' => 'Error al obtener el token de autenticación. Inténtelo de nuevo.']);
        }
        //PASO 03. Recuperar la información del usuario
        $data_user = $llave->getUser($token);
        if(!$data_user){
            //Regresamos al login con error de obtención de datos del usuario
            return Redirect::to('/')->withErrors(['msg' => 'Error al obtener los datos del usuario desde LlaveMX. Inténtelo de nuevo.']);
        }
        error_log('data_user: '.print_r($data_user,true));
        //PASO 04. Identificar el user acorde al curp del usuario
        /* REAL */
        /*
        $curp = $data_user['curp'];
        $correo = $data_user['correo'];
        $nombre = $data_user['nombre'];
        $apellido1 = $data_user['primerApellido'];
        $apellido2 = $data_user['segundoApellido'];
        */
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
        error_log('curp: '.$curp);
        //PASO 05. Si existe el usuario, iniciar sesión
        /*
        * NOTA:
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

                            //usuarios_solicitudes se_registro bandera tomarla en cuenta, la cuenta institucional no se puede usar para representante legal!! 


        //Recuperando los ids de las cuentas de usuario encontradas
        $users_id = array_map(fn($row) => $row->user_id, $data);
        //Recuperando los usuarios
        $users = User::whereIn('id',$users_id)->get();
        error_log('ACCOUNT:'.$users->count());
        if($users->count() > 0){
            if ($users->count() > 1) {
                //Guardamos los users en la cookie 'j7pk19'
                Cookie::queue('j7pk19', Crypt::encryptString(implode(',', $users_id)), 1440);
                //Redireccionamos a una vista para que el usuario seleccione la cuenta con la que desea ingresar
                return redirect()->route('llavemx.selector');
            }else{
                $user = $users->first();
                Auth::login($user, true);
                //Auth::loginUsingId($user->id);
            }
        }else{
            //PASO 06. Si no existe, crear el usuario y luego iniciar sesión
            //Crear el usuario
            error_log('crear el usuario!!');
            $role_name = 'representante_legal';
            $data_user = [
                'first_name' => $nombre,
                'last_name' => $apellido1,
                'second_last_name' => $apellido2,
                'email' => $correo
            ];
            $user = User::create($data_user);
            $role = Role::where('name', $role_name)->first();
            $user->assignRole($role->id);
            $user->password = Hash::make('hola centro'); //Contraseña aleatoria Str::random(16)
            $user->save();

            //Necesario iniciar sesión en el core con una cuenta de ADMIN SECRETO
            /*
            $core_service = new CoreService();
            $data_admin = [
                'email' => 'core_admin@fake.email',
                'password' => 'hola centro'
            ];
            $response = $core_service->loginInCore($data_admin);
            dd($response);
            */
            //Registrar en el core el usuario
            $user_services = new Users();
            $response = $user_services->registerUserInCore($data_user); //<- OJO aquí esta fallando, ya que no se ha iniciado sesión en el core el register se ejecuta como admin por eso no falla en el CRUD Users
            if (isset($response['id'])){
                $user->token_session = $response['token_session'];
                $user->user_core_id = $response['id'];
                $user->update_data_at = $response['update_data_at'];
                $user->save();
            }
            /*
            * NOTA:
            * Posiblemente no requiere bitacorización de la creación del usuario, si es así eliminar este bloque
            */
            Bitacora::create([
                'usuario_id' => $user->id,
                'code' => 'admin',
                'subcode' => 'usuarios',
                'descripcion' => 'Se restablecio el usuario o contraseña',
                'referencia_id' => $user->id,
                'tipo_referencia' => 'usuario'
            ]);
            Auth::login($user, true);
        }

        //PASO 07. Redirigir al usuario a la página principal del sistema acorde a su rol
        if (!Auth::check()) return redirect()->route('inicio')->with('error','No se logro iniciar sesión con el usuario. Inténtelo de nuevo.');
        /*
        * NOTA:
        * Ajustar segun roles del sistema
        */
        if(Auth::user()->hasRole('admin')){
            return redirect('admin/resoluciones');
        }else if (Auth::user()->hasRole('representante_legal')) {
            return redirect('/mis-tramites');
        }
        
        return redirect('/revision-tramites');
        //return Redirect::to('/');
    }

    public function logout(Request $request)
    {
        error_log('LlaveMX logout');
        error_log('code: '.$request->code);
        
    }
    public function loginSelector($hash_user_id)
    {
        error_log('LlaveMX loginSelector');
        error_log('code: '.$hash_user_id);
        $user_id = Crypt::decryptString($hash_user_id);
        $user = User::find($user_id);
        if(!isset($user->id)){
            return redirect()->route('inicio')->with('error','No se encontró el usuario seleccionado. Inténtelo de nuevo.');
        }
        Auth::login($user, true);
        //Auth::loginUsingId($user->id);
        //PASO 07. Redirigir al usuario a la página principal del sistema acorde a su rol
        if (!Auth::check()) return redirect()->route('inicio')->with('error','No se logro iniciar sesión con el usuario seleccionado. Inténtelo de nuevo.');
        /*
        * NOTA:
        * Ajustar segun roles del sistema
        */
        if(Auth::user()->hasRole('admin')){
            return redirect('admin/resoluciones');
        }else if (Auth::user()->hasRole('representante_legal')) {
            return redirect('/mis-tramites');
        }
        
        return redirect('/revision-tramites');
        //return Redirect::to('/');
    }

    public function login(Request $request)
    {
        error_log('LlaveMX login');
        error_log('code: '.$request->code);
        //PASO 05. Si existe el usuario, iniciar sesión
        
        //PASO 06. Si no existe, crear el usuario y luego iniciar sesión

        //PASO 07. Redirigir al usuario a la página principal del sistema
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
