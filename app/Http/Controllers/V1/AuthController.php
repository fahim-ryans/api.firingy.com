<?php

namespace App\Http\Controllers\V1;

use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class AuthController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api', ['except' => ['login', 'register']]);
    }

    public function login(Request $request)
    {
        $credentials = [
            'password' => $request->password,
            'status' => 1,
        ];
        $bdPhoneValidation = true;

        if ($request->email) {
            $credentials['email'] = $request->email;
        } elseif ($request->code && $request->phone) {
            if ($request->code == '880') {
                if ($request->phone[0] == '0') {
                    $bdPhoneValidation = false;
                } else {
                    if (strlen($request->phone) != 10) {
                        $bdPhoneValidation = false;
                    }
                }
            }
            if (!$bdPhoneValidation) {
                return response()->json(['error' => 'Please provide valid phone number'], 401);
            }
            $credentials['code'] = $request->code;
            $credentials['phone'] = $request->phone;
        } else {
            return response()->json(['error' => 'Please provide valid login information'], 401);
        }

        if (!$token = auth('customer')->attempt($credentials)) {
            if ($request->code && $request->phone) {
                $isRegistered = DB::select("select customer_id, status from customers where code = '" . $request->code . "' and phone = '" . $request->phone . "'");
                if ($isRegistered) {
                    return response()->json(['error' =>'Password do not match our records'], 401);
                }else{
                    return response()->json(['error' => 'This is a new number for us. You can signup now'], 401);
                }
            }
            if ($request->email) {
                $isRegistered = DB::select("select customer_id, status from customers where email = '" . $request->email . "'");
                if ($isRegistered) {
                    return response()->json(['error' =>'Password do not match our records'], 401);
                }else{
                    return response()->json(['error' =>'This is a new email address for us. You can signup now'], 401);
                }
            }

            return response()->json(['error' => 'This is a new number for us. You can signup now'], 401);
        }

        return $this->respondWithToken($token);
    }

    public function logout()
    {
        auth()->logout();

        return response()->json(['message' => 'Successfully logged out']);
    }

    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'phone' => 'required|regex:/[0-9]{9}/|min:4',
            'password' => 'required|string|min:3'
        ],
            [
                'phone.required' => 'Enter phone number',
                'phone.regex' => 'Enter valid phone number',
                'phone.min' => 'The phone number may not be smaller than 4',
                'password.required' => 'Enter password',
                'password.min' => 'Password must be min 3 character',
            ]);

        if ($validator->fails()) {
            return response()->json($validator);
        }

        $isRegistered = DB::select("select customer_id, status from customers where code = '" . $request->code . "' and phone = '" . $request->phone . "'");

        if ($isRegistered) {
            return response()->json('Mobile number already registered, please login');
        }

        $customer = Customer::create([
            'code' => $request['code'],
            'phone' => $request['phone'],
            'password' => Hash::make($request['password']),
            'status' => '0',
            'phone_verify_token_time' => now(),
            'phone_verify_token_count' => 1,
            'verify_token' => rand(1111, 9993),
            'created_at' => Carbon::now('GMT+6')
        ]);

        $mobileNumber = $customer->code . $customer->phone;

        $time = config('constants.phone_verify_token_time_expire') / 60;
        $message = 'Your Verification code for signup is ' . $customer->verify_token . '. This will expire in ' . $time . ' min.';

        // sendSMS($mobileNumber, $message);

        // return response()->json('We sent you SMS, please active your account');

        return response()->json('Registered successfully');
    }

    protected function respondWithToken($token)
    {
        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'user' => auth()->user()->only('email', 'code', 'phone'),
            // 'expires_in' => auth()->factory()->getTTL() * 60
        ]);
    }
}
