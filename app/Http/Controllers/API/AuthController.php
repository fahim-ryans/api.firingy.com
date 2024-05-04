<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\B2BUser;

class AuthController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api', ['except' => ['login', 'register']]);
    }

    public function login(Request $request)
    {
        try {
            $request->validate([
                'phone' => 'required|string',
                'password' => 'required|string',
            ]);

            $phone = trim($request->code) . "" . trim($request->phone);

            // $credentials = $request->only('phone', 'password');
            $credentials['phone'] = $phone;
            $credentials['password'] = $request->password;
            $token = Auth::attempt($credentials);

            if (!$token) {
                return response()->json([
                    'message' => 'Unauthorized',
                ], 401);
            }

            $user = Auth::user();
            // return response()->json([
            //     'user' => $user,
            //     'authorization' => [
            //         'token' => $token,
            //         'type' => 'bearer',
            //     ]
            // ]);

            return response()->json([
                'user' => [
                    'name' => $user->org_name,
                    'email' => $user->email ,
                    'phone' => $user->phone
                ],
                'access_token' => $token,
                'token_type' => 'bearer',
                "otp" => 124 ,
                "success"=> true
            ]);

        }
        catch(\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
                "success"=> false
            ]);
        }

    }

    public function register(Request $request)
    {

        $phone = trim($request->code) . "" . trim($request->phone);
        $isExist = B2BUser::where('phone', $phone)->orWhere('email', $request->email)->count();

        if ($isExist == 0) {

            $request->validate([
                'code' => 'required|integer',
                'phone' => 'required|integer',

                'password' => 'required|string|min:6',
                'confirmPassword' => 'required|string|min:6',

                'userName' => 'required|string',
                'orgName' => 'required|string',
                'email' => 'required|string|email|max:255|unique:b2b_users',

                'address' => 'required|string|max:255',
                'district' => 'required',
                'city' => 'required'
            ]);

            $u =    [
                        'phone' => $phone,
                        'email' => $request->email,
                        'password' => Hash::make($request->password),

                        'name' => $request->userName,
                        'org_name' => $request->orgName,
                        'address' => $request->address,

                        'city_id' => intval($request->city),
                        'district_id' => intval($request->district),
                        'location_id' => 0,

                        'is_active' => 'Pending',
                        'category' => 'C'
                    ];

            $user = B2BUser::create($u);
            return response()->json([
                'message' => 'User created successfully',
                'user' => $user ,
                'success' => true
            ]);
        }
        else
        {
            return response()->json([
                'message' => 'User already registered',
                'user' => null,
                'success' => false
            ]);
        }
    }

    public function logout()
    {
        Auth::logout();
        return response()->json([
            'message' => 'Successfully logged out',
        ]);
    }

    public function refresh()
    {
        $user = Auth::user();
        // return response()->json([
        //     'user' => Auth::user(),
        //     'authorisation' => [
        //         'token' => Auth::refresh(),
        //         'type' => 'bearer',
        //     ]
        // ]);


        return response()->json([
                'user' => [
                    'name' => $user->org_name,
                    'email' => $user->email ,
                    'phone' => $user->phone
                ],
                'access_token' => Auth::refresh(),
                'token_type' => 'bearer',
                "success"=> true
         ]);
    }
}
