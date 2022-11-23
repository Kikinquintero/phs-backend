<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\UserCode;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;
use App\Models\Producto;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\Response;
use Exception;
use Illuminate\Foundation\Auth\AuthenticatesUsers;

// importante para envio de sms
use Twilio\Rest\Client;

class AuthController extends Controller
{
    /**
     * Create a new AuthController instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth:api', ['except' => ['login', 'register','buscaCorreo','update']]);
    }

    
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string|min:8',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        if (! $token = auth()->attempt($validator->validated())) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $user = auth()->user();
        // if($user->email_verified_at == NULL){
        //     return response()->json(['message'=>'Email no verificado, Confirme su correo electrónico',
        //     'err'=>'err'],  Response::HTTP_NOT_FOUND);
        // }
            

        
        // // auth()->user()->generateCode();
        // //   return response()->json(['message'=>'El usuario si existe'],201) ;

        return $this->createNewToken($token);
    }


    public function userProfile() {
        return response()->json(auth()->user());
    }

  
    public function logout()
    {
        // $this->guard()->logout();
        // return response()->json(['message' => 'Successfully logged out']);
        auth()->logout();
        return response()->json(['message' => 'El usuario cerró la sesión con éxito']);
    }

   
    public function refresh()
    {
        // return $this->respondWithToken($this->guard()->refresh());
        return $this->createNewToken(auth()->refresh());
    }

   
    protected function createNewToken($token){
        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth()->factory()->getTTL() * 60,
            'user' => auth()->user()
        ]);
    }


    public function guard()
    {
        return Auth::guard();
    }


    public function register(Request $request){
        $validator = Validator::make($request->all(), [
            'nombre' => 'required|string|between:2,100',
            'apellido' => 'required|string|between:2,100',
            'email'   => 'required|string|email|max:100|unique:users',
            'password'  => 'required|string|min:8',
        ]);

        if ($validator->fails()) {
                return response()->json($validator->errors()->toJson(),400);
        }

        $user = User::create(array_merge(
            $validator->validate(), 
             ['password' => bcrypt($request->password),
              'rol' =>'Usuario'
             ]
        ));



        return response()->json([
            'message' =>'usuario registrado exitosamente!',
            'user' => $user
        ],201);

    }



    public function update(Request $request)
    {
        $id = $request['id'];
        try {
            $data = User::find($id)
                        ->update($request->all());
        } catch (Exception $e) {
            return response()->json([
                'data' => [],
                'message'=>$e->getMessage()
            ], 500);
        }

        return response()->json([
            'data' => $data,
            'message' => 'Succeed'
        ],200);
    }



} // FIN
