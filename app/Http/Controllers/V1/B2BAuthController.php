<?php

namespace App\Http\Controllers\V1;

use App\Models\B2BUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Facades\JWTAuth;
use Carbon\Carbon;
use App\Models\Common;

class B2BAuthController extends Controller
{

    public function __construct()
    {
        $this->middleware('auth:api', ['except' => ['login', 'test', 'register', 'loginOtpResend', 'loginOtpVerification', 'registerOtpVerification', 'registerOtpResend']]);
        // $this->middleware('auth:api', ['except' => ['login', 'register']]);
    }


    public function login(Request $request) {
        // return json_encode( Hash::make("12345678"));
        // dd("");
         $validator = Validator::make($request->all(), [
            'code' => 'required',
            'phone' => 'required',
            'password' => 'required|string|min:3'
        ],
            [
                'phone.required' => 'Enter phone number',
                'phone.regex' => 'Enter valid phone number',
                'password.required' => 'Enter password',
                'password.min' => 'Password must be min 3 character',
            ]);

        $credentials['phone'] = trim($request->code) .''. trim($request->phone);
        $credentials['password'] = trim($request->password);
        $credentials['is_active'] = 'Approved';


        if (empty($request->code)) {
            return response()->json(['error' => 'Country Code is required'], 401);
        }

        if (empty($request->phone)) {
            return response()->json(['error' => 'Phone is required'], 401);
        }

        if (empty($request->password)) {
            return response()->json(['error' => 'Password is required'], 401);
        }
        if (strlen($request->password) < 6) {
            return response()->json(['error' => 'Password minimum 6 character'], 401);
        }

        $isRegistered = DB::table('b2b_users')->where("phone", "=" , trim($request->code).  trim($request->phone) )->first();

        if ($isRegistered) {
            if ($isRegistered->is_active == 'Pending' ) {
                return response()->json(['error' =>'Approval is pending.Contact: +8801755513901 for details', 'success'=> false], 401);
            }
            else if ($isRegistered->is_active == 'Reject' ) {
                return response()->json(['error' =>'Account is pending.Contact: +8801755513901 for details', 'success' => false], 401);
            } else {

                $token = Auth::attempt($credentials);
                // if (!$token = auth('b2b')->attempt($credentials)) {
                if (!$token) {
                    return response()->json(['error' => 'Enter correct password', 'success' => false], 401);
                }
            }
        }else{
            return response()->json(['error' => 'Enter correct phone number', 'success' => false], 401);
        }

        $phoneNum = trim($request->code) .''. trim($request->phone);

        return $this->respondWithToken($token, $phoneNum);
    }

    public function loginOtpVerification(Request $request) {

        $validator = Validator::make($request->all(), [
            'code' => 'required',
            'phone' => 'required',
            'otp' => 'required'
        ]);


        if (empty($request->code)) {
            return response()->json(['error' => 'Code is required','success' => false], 401);
        }

        else if (empty($request->phone)) {
            return response()->json(['error' => 'Phone is required','success' => false], 401);
        }

        else if (empty($request->otp)) {
            return response()->json(['error' => 'OTP is required','success' => false], 401);
        }

        else {
            $mobileNumber = trim($request->code). trim($request->phone);
            $cust = DB::table('b2b_users')->where('phone', $mobileNumber)->first();

            if ($cust) {

                $isValidOtp = DB::table('b2b_users')->where('phone', $mobileNumber)
                                ->where('verify_password_token', $request->otp )->first();

                if ($isValidOtp) {
                    return response()->json([
                        'success' => true,
                                    'msg' => 'OTP is valid'
                    ], 200);

                } else {
                       return response()->json(['error' => 'Invalid OTP','success' => false], 401);
                }
            }  else {
                return response()->json(['error' => 'Customer not found','success' => false], 401);
            }
        }
    }

    public function loginOtpResend(Request $request) {
        $validator = Validator::make($request->all(), [
            'code' => 'required',
            'phone' => 'required'
        ]);


        if (empty($request->code)) {
            return response()->json(['error' => 'Code is required','success' => false], 401);
        }

        else if (empty($request->phone)) {
            return response()->json(['error' => 'Phone is required','success' => false], 401);
        }
        else {
            $mobileNumber = trim($request->code). trim($request->phone);
            $cust = DB::table('b2b_users')->where('phone', $mobileNumber)->first();

            if ($cust) {
                $verify_token = rand(1111, 9993);

                $time = config('constants.phone_verify_token_time_expire') / 60;
                $message = 'Your Verification code is ' . $verify_token . ' Thank you. Ryans Computers Ltd.';

                $today = Carbon::now();
                $second = $today->diffInSeconds($cust->verify_password_token_time);
                Log::info('phone-block-diffInSeconds: '. $second);

                $isSendNotification = $this->sendSMS($mobileNumber, $message);

                DB::table('b2b_users')
                    ->where('phone', $mobileNumber)
                    ->update([
                        'verify_password_token' => $verify_token,
                        'verify_password_token_time' => $today
                    ]);

                 return response()->json([
                        'success' => true,
                        'otp' => $verify_token,
                        'msg' => 'OTP send your phone'
                    ], 200);

            }  else {
                return response()->json(['success' => false ,'error' => 'Customer not found'], 401);
            }
        }
    }


    public function register(Request $request)
    {
        Log::info("customer-registration-log::: ". json_encode($request->all()) );


        $validator = Validator::make($request->all(), [
            'code' => 'required',
            'userName' => 'required',
            'orgName' => 'required',
            'email' => 'required|email',
            'phone' => 'required|regex:/[0-9]{10}/|min:4',
            'address' => 'required',
            // 'city' => 'required',
            // 'district' => 'required',
            'password' => 'required|string|min:3',
            'confirmPassword' => 'required|string|min:3'

        ]);

        // ,
        //     [
        //         'phone.required' => 'Enter phone number',
        //         'phone.regex' => 'Enter valid phone number',
        //         //'phone.min' => 'The phone number may not be smaller than 4',
        //         'password.required' => 'Enter password',
        //         'password.min' => 'Password must be min 3 character',
        //         'address.required' => 'Address is required'
        //     ]);

        if (empty($request->address)) {
            return response()->json(['error' => 'Address is required'], 401);
        }

        if (empty($request->email)) {
            return response()->json(['error' => 'Email is required'], 401);
        }

        if (empty($request->code)) {
            return response()->json(['error' => 'Country Code is required'], 401);
        }

        if (empty($request->phone)) {
            return response()->json(['error' => 'Phone is required'], 401);
        }

        if (empty($request->userName)) {
            return response()->json(['error' => 'Name is required'], 401);
        }

        if (empty($request->orgName)) {
            return response()->json(['error' => 'Organization name is required'], 401);
        }

         if ($request->password != $request->confirmPassword ) {
            return response()->json(['error' => 'User password and confirm password not matched!'], 401);
        }

        if (strlen($request->password) < 6) {
            return response()->json(['error' => 'Password minimum 6 character'], 401);
        }

        if (!filter_var($request->email, FILTER_VALIDATE_EMAIL)) {
            return response()->json(['error' => 'Please enter valid email address!'], 401);
        }


        $verify_token = rand(1111, 9993);
        $today = Carbon::now('GMT+6');

        $time = config('constants.phone_verify_token_time_expire') / 60;
        $message = 'Your Verification code is ' . $verify_token . ' Thank you. Ryans Computers Ltd.';

        $pass = trim($request['password']);
        $phone = trim($request['phone']);
        $mobileNumber = trim($request['code']) . trim($request['phone']);

      	//================= 2nd time reg if otp not verified =====================
        try {
          $verifyTokenNotSetObj = DB::table("b2b_users")
             					->where("phone",  $mobileNumber );

          if ($verifyTokenNotSetObj->count() > 0 ) {
            $v = $verifyTokenNotSetObj->first();
            DB::table("b2b_users")->where("b2b_user_id", $v->b2b_user_id )->delete();

            try {
             	DB::table('b2b_user_deletes')
                        ->insert([
                            'name'       => $request['userName'],
                            'org_name'   => $request['orgName'],
                            'email'      => trim($request['email']),
                            'phone'      => $mobileNumber,
                            'address'    => $request['address'],
                            'password'   => Hash::make($pass),
                            'avatar'     => 'no_avatar.jpg',
                            'verify_password_token'     => $verify_token,
                            'verify_password_token_time' => $today,
                            'is_active'     => 'Waiting',
                            'city_id'       => isset($request['city']) ? $request['city'] : null,
                            'district_id'   => isset($request['district']) ? $request['district'] : null,
                            'created_at'    => $today
                        ]);

            } catch(\Exception $e) {
             	Log::info("2nd time reg deleted info store >> error:: ". $e->getMessage() );
            }

          }
        }
      	catch(\Exception $e) {
          Log::info("2nd time reg if otp not verified >> error:: ". $e->getMessage() );
        }
      //================= 2nd time reg if otp not verified =====================



        $isRegisteredWithMobile = DB::table('b2b_users')
                    ->selectRaw("b2b_user_id,name, org_name, email, phone, address, avatar, created_at")
                    ->where("phone",  $mobileNumber )
                    ->first();

        $isRegisteredWithEmail = DB::table('b2b_users')
                    ->selectRaw("b2b_user_id,name, org_name, email, phone, address, avatar, created_at")
                    ->where("email", trim($request->email) )
                    ->first();

        if ($isRegisteredWithMobile) {

            	return response()->json(['error' => 'User '. $mobileNumber .' already registered, Please login'], 401);
        }
        else if ($isRegisteredWithEmail) {
            return response()->json(['error' => 'User '. $request->email .' already registered, Please login'], 401);
        }
        else {

            if (  strlen($mobileNumber) > 13  ) {
                return response()->json(['error' => 'Mobile number maximum 13 digit'], 401);
            }

            else if (  strlen($phone) < 10   ) {
                return response()->json(['error' => 'Mobile number must be 10 digit'], 401);
            }

            else if (!preg_match("/^[0-9]+$/", $phone)) {
                 return response()->json(['error' => 'Mobile number numbers only'], 401);
            }

            else {

                try {
                    $customer = new B2BUser();
                    $customer->name       = $request['userName'];
                    $customer->org_name   = $request['orgName'];
                    $customer->email      = trim($request['email']);
                    $customer->phone      = $mobileNumber;
                    $customer->address    = $request['address'];
                    $customer->password   = Hash::make($pass);
                    $customer->avatar     = 'no_avatar.jpg';
                    $customer->verify_password_token = $verify_token;
                    $customer->verify_password_token_time = $today;
                    $customer->is_active  = 'Waiting';
                    $customer->category = 'C';

                    $customer->city_id = isset($request['city']) ? $request['city'] : null;
                    $customer->district_id = isset($request['district']) ? $request['district'] : null;

                    $customer->created_at = $today;
                    $customer->save();

                    $isSendNotification = $this->sendSMS($mobileNumber, $message);



                    //============= rcom registration ===========================
                    try {
                        $isExistCustomer = DB::table('customers')
                                                ->where('phone', $phone)
                                                ->orWhere('email', trim($request['email']))
                                                ->count();

                        if ($isExistCustomer == 0) {
                            DB::table('customers')
                                    ->insert([
                                            'code'                       => $request['code'],
                                            'phone'                      => $phone,
                                            'password'                   => Hash::make($pass),
                                            'status'                     => '1',
                                            'phone_verify_token_time'    => $today,
                                            'phone_verify_token_count'   => 1,
                                            'is_email_verified'          => '0',
                                            'customer_is_exist'          => '1',
                                            'verify_token'               => $verify_token,
                                            'verify_password_token_time' => $today,
                                            'customer_reg_tracker'       => 'b2b_app',
                                            'is_customer'                => 'customer',
                                            'created_at'                 => $today,
                                            'location_id'                => 0
                                        ]);

                            $customerID = DB::getPdo()->lastInsertId();

                            DB::table('customer_details')
                                    ->insert([
                                            'customer_id'      => $customerID,
                                            'customer_name'    => $request['userName'],
                                            'customer_address' => $request['address'],
                                            'created_at'       => $today
                                        ]);
                        }
                    }
                    catch(\Exception $e) {
                        Log::info("reg-error: ". $e->getMessage() );
                    }



                    //================================================================= registration notification =========================================================================
                    try {
                        $template = "registration";
                        $body_text = "B2B App New Customer:". $request['userName'] ." registration has been completed. Please approve your account and then you can login the app.";
                        $subject = "B2B App Customer: ". $request['userName'] ." Registration Confirmation";
                        Log::info($body_text);
                        Common::sendMail(Common::notificationHolderEmail() , null, $body_text,  $subject, Common::notificationHolderName(), $template );
                    } catch(\Exception $e) {
                            Log::info($e->getMessage());
                    }
                    // ================================================================ registration notification ==========================================================================

                    return response()->json([
                            'user' => [
                                        'name' => $request['userName'] ,
                                        'email' => $request['email'],
                                        'phone' => $mobileNumber,
                                ],
                            'otp' => $verify_token ,
                            'msg' => 'Registered successfuly',
                            'success' => true
                        ]);


                    // ============ token code
                    // $cust = DB::table('b2b_users')->where('phone', $mobileNumber)->first();
                    // $credentials['phone'] = $mobileNumber;
                    // $credentials['password'] = trim($request['password']);
                    // $credentials['is_active'] = 'Pending';

                    // $token = auth('b2b')->attempt($credentials);
                    // if (!$token) {
                    //     return response()->json(['error' => 'Registered token missing', 'success' => false], 401);
                    // } else {

                        // auth()->setToken($token)->user();

                        // return response()->json([
                        //      'access_token' => $token,
                        //      'token_type' => 'bearer',
                        //     'user' => [
                        //                 'name' => $cust->name ,
                        //                 'email' => $cust->email,
                        //                 'phone' => $cust->phone,
                        //         ],
                        //     'otp' => $verify_token ,
                        //     'msg' => 'Registered successfuly',
                        //     'success' => true
                        // ]);
                    // }


                }
                catch(\Exception $e) {
                    return response()->json(['error' => 'Registered failed', 'e' => $e->getMessage() , 'success' => false], 401);
                }
            }


        }
    }

    public function registerOtpVerification(Request $request) {

        $validator = Validator::make($request->all(), [
            'code' => 'required',
            'phone' => 'required',
            'otp' => 'required'
        ]);


        if (empty($request->code)) {
            return response()->json(['error' => 'Code is required','success' => false], 401);
        }

        else if (empty($request->phone)) {
            return response()->json(['error' => 'Phone is required','success' => false], 401);
        }

        else if (empty($request->otp)) {
            return response()->json(['error' => 'OTP is required','success' => false], 401);
        }

        else {
            $mobileNumber = trim($request->code). trim($request->phone);
            $cust = B2BUser::where('phone', $mobileNumber)->first();

            if ($cust) {
                if ( $cust->verify_password_token ==  trim($request->otp) ) {
                    B2BUser::where('phone', $mobileNumber)
                            ->update([
                                'is_active' => 'Pending'
                            ]);
                    return response()->json(['msg' => 'OTP matched','success' => true], 200);
                }
                else {
                    return response()->json(['error' => 'Invalid OTP','success' => false], 401);
                }
            }  else {
                return response()->json(['error' => 'Customer not found','success' => false], 401);
            }
        }
    }

    public function registerOtpResend(Request $request) {
        $validator = Validator::make($request->all(), [
            'code' => 'required',
            'phone' => 'required'
        ]);


        if (empty($request->code)) {
            return response()->json(['error' => 'Code is required','success' => false], 401);
        }

        else if (empty($request->phone)) {
            return response()->json(['error' => 'Phone is required','success' => false], 401);
        }
        else {
            $mobileNumber = trim($request->code). trim($request->phone);
            $cust = DB::table('b2b_users')->where('phone', $mobileNumber)->first();

            if($cust) {
                $verify_token = rand(1111, 9993);

                $time = config('constants.phone_verify_token_time_expire') / 60;
                $message = 'Your Verification code is ' . $verify_token . ' Thank you. Ryans Computers Ltd.';

                $today = Carbon::now();
                $second = $today->diffInSeconds($cust->verify_password_token_time);
                Log::info('phone-block-diffInSeconds: '. $second);

                $isSendNotification = $this->sendSMS($mobileNumber, $message);

                DB::table('b2b_users')
                    ->where('phone', $mobileNumber)
                    ->update([
                        'verify_password_token' => $verify_token,
                        'verify_password_token_time' => $today
                    ]);

                 return response()->json([
                        'success' => true,
                        'otp' => $verify_token,
                        'msg' => 'OTP send your phone'
                    ], 200);

            }  else {
                return response()->json(['success' => false ,'error' => 'Customer not found'], 401);
            }
        }
    }




    protected function respondWithToken($token,   $mobile)
    {

        $verify_token = rand(1111, 9993);

        $time = config('constants.phone_verify_token_time_expire') / 60;
        $message = 'Your Verification code is ' . $verify_token . ' Thank you. Ryans Computers Ltd.';

        $mobileNumber = trim($mobile);

        $cust = DB::table('b2b_users')->where('phone', $mobileNumber)->first();


        $today = Carbon::now();
        $second = $today->diffInSeconds($cust->verify_password_token_time);
        Log::info('phone-block-diffInSeconds: '. $second);


        // if($second > 120) {

            /// $isSendNotification = $this->sendSMS($mobileNumber, $message);

             DB::table('b2b_users')->where('phone', $mobileNumber)->update([
                    'verify_password_token' => $verify_token,
                    'verify_password_token_time' => $today
                 ]);

            auth()->setToken($token)->user();
            return response()->json([
                'access_token' => $token,
                'token_type' => 'bearer',
                'user' => [
                            'name' => \Auth::user()->name ,
                            'email' => \Auth::user()->email,
                            'phone' => \Auth::user()->phone,
                    ],
                'otp' => $verify_token ,
                'success' => true
                // 'expires_in' => auth()->factory()->getTTL() * 60
            ]);
        // } else {
        //     return response()->json([

        //         'success' => false
        //         ]);
        // }
    }

    protected function sendSMS($mobileNumber, $message) {
        // Log::info('sendSMS:'.$mobileNumber .','. $message  );
        try{
            $conf = DB::table("sms_config")->selectRaw("api_token,sid,url")->where("id", 1)->first();
            $apiToken = $conf->api_token;
            $sid = $conf->sid;
            $url = $conf->url;

            $params = [
                "api_token" => $apiToken,
                "sid" => $sid,
                "msisdn" => $mobileNumber,
                "sms" => $message,
                "csms_id" => "2934fe343". mt_rand(1, 12333)
            ];

            $params = json_encode($params);

            $ch = curl_init(); // Initialize cURL
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
            curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Content-Type: application/json',
                'Content-Length: ' . strlen($params),
                'accept:application/json'
            ));

            $response = curl_exec($ch);
            curl_close($ch);

            return 1;
            // return $response;
        }catch(\Exception $e) {
            return 0;
        }
    }


    public function logout()
    {
        Auth::logout();
        return response()->json([
            'message' => 'Successfully logged out',
        ]);
    }


    // token refresh
    public function refresh()
    {
        $user = Auth::user();
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
