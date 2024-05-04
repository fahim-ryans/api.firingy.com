<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Models\B2BUser;
use App\Models\Common;
use Carbon\Carbon;
use App\Models\B2BCustomerQueryProduct;
use App\Models\B2BCustomerQuery;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use App\Lib\B2BLib;
use App\Lib\B2BUtility;
use JWTAuth;

class B2BCustomerController extends Controller {


    private  $category_id_array = [];


    public function getUserProfile(Request $request) {

        // $update_info = "New version of Ryans B2B is launched on 16th March, 2024. Please uninstall older version (V:0.0.12). Thank you for cooperation. ";
        // $update_info = "ঈদ মোবারক
        // পবিত্র ঈদুল ফিতর উপলক্ষে ১১-১২ এপ্রিল ২০২৪ তারিখ  B2B Query এর কার্যক্রম স্থগিত থাকবে । ১৩ এপ্রিল ২০২৪ থেকে যথারীতি সকল কার্যক্রম পরিচালিত হবে।
        // ধন্যবাদ";

        $update_info = "";

        $user = $request->bearerToken();
        if (empty($user)) {
            return response()->json(['error'=> 'User not found !'  ], 401);
        }
        else {
         if(empty($request->phone)) {
              return response()->json(['error'=> 'Phone number required'  ], 401);
         }
         else {

            $user = DB::table('b2b_users')
                    ->leftJoin("b2b_cities", "b2b_cities.id", "=", "b2b_users.city_id")
                    ->leftJoin("districts", "districts.id", "=", "b2b_users.district_id")
                    ->selectRaw("b2b_users.name, b2b_users.b2b_user_id , b2b_users.org_name,b2b_users.email, b2b_users.phone, b2b_users.address , b2b_cities.id as city_id,b2b_cities.name as city_name, districts.id as district_id,districts.name as district_name")
                    ->where('b2b_users.phone', '=', $request->phone)
                    //->select('name', 'org_name', 'email', 'phone', 'address')
                    ->get();

            if( count($user) > 0 ) {

                $userObj = DB::table('b2b_user_details')
                ->where("status", "1")
                ->where("b2b_user_id", $user[0]->b2b_user_id)
                ->orderBy("created_at", "desc")
                ->first();
                if ($userObj) {

                    $d = DB::table('districts')->where("id", $userObj->district_id)->first();
                    $c = DB::table('b2b_cities')->where("id", $userObj->city_id)->first();
                    $obj = [
                        "name"=> $userObj->name,
                        "org_name" => $userObj->org_name,
                        "email" => $userObj->email,
                        "phone" => $request->phone,
                        "address" => $userObj->address,
                        "city_id" => $userObj->city_id,
                        "city_name" => isset($c->name) ? $c->name : null,
                        "district_id" => $userObj->district_id,
                        "district_name" => isset($d->name) ? $d->name : null ,
                        'update_info' => $update_info
                    ];


                    // $users = DB::table('b2b_users')
                    //                     ->leftJoin("b2b_cities", "b2b_cities.id", "=", "b2b_users.city_id")
                    //                     ->leftJoin("districts", "districts.id", "=", "b2b_users.district_id")
                    //                     ->leftJoin("b2b_user_details", "b2b_user_details.b2b_user_id", "=", "b2b_users.b2b_user_id")
                    //                     ->selectRaw("b2b_user_details.name, b2b_user_details.org_name,b2b_user_details.email, b2b_users.phone, b2b_user_details.address , b2b_cities.id as city_id,b2b_cities.name as city_name, districts.id as district_id,districts.name as district_name")
                    //                     ->where('b2b_users.b2b_user_id', '=', $userObj->b2b_user_id)

                    //                     ->get();

                    // return response()->json(['t' =>$users[0], 'q' =>  $userObj ]);
                    return response()->json($obj);
                }
                else {
                    $prevObj = [
                        "name"=> $user[0]->name,
                        "org_name" => $user[0]->org_name,
                        "email" => $user[0]->email,
                        "phone" => $user[0]->phone,
                        "address" => $user[0]->address,
                        "city_id" => $user[0]->city_id,
                        "city_name" => isset($user[0]->city_name) ? $user[0]->city_name : null,
                        "district_id" => $user[0]->district_id,
                        "district_name" => isset($user[0]->district_name) ? $user[0]->district_name : null ,
                        'update_info' => $update_info
                    ];
                    return response()->json($prevObj);
                }

            } else {
                 return response()->json(['error'=> 'Phone number is not registered' , 'update_info' => $update_info  ], 401);
            }
         }
        }
    }

    public function userProfileUpdate(Request $request) {
        Log::info("userProfileUpdate:: ". json_encode($request->all()) );
        $user = trim($request->bearerToken());
        Log::info('bearer-token: '. json_encode($user));

        if (empty($user)) {
            return response()->json(['error'=> 'User not found !'  ], 401);
        }
        else {

             if(empty($request->phone)) {
                  return response()->json(['error'=> 'Phone number required'  ], 401);
             }
             else if(empty($request->name)) {
                  return response()->json(['error'=> 'Name required'  ], 401);
             }
             else if(empty($request->address)) {
                  return response()->json(['error'=> 'Address required'  ], 401);
             }
             else {


                $user = DB::table('b2b_users')
                            ->selectRaw('b2b_user_id , name, org_name, email, phone, address, city_id, district_id, is_active, category, location_id')
                            ->where('phone',   $request->phone)
                            ->first();
                if ( $user )
                {
                     Log::info('before-update: '. json_encode($user));
                     $flag = $user->is_active;
                     Log::info('before-update-status: '.  $flag );


                    if ($flag != "Approved") {
                        return response()->json(['error'=> 'Account is not approved'  ], 401);
                    }
                    else {


                        try {
                       $isDetailExist = DB::table("b2b_user_details")
                        ->where("b2b_user_id", $user->b2b_user_id)
                        ->where("status", 1)
                        ->orderBy("created_at", "desc")
                        ->first();

                        if ($isDetailExist) {
                            $obj = [
                                'name'        => $isDetailExist->name,
                                'email'       => $isDetailExist->email,
                                'address'     => $isDetailExist->address,
                                'org_name'    => $isDetailExist->org_name,
                                'city_id'     => $isDetailExist->city_id  ,
                                'district_id' => $isDetailExist->district_id,
                                'updated_at'  => Carbon::now()
                            ];

                            DB::table('b2b_users')
                                        ->where('phone', '=', $request->phone)
                                        ->update($obj);
                        }
                      }catch(\Exception $e) {
                        Log::info('inner-update-Error: '.  $e->getMessage() );
                      }

                        $count = DB::table("b2b_user_details")
                                            ->where("b2b_user_id", $user->b2b_user_id )
                                            ->where("status", 1)
                                            ->count();
                        // {"phone":"8801793497940","name":"Samer","address":"Dhaka","email":"samerseu@gmail.com","org_name":"Home"}
                        $parse_data = json_decode(json_encode($request->all()), true);
                        $insertData = [];
                        foreach($parse_data as $key => $pd) {
                            if ($key != "phone") {
                                if ($key == "city") {
                                    $insertData["city_id"] =  $pd;
                                } else {
                                    $insertData[$key] =  $pd;
                                }
                            }
                        }
                        $u = DB::table("b2b_users")->where("phone", $request->phone)->first();
                        $insertData["b2b_user_id"] = $u->b2b_user_id;
                        // $data = array_except(json_decode(json_encode($request->all()), true) , ['phone']);
                        DB::table("b2b_user_details")->insert($insertData);

                        // {"phone":"8801755554910","name":"Fahim","address":"Dhaka, Agargaon","email":"fahim@ryans.com","org_name":"Ryans Computers Ltd."}

                        // DB::table("b2b_user_details")
                        // ->insert([
                        //             "b2b_user_id" => $user->b2b_user_id ,
                        //             "name" => $request->name ,
                        //             "email" => isset($request->email) ? $request->email : $user->email  ,
                        //             "org_name" => $user->org_name ,
                        //             "address" => $request->address ,
                        //             "city_id" => isset($request->city) ? $request->city : $user->city_id ,
                        //             "district_id" => isset($request->district) ? $request->district : $user->district_id ,
                        //             "location_id" => $user->location_id ,
                        //             "category" => $user->category,
                        //             "status" => 0,
                        //             "created_at" => Carbon::now()
                        //         ]);


                        if ($count == 0 ) {
                                $updatedUser = DB::table('b2b_users')
                                            ->leftJoin("b2b_cities", "b2b_cities.id", "=", "b2b_users.city_id")
                                            ->leftJoin("districts", "districts.id", "=", "b2b_users.district_id")
                                            ->selectRaw("b2b_users.name,
                                                    b2b_users.org_name,b2b_users.email,
                                                    b2b_users.phone, b2b_users.address ,
                                                    b2b_cities.id as city_id,
                                                    b2b_cities.name as city_name,
                                                    districts.id as district_id,
                                                    districts.name as district_name")
                                            ->where('b2b_users.phone', '=', $request->phone)
                                            ->get();

                            return response()->json([
                                                // 'msg' => 'Thank you for submission, admin will review and update',
                                                'msg' => B2BLib::allConfirmationTexts("profile_update_submit"),
                                                'user'=> $updatedUser[0],
                                                'success'=> true
                                            ]);
                        }
                        else
                        {
                            $det = DB::table("b2b_user_details")
                                    ->selectRaw("b2b_users.name, b2b_users.org_name,b2b_users.email,
                                                b2b_users.phone, b2b_users.address , b2b_cities.id as city_id,
                                                b2b_cities.name as city_name, districts.id as district_id, districts.name as district_name")

                                    ->leftJoin("b2b_users", "b2b_users.b2b_user_id", "=", "b2b_user_details.b2b_user_id")
                                    ->leftJoin("b2b_cities", "b2b_cities.id", "=", "b2b_users.city_id")
                                    ->leftJoin("districts", "districts.id", "=", "b2b_users.district_id")
                                    ->where("b2b_user_details.b2b_user_id",  $user->b2b_user_id)
                                    ->where("b2b_user_details.status", 1)
                                    ->first();

                            $data = [
                                    'name' => $det->name,
                                    'org_name' => isset($det->org_name) ? $det->org_name : $updatedUser[0]->org_name,
                                    'email' => $user->email,
                                    'phone' => $user->phone,
                                    'address' => isset($det->address) ? $det->address : $updatedUser[0]->address,
                                    'city_id' => isset($det->city_id) ? $det->city_id : $updatedUser[0]->city_id,
                                    'city_name' => isset($det->city_name) ? $det->city_name : $updatedUser[0]->city_name,
                                    'district_id' => isset($det->district_id) ? $det->district_id : $updatedUser[0]->district_id,
                                    'district_name' => isset($det->district_name) ? $det->district_name : $updatedUser[0]->district_name
                                ];
                            // 'user'=> $updatedUser[0],
                            return response()->json([
                                // 'msg' => 'Thank you for submission, admin will review and update',
                                'msg' => B2BLib::allConfirmationTexts("profile_update_submit"),
                                'user'=> $data,
                                'success'=> true
                            ]);
                        }
                        // Log::info('after-update: '. json_encode($updatedUser));
                    }
                }
                else {
                    return response()->json(['error'=> 'Phone number is not registered'  ], 401);
                }
             }
        }
    }



    public function getProductDetailsAttribute(Request $request) {
        $user = trim($request->bearerToken());
        if (empty($user)) {
            return response()->json(['error'=> 'User not found !', 'status' => false  ], 401);
        }
        else {
         if(empty($request->phone)) {
              return response()->json(['error'=> 'Phone number required' , 'status' => false ], 401);
         }
         else {

            if(empty($request->sku)) {
              return response()->json(['error'=> 'Product ID required'  , 'status' => false], 401);
            }  else {

                 $user = DB::table('b2b_users')->where('phone', '=', $request->phone)->select('name', 'org_name', 'email', 'phone', 'address')->get();
                 if( count($user) > 0 ) {
                    $sku = trim($request->sku);

                    $p = DB::table('products')->where('product_id', $sku)->first();
                    // $p = DB::table('products')->where('product_code_inv', $sku)->first();
                    if ($p) {

                        $attr = $this->_get_attribute_sets($p->product_id,  $p->item_type_id);

                        return response()->json(['product_sku' => $sku, 'attr' => $attr, 'detail' => $p->product_long_description, 'status' => true ]);
                    }
                    else {
                        return response()->json(['status' => false, 'msg' => 'Product not found']);
                    }
                 }else {
                     return response()->json(['error'=> 'Phone number is not registered', 'status' => false  ], 401);
                 }
            }
         }
        }
    }

    public function getQueryProducts(Request $request) {


        // SELECT b2b_customer_query_products.order_id, b2b_customer_query.query, COUNT(b2b_customer_query_products.order_id) AS counter
        // FROM `b2b_customer_query_products`
        // INNER JOIN `b2b_customer_query` ON (b2b_customer_query.order_id = b2b_customer_query_products.order_id)
        //  GROUP BY b2b_customer_query_products.order_id ,b2b_customer_query.query

        $user = trim($request->bearerToken());
        Log::info('bearer-token: '. json_encode($user));
        if (empty($user)) {
            return response()->json(['error'=> 'User not found !'  ], 401);
        }
        else
        {
             if (empty($request->phone)) {
                  return response()->json(['error'=> 'Phone number required'  ], 401);
             }
             else {

                $num = 10;
                $page = empty($request->page) || !is_numeric($request->page) ?  1 : $request->page;
                $skip =  ($page == 1) ? -1 : ($num * ($page -1)) + 1;
                $sort = $request->sort;

                $allCount = DB::table('b2b_customer_query')
                                        ->where('b2b_customer_query.phone', trim($request->phone) )
                                        ->whereIn('is_active', ['Done', 'Pending', 'Processing', 'Partialy Done', ''])
                                        ->orderBy('b2b_customer_query.b2b_cust_query_id', 'desc')
                                        ->count();

                $pendingCount = DB::table('b2b_customer_query')
                                        ->where('b2b_customer_query.phone', trim($request->phone) )
                                        ->whereIn('is_active', [ 'Pending' ])
                                        ->orderBy('b2b_customer_query.b2b_cust_query_id', 'desc')
                                        ->count();

                $processingCount = DB::table('b2b_customer_query')
                                        ->where('b2b_customer_query.phone', trim($request->phone) )
                                        ->whereIn('is_active', [ 'Processing'  ])
                                         ->whereNotIn('is_active', ['Done', 'Partialy Done'])
                                        ->orderBy('b2b_customer_query.b2b_cust_query_id', 'desc')
                                        ->count();

                $doneCount = DB::table('b2b_customer_query')
                                        ->where('b2b_customer_query.phone', trim($request->phone) )
                                        ->whereIn('is_active', [ 'Done', 'Partialy Done' ])
                                        ->orderBy('b2b_customer_query.b2b_cust_query_id', 'desc')
                                        ->count();

                  Log::info("mob-trace-sort: ". $sort);

                if (empty($sort) || $sort == 'Processing')  {


                    $queries = DB::table('b2b_customer_query')
                                        ->selectRaw("b2b_customer_query.b2b_cust_query_id as query_id , b2b_customer_query.order_id, b2b_customer_query.phone, b2b_customer_query.is_history,
                                                        b2b_customer_query.query,DATE_FORMAT(b2b_customer_query.created_at, '%d %b %y, %H:%i %p') as time,
                                                        (CASE
                                                            	WHEN (is_active='Pending') THEN 'Query for Price'
                                                                    WHEN (is_active='Processing') THEN 'Price Given'
                                                                    WHEN (is_active='Done') THEN 'Order Done'
                                                                    WHEN (is_active='Partialy Done') THEN 'Partialy Done'
                                                                    WHEN (is_active='') THEN null
                                                                    ELSE ''
                                                                 END) AS new_status")
                                        ->where('b2b_customer_query.phone', trim($request->phone) )
                                        // ->whereIn('b2b_customer_query.is_active', ['Done', 'Pending', 'Processing', 'Partialy Done', ''])
                                        ->whereIn('b2b_customer_query.is_active', [  'Processing', ''])
                                         ->whereNotIn('b2b_customer_query.is_active', ['Done', 'Partialy Done'])
                                        // ->orWhere('b2b_customer_query.is_active', 'Partialy Done')
                                        ->orWhere('b2b_customer_query.is_active', null)
                                        ->orderBy('b2b_customer_query.b2b_cust_query_id', 'desc')
                                        ->skip($skip)
                                        ->take(10)
                                        ->get();
                }
                else if (empty($sort) || $sort == 'Done')  {
                    $queries = DB::table('b2b_customer_query')
                                        ->selectRaw("b2b_customer_query.b2b_cust_query_id as query_id, b2b_customer_query.order_id, b2b_customer_query.phone, b2b_customer_query.query,
                                                        b2b_customer_query.is_history,
                                                        DATE_FORMAT(b2b_customer_query.created_at, '%d %b %y, %H:%i %p') as time,
                                                        (CASE
                                                            	WHEN (is_active='Pending') THEN 'Query for Price'
                                                                    WHEN (is_active='Processing') THEN 'Price Given'
                                                                    WHEN (is_active='Done') THEN 'Order Done'
                                                                    WHEN (is_active='Partialy Done') THEN 'Partialy Done'
                                                                    WHEN (is_active='') THEN null
                                                                    ELSE ''
                                                                 END) AS new_status")
                                        ->where('b2b_customer_query.phone', trim($request->phone) )
                                        ->whereIn('is_active',  ['Done' , 'Partialy Done'])
                                        ->orderBy('b2b_customer_query.b2b_cust_query_id', 'desc')
                                        ->skip($skip)
                                        ->take(10)
                                        ->get();
                }
                else {

                    $queries = DB::table('b2b_customer_query')
                                        ->selectRaw("b2b_customer_query.b2b_cust_query_id as query_id, b2b_customer_query.order_id, b2b_customer_query.phone, b2b_customer_query.query,
                                                    b2b_customer_query.is_history,
                                                        DATE_FORMAT(b2b_customer_query.created_at, '%d %b %y, %H:%i %p') as time,
                                                        (CASE
                                                            	WHEN (is_active='Pending') THEN 'Query for Price'
                                                                    WHEN (is_active='Processing') THEN 'Price Given'
                                                                    WHEN (is_active='Done') THEN 'Order Done'
                                                                    WHEN (is_active='Partialy Done') THEN 'Partialy Done'
                                                                    WHEN (is_active='') THEN null
                                                                    ELSE ''
                                                                 END) AS new_status")
                                        ->where('b2b_customer_query.phone', trim($request->phone) )
                                        ->where ('is_active', $sort )
                                        ->orderBy('b2b_customer_query.b2b_cust_query_id', 'desc')
                                        ->skip($skip)
                                        ->take(10)
                                        ->get();
                }

                $orderStatusLabels = ['Pending'=> 'Query for Price', 'Processing'=> 'Price Given', 'Done' => 'Order Done', 'Partialy Done' => 'Order Done', 'null' => null ];

                if(count($queries) > 0 ) {
                    $result = [];
                    foreach ($queries as $q) {

                        if (empty($sort))  {
                            $order = DB::table('b2b_customer_query')->where('b2b_customer_query.order_id', $q->order_id)->first();
                        }
                        else {
                            if ($sort == 'All') {
                                $order = DB::table('b2b_customer_query')->where('b2b_customer_query.order_id', $q->order_id)->whereIn('is_active', ['Pending', 'Processing', 'Done', 'Partialy Done', ''])->first();
                            } else {
                                $order = DB::table('b2b_customer_query')->where('b2b_customer_query.order_id', $q->order_id)->where('is_active', $sort)->first();
                            }
                        }

                        // if ( isset($order->is_active) ) {

                            $totalQty = DB::table('b2b_customer_query_products')
                                                ->selectRaw("count(b2b_customer_query_products.order_id) as qty")
                                                ->where('order_id', '=', $q->order_id)
                                                ->count();

                            $today = Carbon::now()->format('Y-m-d');

                            $products = DB::table('b2b_customer_query_products')
                                                ->selectRaw("b2b_cust_query_product_id,  product_sku, product_name,
                                                qty, reg_price, sug_price , is_active as status,
                                                                (CASE
                                                            	    WHEN (is_active='Pending') THEN 'Query for Price'
                                                                    WHEN (is_active='Processing'  ) THEN 'Price Given'

                                                                    WHEN (is_active='Done') THEN 'Order Done'
                                                                    WHEN (is_active='Partialy Done') THEN 'Partialy Done'
                                                                    WHEN (is_active='') THEN null
                                                                    ELSE ''
                                                                 END) AS new_status,
                                                                 (CASE
                                                                    WHEN  (DATEDIFF(DATE_FORMAT(b2b_exp_date, '%Y-%m-%d') , curdate() ) < 0)  THEN 'Expired'
                                                                    ELSE ''
                                                                    END
                                                                  ) AS b2b_exp_status, b2b_qty, b2b_req_price as b2b_given_price, b2b_exp_date")
                                                ->where('order_id', '=', $q->order_id)
                                                ->get();

                            $productTotal = DB::table('b2b_customer_query_products')
                                                ->selectRaw("sum(sug_price) as total")
                                                ->where('order_id', '=', $q->order_id)
                                                ->first();

                            $ord = DB::table('b2b_customer_query')->where('b2b_customer_query.order_id', $q->order_id)->first();


                            $totalNumOfItem = DB::table('b2b_customer_query_products')
                                                        ->where('order_id', '=', $q->order_id)
                                                        ->count();

                            $notAvailableCount = DB::table('b2b_customer_query_products')
                                                        ->where('order_id', '=', $q->order_id)
                                                        // ->where('is_active', 'Processing')
                                                        ->where('b2b_qty', 0)
                                                        ->count();

                            if ($totalNumOfItem == $notAvailableCount) {
                                $orderStatusFlag = "Price Given";
                                 $orderTagStatusFlag = "Not Available";
                            } else {
                                if (isset($orderStatusLabels[$ord->is_active]) ) {
                                    $orderStatusFlag = $orderStatusLabels[$ord->is_active] ;
                                     $orderTagStatusFlag = "";
                                } else {
                                    $orderStatusFlag = null;
                                    $orderTagStatusFlag = "";
                                }
                            }


                            $result[] = [
                                            'time' => $q->time ,
                                            'qty' => $totalQty ,
                                            'query' => $q->query ,
                              				'query_id' => $q->query_id ,

                                            'order_status' => isset($ord->is_active) ? $ord->is_active : '0',
                                            'is_hide' => isset($q->is_history) ? $q->is_history: 0 ,

                                            // 'order_new_status' => isset($orderStatusLabels[$ord->is_active]) ? $orderStatusLabels[$ord->is_active] : null  ,

                                            'order_new_status' => $orderStatusFlag ,
                                            'order_tag_status' => $orderTagStatusFlag ,

                                            'order_id' => $q->order_id,
                                            'phone' => $q->phone,

                                            'item' => $products,
                              				'hhh' => 'Check API',
                                            'total' => isset($productTotal->total) ? $productTotal->total : 0
                                ];
                        // }
                    }

                    return response()->json([ 'data'=> $result ,  'counting' => ['allCount' => $allCount, 'pendingCount' => $pendingCount, 'processingCount'=> $processingCount, 'doneCount'=> $doneCount]   ,  'success'=> true]);
                }
                else {
                  return response()->json([ 'msg'=> 'No records found', 'data' => [], 'counting' => [], 'success'=> true]);
                }
             }
        }
    }

    public function getQueryProductsB2BPrices(Request $request)
    {
        $user = trim($request->bearerToken());
        Log::info('bearer-token: '. json_encode($user));

        if (empty($user)) {
            return response()->json([
                                        'error'=> 'User not found !'
                                    ], 401);
        }
        else {

            if(empty($request->orderId)) {
                  return response()->json([
                                            'error'=> 'Order ID required'
                                        ], 401);
            }
            else
            {
                $queries = DB::table('b2b_customer_query_products')

                                    ->selectRaw("b2b_customer_query_products.order_id, b2b_customer_query.b2b_cust_query_id as query_id,
                                                  b2b_customer_query.phone,
                                                    b2b_customer_query.query, COUNT(b2b_customer_query_products.order_id) AS qty ,
                                                    DATE_FORMAT(b2b_customer_query.created_at, '%d %b %y, %H:%i %p') as time")

                                    ->join('b2b_customer_query', 'b2b_customer_query.order_id' , '=', 'b2b_customer_query_products.order_id')
                                    ->where("b2b_customer_query_products.order_id", $request->orderId)
                                    // ->where("b2b_customer_query.is_active", "!=", "Done")
                                    ->groupBy('b2b_customer_query_products.order_id','b2b_customer_query.phone'
                                              ,'b2b_customer_query.query',
                                              'b2b_customer_query.created_at',
                                             'b2b_customer_query.b2b_cust_query_id')
                                    ->get();

                if (count($queries) > 0 )
                {
                    $result = [];
                    foreach($queries as $q)
                    {
                        $order = DB::table('b2b_customer_query')
                                            ->where('b2b_customer_query.order_id', $q->order_id)
                                            ->first();

                        $products = DB::table('b2b_customer_query_products')
                                            ->selectRaw("b2b_cust_query_product_id,  product_sku, product_name, qty,
                                            order_qty as newQty, reg_price, sug_price, b2b_req_price,
                                            (b2b_qty-order_qty) as availQty,
                                                            b2b_qty,
                                                            b2b_exp_date, is_active as status,
                                                             (CASE
                                                            	WHEN (is_active='Pending') THEN 'Query for Price'
                                                                    WHEN (is_active='Processing') THEN 'Price Given'

                                                                    WHEN (is_active='Done') THEN 'Order Done'
                                                                    WHEN (is_active='Partialy Done') THEN 'Partialy Done'
                                                                    ELSE ''
                                                                 END) AS new_status,
                                                                 (CASE
                                                                    WHEN  (DATEDIFF(DATE_FORMAT(b2b_exp_date, '%Y-%m-%d') , curdate() ) < 0)  THEN 'Expired'
                                                                    ELSE ''
                                                                    END
                                                                  ) AS b2b_exp_status ,
                                                                  IF(b2b_cust_query_product_id != 0 , 'false', 'false') as isCheck,
                                                                  b2b_req_price as b2b_given_price
                                                                  ")
                                            ->where('order_id', '=', $q->order_id)
                                            // ->where('is_active', '!=', 'Done')

                                             ->where('b2b_req_price', '!=', null)
                                            // ->where('b2b_qty', '!=', '')
                                            // ->where('b2b_exp_date', '!=', '')

                                            ->get();

                         $productTotal = DB::table('b2b_customer_query_products')
                                                        ->selectRaw("sum(sug_price) as total")
                                                        ->where('order_id', '=', $q->order_id)
                                                        ->first();



                        // not available check
                        // is_active='Processing' AND b2b_req_price=0


                        $totalNumOfItem = DB::table('b2b_customer_query_products')
                                                        ->where('order_id', '=', $q->order_id)
                                                        ->count();

                        $notAvailableCount = DB::table('b2b_customer_query_products')
                                                        ->where('order_id', '=', $q->order_id)
                                                        // ->where('is_active', 'Processing')
                                                        ->where('b2b_qty', 0)
                                                        ->count();



                        if ($totalNumOfItem == $notAvailableCount) {
                            $orderStatusFlag = "Price Given";
                            $orderTagStatusFlag = "Not Available";
                            $orderStatusTotal = 0;
                        } else {
                            if (isset($order->new_status) ) {
                                $orderStatusFlag = $order->new_status ;
                                $orderStatusTotal = isset($productTotal->total) ? $productTotal->total : 0;
                                $orderTagStatusFlag = "";
                            } else {
                                $orderStatusFlag = null;
                                $orderStatusTotal = isset($productTotal->total) ? $productTotal->total : 0;
                                $orderTagStatusFlag = "";
                            }
                        }


                        $result[] = [
                                            'time'          => $q->time ,
                                            // 'qty'           => $q->qty ,
                                            // 'newQty'        => $q->order_qty,

                                            'query'         => $q->query ,
                          					'query_id'      => $q->query_id,
                                            'order_status'  => isset($order->is_active) ? $order->is_active : null,


                                             'order_new_status' => $orderStatusFlag,

                                            // 'order_new_status' => isset($order->new_status) ? $order->new_status : null,

                                            'order_tag_status' => $orderTagStatusFlag,

                                            'order_id'      => $q->order_id,
                                            'phone'         => $q->phone,
                                            'item'          => $products,
                                            'total'         => $orderStatusTotal

                                            // 'total'         => isset($productTotal->total) ? $productTotal->total : 0
                                    ];
                    }

                    return response()->json([
                                                'data'=> array_reverse($result)   ,
                                                'success'=> true
                                            ]);
                }
                else
                {
                    return response()->json([
                                                'msg'=> 'No records found',
                                                'success'=> false
                                            ]);
                }
            }
        }
    }



    // =============== my ordered list ========================
    public function getOrderedProductList(Request $request)
    {
        $user =  trim($request->bearerToken());
        if (empty($user)) {
            return response()->json(['error'=> 'User not found !'  ], 401);
        }
        else if ($request->orderId == "" ) {
            return response()->json(['error'=> 'Order ID required !'  ], 401);
        }
        else if (empty($request->phone)) {
            return response()->json(['error'=> 'Phone required !'  ], 401);
        }
        else {
            $o = DB::table('b2b_customer_query')->where('order_id', $request->orderId)->first();
            if ($o) {
                    $productsList = DB::table('b2b_customer_query_products')
                                                ->selectRaw("b2b_cust_query_product_id, order_id, phone, product_sku, product_name,
                                                                item_name, brand_name, qty, order_qty as  newQty, reg_price, sug_price, b2b_req_price,
                                                                b2b_exp_date, is_active,
                                                                (CASE
                                                            	WHEN (is_active='Pending') THEN 'Query for Price'
                                                                    WHEN (is_active='Processing') THEN 'Price Given'
                                                                    WHEN (is_active='Done') THEN 'Order Done'
                                                                    WHEN (is_active='Partially Done') THEN 'Partially Done'
                                                                    WHEN (is_active=' ') THEN ''
                                                                    ELSE ''
                                                                 END) AS new_status,
                                                                updated_by, created_at, updated_at")
                                                ->where('order_id', $request->orderId)
                                                ->whereIn('is_active', ['Done', 'Partialy Done'])
                                                ->get();
                    return response()->json([
                                        'order_id' => $o->order_id ,
                                        'products' => $productsList,
                                        'total' => $o->total,
                                        'success'=> true
                            ], 200);
            }
            else {
                return response()->json([
                        'msg' => 'Order not found',
                        'success'=> false
                    ], 200);
            }
        }
    }

    protected function importInvDataToRCOM($brand_name, $p, $b, $i, $productItem)
    {
        try {
            if ($i) {

                $pp = DB::table("b2b_products")->where('inv_code' , $p['product_id'])->first();
                if ($pp) {
                    $item = [
                                'inv_code'       => $p['product_id'],
                            	'product_name'   => $p['item_name'],
                            	'item_type_id'   => isset($productItem->item_type_id) ? $productItem->item_type_id : null,
                            	'item_type_name' => isset($i->item_type_name) ? $i->item_type_name : null,
                    	        'brand_id'       => $b->brand_id,
                    	        'brand_name'     => $p['brand_name'] ,
                    	        'reg_price'      => $p['reg_price'],
                    	        'sug_price'      => $p['sug_price'],
                    	        'qty'            => $p['qty'],
                    	        'updated_at'     => Carbon::now()
                            ];
                        DB::table("b2b_products")
                                ->where('inv_code' , $p['product_id'])
                                ->update($item);
                }
                else {
                    $item = [
                                'inv_code'          => $p['product_id'],
                            	'product_name'      => $p['item_name'],
                            	'item_type_id'      => isset($productItem->item_type_id) ? $productItem->item_type_id : null,
                            	'item_type_name'    => isset($i->item_type_name) ? $i->item_type_name : null,
                    	        'brand_id'          => $b->brand_id,
                    	        'brand_name'        => $p['brand_name'] ,
                    	        'reg_price'         => $p['reg_price'],
                    	        'sug_price'         => $p['sug_price'],
                    	        'qty'               => $p['qty'],
                    	        'created_at'        => Carbon::now()
                            ];
                        DB::table("b2b_products")
                                ->insert($item);
                }
            }
            else {

                 $ii = DB::table('item_types')->where('item_type_name', $p['sub_type_name'])->first();

                 $pp = DB::table("b2b_products")->where('inv_code' , $p['product_id'])->first();
                 if ($pp) {
                    $item = [
                        	'product_name'   => $p['item_name'],
                        	'item_type_id'   => isset($productItem->item_type_id) ? $productItem->item_type_id : null,
                        	'item_type_name' => isset($i->item_type_name) ? $i->item_type_name : null,
                	        'brand_id'       => $b->brand_id,
                	        'brand_name'     => $p['brand_name'] ,
                	        'reg_price'      => $p['reg_price'],
                	        'sug_price'      => $p['sug_price'],
                	        'qty'            => $p['qty'],
                	        'created_at'     => Carbon::now()
                        ];
                        DB::table("b2b_products")->where('inv_code' , $p['product_id'])->update($item);
                 }
                 else {
                      $item = [
                            'inv_code'       => $p['product_id'],
                        	'product_name'   => $p['item_name'],
                        	'item_type_id'   => isset($productItem->item_type_id) ? $productItem->item_type_id : null,
                        	'item_type_name' => isset($i->item_type_name) ? $i->item_type_name : null,
                	        'brand_id'       => $b->brand_id,
                	        'brand_name'     => $p['brand_name'] ,
                	        'reg_price'      => $p['reg_price'],
                	        'sug_price'      => $p['sug_price'],
                	        'qty'            => $p['qty'],
                	        'created_at'     => Carbon::now()
                        ];
                        DB::table("b2b_products")->insert($item);
                 }
            }
        } catch(\Exception $e) {
            echo $e->getMessage();
        }
    }



    protected function getLayerOne($brand_name, $s) {
        $res = [];
        foreach($s['product'] as $p) {

            $b = DB::table('brands')->where('brand_name', $p['brand_name'])->first();

            $productItem = DB::table('products')->where('product_code_inv', $p['product_id'])->first();
            $i = DB::table('item_types')->where('item_type_id', $productItem->item_type_id)->first();
            $this->importInvDataToRCOM($brand_name, $p, $b, $i, $productItem);

            $image = DB::table('product_images')
                                                ->select(['product_image_name'])
                                                ->join('products', 'product_images.product_id', '=', 'products.product_id')
                                                ->where('products.product_code_inv', $p['product_id'])
                                                ->get();

            $t = array_merge($p, [
                                    'img_base' => isset($image[0]->product_image_name) ? "https://www.ryans.com/storage/products/small/" : "https://www.ryans.com/logo/",
                                    'image' => isset($image[0]->product_image_name) ?   $image[0]->product_image_name : 'Ryans.png'
                                ]);
            $brn[] = $t;
        }
        return $brn;
    }

    public function getProductHistory(Request $request) {
        $user =  trim($request->bearerToken());
        if (empty($user)) {
            return response()->json(['error'=> 'User not found !'  ], 401);
        }else{
            $validator = Validator::make($request->all(), ['phone' => 'required']);
            if( empty($request->phone)) {
                 return response()->json(['error'=> 'Phone number required'  ], 401);
            } else {
                $num = 10;
                $page = empty($request->page) || !is_numeric($request->page) ? $request->page : 1;
                $skip = $num*$page;

                if(!empty($request->page)) {

                    $products = DB::table('b2b_customer_query_products')
                                ->select('b2b_customer_query_products.order_id',
                                            'b2b_customer_query_products.product_sku',
                                            'b2b_customer_query_products.product_name as product',
                                            'b2b_customer_query_products.item_name as item',
                                            'b2b_customer_query_products.brand_name as brand',
                                            'b2b_customer_query_products.sug_price',
                                            'b2b_customer_query_products.reg_price',
                                            DB::raw("DATE_FORMAT(b2b_customer_query_products.created_at, '%d %b %y, %H:%i %p') as  created_at"))
                                ->join('b2b_customer_query', 'b2b_customer_query.order_id', '=', 'b2b_customer_query_products.order_id')
                                ->where('b2b_customer_query_products.phone', '=', trim($request->phone) )
                                ->orderBy('b2b_customer_query_products.order_id', 'desc')
                                ->skip($skip)
                                ->take(10)
                                ->get();
                }
                else {
                    $products = DB::table('b2b_customer_query_products')
                                ->select('b2b_customer_query_products.order_id',
                                        'b2b_customer_query_products.product_sku',
                                        'b2b_customer_query_products.product_name as product',
                                        'b2b_customer_query_products.item_name as item',
                                        'b2b_customer_query_products.brand_name as brand',
                                        'b2b_customer_query_products.sug_price',
                                        'b2b_customer_query_products.reg_price',
                                        DB::raw("DATE_FORMAT(b2b_customer_query_products.created_at, '%d %b %y, %H:%i %p') as  created_at"))
                            ->join('b2b_customer_query', 'b2b_customer_query.order_id', '=', 'b2b_customer_query_products.order_id')
                            ->where('b2b_customer_query_products.phone', '=', trim($request->phone) )
                            ->orderBy('b2b_customer_query_products.order_id', 'desc')
                            // ->skip($skip)
                            // ->take(10)
                            ->get();
                }
                return response()->json($products);
            }
        }
    }

    public function customerQuery(Request $request) {
        Log::info('customerQuery: Request: '. json_encode($request->all()));
        // exit();

        $user =  trim($request->bearerToken());
        if (!$user) {
            return response()->json(['error'=> 'User not found !'  ], 401);
        } else {


            $validator = Validator::make($request->all(), ['phone' => 'required']);
            if ( empty($request->phone) ) {
                 return response()->json(['error'=> 'Phone number required'  ], 401);
            } else {
                // Log::info('customerQuery::Request data:'. json_encode($request->all()));

                // if ($request->phone == "8801755554910") {

                    if (isset($request->data['products'])) {

                        $countOrders = DB::table('b2b_customer_query')->count();
                        $countOrdersObj = DB::table('b2b_customer_query')->orderBy('created_at', 'desc')->first();


                        if ($countOrders == 0) {
                            $dt = DB::table('b2b_order_seq')->orderBy('created_at', 'asc')->first();
                            $orderID = $dt->id;
                        }  else {
                            $dt = DB::table('b2b_customer_query')->orderBy('created_at', 'desc')->first();
                            $dtt = DB::table('b2b_order_seq')->where('id', '>', $dt->order_id)->first();
                            $orderID = $dtt->id;
                        }

                        $lastQuery = DB::table('b2b_customer_query')
                                        ->selectRaw('REGEXP_SUBSTR(order_id,"[0-9]+") as query_id')
                                        ->orderBy('created_at', 'desc')->first();
                        $lastQueryID = null;
                        if ($lastQuery ) {
                            $lastQueryID = $lastQuery->query_id + 1;
                        }


                        $all_data = $request->data['products'];
                        $total = $request->data['total'];

                        $orderId = $orderID  ;
                        $phone = $request->phone;
                        $msg = $request->msg;
                        $pid = null;



                        // new style
                        $lastCustQueryObj = DB::table('b2b_customer_query')
                                                ->selectRaw('(count(b2b_cust_query_id)+1) as  q_id')
                                                ->orderBy('b2b_cust_query_id', 'desc')->take(1)->first();
                        $withour_prefix_order_id = $lastCustQueryObj->q_id  ;

                        $lastCustQueryObjj = DB::table('b2b_customer_query')
                                                ->selectRaw('b2b_cust_query_id')
                                                ->orderBy('b2b_cust_query_id', 'desc')->take(1)->first();
                        if ($lastCustQueryObjj) {
                            $existing_order_id = $lastCustQueryObjj->b2b_cust_query_id;
                        } else {
                            $existing_order_id = 1;
                        }



                        Log::info('all_data:: '. json_encode($all_data));

                            $totalAmount = 0;
                            foreach ($all_data as $nkey => $a) {

                                $product_name = isset($a['item_name']) ? $a['item_name'] : null;
                                $item_name    = isset($a['item']) ? $a['item'] : null;
                                $brand_name   = isset($a['brand_name']) ? $a['brand_name'] : '';
                                $product_sku  = $a['product_id'];
                                $reg_price    = $a['reg_price'];
                                $sug_price    = $a['sug_price'];
                                $qty          = $a['qty'];

                                $isProduct = DB::table('products')->where('product_id', $a['product_id'])->first();
                                $isBrand = DB::table('brands')->where('brand_id', $isProduct->brand_id)->first();

                                if ($a['check'] == true) {
                                    $querySeriesID =   ($nkey + 1) ;
                                    $NOID = $withour_prefix_order_id ;

                                    //============== customer query and order relations  (temporary) ===============

                                    try {
                                        $is_exist_count =  DB::table("b2b_order_query_relations")
                                                                ->where("order_id", 'BO'. $NOID)
                                                                ->where("query_id", 'BQ'. $querySeriesID)
                                                                ->count();

                                        if ($is_exist_count == 0) {
                                            DB::table("b2b_order_query_relations")
                                            ->insert([
                                                        "order_id" => 'BO'. $NOID,
                                                        "query_id" => 'BQ'. $querySeriesID
                                                    ]);
                                        }
                                    } catch(\Exception $e) {
                                        Log::info("b2b_order_query_relations :: error:: ". $e->getMessage() );
                                    }

                                    //============== customer query and order relations  ===============


                                    $p = DB::table('b2b_customer_query_products')->insert([
                                            'phone'        => $phone,
                                            'order_id'     => 'BO'. $NOID ,
                                            'new_order_id' => 'BO'. $NOID ,
                                            'query_id'     => 'BQ'. $querySeriesID,
                                            'product_sku'  => isset($isProduct) ? $isProduct->product_code_inv : null,
                                            'product_name' => $product_name,
                                            'item_name'    => $item_name,
                                            'brand_name'   => isset($isBrand) ? $isBrand->brand_name : null,
                                            'reg_price'    => $reg_price,
                                            'sug_price'    => $sug_price,
                                            'qty'          => $qty,
                                            'is_active'    => 'Pending',
                                            'created_at'   => Carbon::now('GMT+6')
                                        ]);

                                    $totalAmount += ($sug_price *  1);
                                    $pid = DB::getPdo()->lastInsertId();
                                }

                            //  echo  $product_name . " ". $product_sku . " ". $reg_price. " ".$sug_price." ". $qty . '<br/>';
                            }


                            if ($pid ) {
                                $orderId = 'BO'. $NOID;
                                try {
                                    $loc = DB::table("b2b_users")->where("phone", $phone)->first();
                                    if ($loc) {
                                        $location_id = $loc->location_id;
                                    } else {
                                        $location_id = 0;
                                    }
                                }
                                catch(\Exception $e) {}

                                try {
                                    DB::table('b2b_customer_query')
                                        ->insert([
                                            'phone'         => $phone,
                                            'location_id'   => $location_id ,
                                            'order_id'      => 'BO'. $NOID,
                                            'query_id'      => 'BQ'. mt_rand(1,5),
                                            'new_order_id'  => 'BO'. $NOID,
                                            'total'         => $totalAmount,
                                            'query'         => $msg,
                                            'is_active'     => 'Pending',
                                            'created_at'    => Carbon::now('GMT+6')  ,
                                            'updated_at'    => Carbon::now('GMT+6') ,
                                            'sorting_date'    => Carbon::now('GMT+6')
                                   ]);

                                    $last_customer_queryID = DB::getPdo()->lastInsertId();
                                    Log::info("b2b_orders >> last_queryID:: ". $last_customer_queryID);


                                    $lastQueryObject = DB::table('b2b_customer_query')
                                                        ->selectRaw('b2b_cust_query_id, query_id')
                                                        ->where('b2b_cust_query_id',   $last_customer_queryID)
                                                        ->first();

                                    if (isset($last_customer_queryID)) {
                                        $queryID = isset($lastQueryObject->b2b_cust_query_id) ? $lastQueryObject->b2b_cust_query_id : null;

                                        DB::table('b2b_customer_query_products')
                                                ->where('phone' ,  $request->phone )
                                                ->where('order_id' ,  $orderId )
                                                ->update([
                                                        'customer_query_id' => $queryID,
                                                        'query_id' =>  $lastQueryObject->query_id
                                                ]);
                                        DB::table('b2b_customer_query')
                                        ->where('b2b_cust_query_id', $last_customer_queryID)
                                        ->update(['sorting_date' => Carbon::now('GMT+6') ]);
                                    }

                                }
                                catch(\Exception $e) {
                                    Log::info("b2b_customer_query::error:: ". $e->getMessage());
                                }


                            }

                            //echo 'Total: '. $totalAmount .'<br/>';



                            //============= query notification ==============================
                            try {
                                $template = "query";
                                $body_text = "B2B App New query: ". $orderId ." has been received. Please review the request.";
                                Log::info($body_text);
                                $subject = "B2B App Query#". $orderId ." received";
                                Common::sendMail(Common::notificationHolderEmail() , null, $body_text,  $subject, Common::notificationHolderName(), $template );
                            } catch(\Exception $e) {
                                Log::info("Query-Error:: ". $e->getMessage());
                            }
                            // ============= query notification ==============================



                            Log::info('customerQuery::OrderId:'.  $orderId );
                            Log::info('customerQuery::New-OrderId:'.                    $withour_prefix_order_id );

                            Log::info('customerQuery::Phone:'.  $phone );
                            Log::info('customerQuery::msg:'.  $msg );
                            Log::info('customerQuery::Total:'.  $total );
                            Log::info('customerQuery::Request:'. json_encode($request->all()));

                            return response()->json(['msg'=> 'Request saved successfully', 'success' => true  ], 200);
                        // }


                    } else {
                        return response()->json(['error'=> 'Product not found' , 'status'=> false  ], 401);
                    }
                }

            // }
        }
    }

    //=== get all b2b data to inventory
    public function getInvB2BAllData(Request $request) {
        $user =  trim($request->bearerToken());
        $validator = Validator::make($request->all(), ['phone' => 'required']);

        if (!$user) {
            return response()->json(['error'=> 'User not found !'  ], 401);
        }
        else if( empty($request->phone) ) {
                return response()->json(['error'=> 'Phone number required'  ], 401);
        }
        else{
                try {
                    $url = 'http://115.127.98.139/api/b2b_app/b2b_items/';
                    $ch = curl_init();
                    curl_setopt($ch, CURLOPT_URL, $url);
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");

                    $records = curl_exec($ch);
                    if (curl_errno($ch)) {
                        echo 'Error:' . curl_error($ch);
                    }
                    curl_close ($ch);

                    $records_data = json_decode($records, true);
                    $dataProducts = $records_data['data'];


                    $obj = [];
                    foreach($dataProducts as $key => $val) {
                        $sub = [];
                        foreach($val['sub_type'] as $sb) {
                            $sub_type_name = $sb['name'];
                            $product = [];
                            foreach($sb['brand'] as $s) {
                                $brand_name = $s['name'];
                                $pro = $this->getLayerOne($brand_name, $s);
                                $product[] = ['name'=> $brand_name, 'product' => $pro ];

                            }
                            $sub[] = ['name' => $sub_type_name, 'brand'=> $product  ];
                        }
                        $obj[] = ['id' =>$val['id'] , 'item' => $val['item'], 'sub_type' => $sub ];
                    }



                    return  json_encode([ 'status' => true , 'data'=> $obj  ]);

                }
                catch(\Exception $e) {
                    Log::info('Error getInvB2BAllData: '. $e->getMessage() );
                    return response()->json([
                            'status'=> 500,
                            'e' => $e->getMessage(),
                            'msg'=> 'Inventory data failed to load.'
                    ]);
                }
        }
    }

    public function getInvB2BDataOnPage(Request $request) {

       $validator = Validator::make($request->all(), ['phone' => 'required']);
       $user =  trim($request->bearerToken());
       if (!$user) {
            return response()->json(['error'=> 'User not found !' , 'success' => false  ], 401);
       } else if( empty($request->phone) ) {
            return response()->json(['error'=> 'Phone number required' , 'success' => false  ], 401);
       }
       else {

            if(is_numeric($request->page)) {
                $page = $request->page;
            } else {
                $page = 1;
            }
            if(is_numeric($request->count)) {
                $count = $request->count;
            } else {
                $count = 10;
            }

            Log::info('getInvB2BDataOnPage: Token: '. $user);
            Log::info('getInvB2BDataOnPage: '. json_encode($request->all()));
            $url = empty($request->count) ? config('constants.generate_inv_b2b_path') : config('constants.generate_inv_b2b_path') . "?count=". $count ."&page=". $page;

            try {
                // $url =  config('constants.generate_inv_b2b_path') . "?count=". $count ."&page=". $page;

                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");

                // $headers = array();
                // $headers[] = "count: ". $count;
                // curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

                $response = curl_exec($ch);
                if (curl_errno($ch)) {
                    echo 'Error:' . curl_error($ch);
                }

                curl_close ($ch);


                $imagesList= [];
                $content = json_decode($response, true);

                foreach($content['data'] as $key => $cont) {
                    foreach($cont['sub_type'] as $ct) {
                        foreach($ct['brand'] as $b) {
                            foreach($b['product'] as $p) {

                                $image = DB::table('product_images')
                                            ->select(['product_image_name'])
                                            ->join('products', 'product_images.product_id', '=', 'products.product_id')
                                            ->where('products.product_code_inv', $p['product_id'])
                                            ->get();

                                $imagesList[$p['product_id']] =  [ isset($image[0]->product_image_name) ? "https://www.ryans.com/storage/products/small/". $image[0]->product_image_name : '' ];

                                // echo $p['product_id'] . "<br/>";
                                // print_r( "https://www.cloud.ryanscomputers.com/cdn/products/small/". $image[0]->product_image_name );
                                // echo "<br/>";
                                // echo "<br/>======================================<br/>";
                            }
                        }
                    }
                }

                // return  $response ;
                return  json_encode([ 'status' => true , 'data' => $content['data'], 'images' => [$imagesList]   ]);
            }
            catch(\Exception $e) {
                Log::info('Error getInvB2BDataOnPage: '. $e->getMessage() );
                return  json_encode([ 'status' => true , 'data' => $content['data'], 'images' => [$imagesList]   ]);
            }
        }

    }

    //== password update
    public function changePassword(Request $request) {

       $validator = Validator::make($request->all(), [
                           'phone'        => 'required',
                           'password'     => 'required',
                           'old_password' => 'required'
                        ]);

       $user =  trim($request->bearerToken());

       if (!$user) {
            return response()->json(['error'=> 'User not found !' , 'success' => false ], 401);
       } else if( empty($request->phone) ) {
            return response()->json(['error'=> 'Phone number required' , 'success' => false ], 401);
       }
       else if( empty($request->password) ) {
            return response()->json(['error'=> 'Password required' , 'success' => false ], 401);
       }

       else if( strlen($request->password)  < 6 ) {
            return response()->json(['error'=> 'Password minimum 6 characters' , 'success' => false ], 401);
       }

       else if( empty($request->old_password) ) {
            return response()->json(['error'=> 'Old Password required' , 'success' => false ], 401);
       }

       else {
            Log::info('changePassword-Password: '.  $request->old_password);
            Log::info('changePassword: '. json_encode($request->all()));
            $userInfo = DB::table('b2b_users')->where('phone', '=', $request->phone)->select(['b2b_user_id', 'name', 'org_name', 'password', 'email', 'phone', 'address'])->first();
            Log::info('user-object: '.  json_encode($userInfo));
            if($userInfo) {
                Log::info('changePassword-Password: '.  $userInfo->password);
                $is = Hash::check($request->old_password , $userInfo->password);
                Log::info('changePassword-Password-check: '.  $is);
                if($is) {
                    if(strlen($request->password) < 6 ) {
                        return response()->json(['error'=> 'Password minimum 6 character required' , 'success' => false ], 401);
                    } else {
                        $new_password = Hash::make($request->password);
                         DB::table('b2b_users')->where('b2b_user_id', $userInfo->b2b_user_id )
                         ->update(['password' => $new_password]);
                        return response()->json(['msg'=> 'password changed successfully', 'success' => true]);
                    }
                } else {
                    return response()->json(['error'=> 'Old Password not matched', 'success' => false], 401);
                }
            } else {
                return response()->json(['error'=> 'User not registered', 'success' => false], 401);
            }
       }
    }

    public function saveFinalOrder(Request $request) {

        Log::info("saveFinalOrder::request:: ". json_encode($request->all()) );

        try {
            $user =  trim($request->bearerToken());
            if (!$user) {
                return response()->json(['error'=> 'User not found !', 'success'=>false  ], 401);
            }
            else if( empty($request->phone) ) {
                return response()->json(['error' => 'Phone number required', 'success'=> false  ], 401);
            }
            else if( empty($request->orderId) ) {
                return response()->json(['error' => 'Order ID required', 'success'=> false  ], 401);
            }
            else {

                $products = $request->products;
                $o = DB::table('b2b_customer_query')->where('order_id', $request->orderId)->first();

                if ($o == false) {
                    return response()->json(['error' => 'Invalid Order ID', 'success'=> false  ], 401);
                }
                else if (count($products) == 0) {
                    return response()->json(['error' => 'Products not found', 'success'=> false  ], 401);
                }
                else if ($request->total == "") {
                    return response()->json(['error' => 'Total not found', 'success'=> false  ], 401);
                }
                else {

                    $isSaved = false;
                    $temp = [];

                    foreach ($products as $p) {
                        $temp[] = $p['Check'];
                        $indProd = DB::table('b2b_customer_query_products')->where('b2b_cust_query_product_id', $p['b2b_cust_query_product_id'])->first();


                        $totalProduct = DB::table('b2b_customer_query_products')->where('order_id', $request->orderId)->count();

                        $qtyFlag = false;





                        if ($indProd) {
                            if ( isset($indProd->b2b_exp_date) && (Carbon::now() <= Carbon::createFromFormat("Y-m-d H:i:s", $p['b2b_exp_date'] ))  ) {

                                // if ($p->isCheck == true) {
                                if ($p['Check'] == true) {

                                    if ($indProd->b2b_qty >0) {

                                        DB::table('b2b_customer_query_products')
                                        ->where('b2b_cust_query_product_id', $p['b2b_cust_query_product_id'])
                                        ->update([
                                            'is_active' => 'Done',
                                            'order_qty' => isset($p['newQty']) ? $p['newQty'] : 0,
                                            'order_date' => Carbon::now()
                                        ]);

                                        $isSaved = true;
                                    } else {
                                        $qtyFlag = true;
                                    }
                                }

                            }
                        }
                    }


                    if ($qtyFlag == true) {
                        return response()->json(['msg'=> 'Order not available', 'success'=> false], 401);
                    } else {

                        if ($isSaved) {

                            $totalDoneProduct = DB::table('b2b_customer_query_products')->where('is_active', 'Done')->where('order_id', $request->orderId)->count();

                            if ($totalProduct == $totalDoneProduct) {
                                $isActive = 'Done';
                            } else {
                                $isActive = 'Partialy Done';
                            }


                            // SELECT sum(b2b_qty * b2b_req_price)  FROM `b2b_customer_query_products`

                            $tt = DB::table('b2b_customer_query_products')
                                            ->where('order_id', $request->orderId)
                                            ->selectRaw("sum(b2b_qty * b2b_req_price) as tot")->first();

                            if ($tt ) {
                              $tot = $tt->tot ;
                            } else {
                              $tot = 0 ;
                            }

                            DB::table('b2b_customer_query')
                                ->where('order_id', $request->orderId)
                                ->update([
                                            // 'b2b_total' => $request->total,
                                            'b2b_total' => $tot,
                                            'is_active' => $isActive,

                                             'updated_at' => Carbon::now()
                                    ]);


                             //============= order notification ======================================================================================
                                try {
                                    $template = "order";
                                    $body_text = "New order: ". $request->orderId  ." has been submitted.";
                                    $subject = "B2B App New Order#". $request->orderId ." received";
                                    Log::info($body_text);
                                    Common::sendMail(Common::notificationHolderEmail() , null, $body_text,  $subject, Common::notificationHolderName(), $template );
                                } catch(\Exception $e) {
                                    Log::info($e->getMessage());
                                }
                            // ============= order notification ======================================================================================


                            //  extra parameter (products)
                            return response()->json([
                                                        'msg'=> 'Order Saved',
                                                        'products' => $products,
                                                        'success'=> true
                                                    ], 200);
                        }
                        else {
                            return response()->json(['msg'=> 'Order date expired', 'success'=> false], 401);
                        }

                    }

                }
            }
        } catch(\Exception $e) {
            return response()->json(['error'=> 'Erorr: '. $e->getMessage() , 'success'=> false  ], 401);
        }
    }





    //==== search api
    public function globalSearch(Request $request) {
       $user =  trim($request->bearerToken());
       if (!$user) {
            return response()->json(['error'=> 'User not found !', 'success'=>false  ], 401);
       }else if( empty($request->phone) ) {
            return response()->json(['error'=> 'Phone number required', 'success'=> false  ], 401);
       }
       else if( empty($request->keyword) ) {
            return response()->json(['error'=> 'Search Keyword required', 'success'=> false  ], 401);
       }
       else {

           $keyword = trim($request->keyword);
           $keyword = strtolower($keyword);
           $count = 30;

           $page = trim($request->page);
           $page = empty($page) ? 1: $page;

           $limit = $count ;
           $offset = (($page-1) * $count);


            $words = explode(" ", $keyword);
            $item_types = [];
            $search_list = ["all in one pc", "usb converter", "hdmi converter", "dvi converter", "usb hub", "led strip", "gaming chair", "power supply", "ryans pc", "gaming console", "tv card" ];
            foreach ($words as $word) {
                $i = DB::table("item_types")->where("item_type_name", "like", "%". $word ."%")->first();
                if ($i) {
                    $item_types[] = ["item_type" => $word, "id" => $i->item_type_id ];
                }
            }

            if ( substr_count($keyword, '.') > 0 ) {

                $allProducts =    DB::table("products")
                                ->where("product_code_inv", "like" ,"". $keyword ."%")
                                ->orWhere("product_code_inv",  $keyword  )
                                ->where('product_price2', '>', 0)
                                ->where("product_is_exist", "1")
                                ->pluck("product_code_inv");

                if (count($allProducts->toArray()) == 0) {

                      $allProducts =    DB::table("products")
                                ->whereRaw("product_name like  '%". $keyword ."%' AND product_price2 > 0")

                                // ->where('product_price2', '>', 0)
                                // ->where("product_is_exist", "1")
                                ->pluck("product_code_inv");

                    // print_r($allProducts);
                }


            }
            else {

                if ($keyword == "ips monitor"  ) {
                     $allProducts = DB::table("products")
                                ->where("product_name", "like", "%".  $keyword ."%")
                                ->orWhere("product_name",    "%monitor%"  )
                                ->where('product_price2', '>', 0)
                                ->where("product_is_exist", "1")
                                ->pluck("product_code_inv");
                }

                else if ( in_array($keyword, $search_list) ) {

                     $allProducts = DB::table("products")
                                ->where("product_name", "like", "%".  $keyword ."%")
                                ->where('product_price2', '>', 0)
                                ->where("product_is_exist", "1")
                                ->pluck("product_code_inv");
                }

                else if ($keyword == "network cable"  ) {
                    $keyword = "cable";
                     $allProducts = DB::table("products")
                                ->where("product_name", "like", "%".  $keyword ."%")
                                ->where('product_price2', '>', 0)
                                ->where("product_is_exist", "1")
                                ->pluck("product_code_inv");
                }

                else if ($keyword == "network router"  ) {
                    $keyword = "router";
                     $allProducts = DB::table("products")
                                ->where("product_name", "like", "%".  $keyword ."%")
                                ->where('product_price2', '>', 0)
                                ->where("product_is_exist", "1")
                                ->pluck("product_code_inv");
                }




                else if (isset($words[0])) {
                    try {
                        $allProducts = DB::table("products")
                                ->where("item_type_id",  $item_types[0]['id'])
                                ->where("product_name", "like", "%".  $words[0] ."%")
                                ->orWhere("product_name",    $words[0]  )
                                ->where('product_price2', '>', 0)
                                ->where("product_is_exist", "1")
                                ->pluck("product_code_inv");
                    }
                    catch(\Exception $e) {
                         $allProducts = DB::table("products")
                                // ->where("item_type_id",  $item_types[0]['id'])
                                ->where("product_name", "like", "%".  $words[0] ."%")
                                ->orWhere("product_name",    $words[0]  )
                                ->where('product_price2', '>', 0)
                                ->where("product_is_exist", "1")
                                ->pluck("product_code_inv");
                    }
                }

                else {
                    $allProducts = DB::table("products")
                                ->where("item_type_id",  $item_types[0]['id'])
                                ->where('product_price2', '>', 0)
                                ->where("product_is_exist", "1")
                                ->pluck("product_code_inv");
                }
            }


            return response()->json($allProducts);

            // $products = DB::table('products')
            //                     ->leftJoin("item_types", "item_types.item_type_id", "=", "products.item_type_id")
            //                     ->leftJoin("brands", "brands.brand_id", "=", "products.brand_id")
            //                     ->selectRaw("item_types.item_type_name as item, brands.brand_name as brand, products.product_name as item_name,
            //                                         products.product_code_inv as product_sku,   products.product_price1 as reg_price, products.product_price2 as sug_price")
            //                     ->where("products.product_price2", ">", 0)
            //                     ->where("products.product_is_exist", "1")
            //                      ->whereIn("products.product_code_inv", $allProducts)
            //                     ->paginate(15);



            // if(count($products) > 0 ) {

            //     $dataList = [];
            //     foreach($products as $p) {
            //         $pp = DB::table('products')->where('products.product_code_inv', $p->product_sku)->first();
            //         $image = DB::table('product_images')
            //                                     ->select(['product_image_name'])
            //                                     ->join('products', 'product_images.product_id', '=', 'products.product_id')
            //                                     ->where('products.product_code_inv', $p->product_sku)
            //                                     ->get();

            //         $dataList[$p->product_sku] = [
            //                 'item' => $p->item ,
            //                 'brand' => $p->brand ,
            //                 'item_name' => $p->item_name,
            //                 'product_sku' => $p->product_sku,
            //                 'product_id' => $pp->product_id,
            //                 'qty' => isset($p->qty) ? $p->qty : 1,
            //                 'reg_price' => $p->reg_price,
            //                 'sug_price' => $p->sug_price,
            //                 'img_base' => isset($image[0]->product_image_name) ? "https://www.ryanstasks.com/storage/products/small" : "https://www.ryanstasks.com/logo/",
            //                 'image' => isset($image[0]->product_image_name) ?   $image[0]->product_image_name : 'Ryans.png'
            //             ];
            //     }

            //     $data = [];
            //     foreach($dataList as $d) {
            //         $data[] = $d;
            //     }

            //     return response()->json([
            //                                 'data'=> $data,
            //                                 'total' => count($products),
            //                                 'success' => true
            //                             ]);
            // }else {
            //     return response()->json([
            //                             'msg'=> 'No result found' ,
            //                              'data'=> [] ,
            //                              'total' => 0,
            //                             'success' => true
            //                         ]);
            // }



       }
    }

    //== not part of api
    public function invDataImport()
    {
        $url = config('constants.generate_inv_b2b_path');
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");

        $records = curl_exec($ch);
        if (curl_errno($ch)) {
            echo 'Error:' . curl_error($ch);
        }
        curl_close ($ch);

        $records_data = json_decode($records, true);
        $dataProducts = $records_data['data'];


        foreach($dataProducts as $key => $val) {
            $brands = $val['brand'];
            foreach($brands as $b) {
                $item = $val['item'];
                $brand = $b['name'];
                $products = $b['product'];
                foreach($products as $p) {
                    $product_name = $p['item_name'];
                    $product_id = $p['product_id'];
                    $reg_price = $p['reg_price'];
                    $sug_price = $p['sug_price'];
                    $qty = $p['qty'];

                    echo $item . '  ' . $brand . '  '. $product_name .'  '. $product_id .'  '.$reg_price .'  '. $sug_price .' '. $qty  .'<br/>';

                }
            }
        }

        return true;
    }

    public function logout(Request $request)
    {
         Log::info('logout-request: '. json_encode($request->all()));
         $user = trim($request->bearerToken());
         Log::info('logout-bearer-token: '. json_encode($user));
        if (empty($user)) {
            return response()->json(['error'=> 'User not found !' , 'success' => false ], 401);
        } else {
             if(empty($request->phone)) {
                  return response()->json(['error'=> 'Phone number required' , 'success' => false ], 401);
             }
            else{
                \Auth()->logout();
                return response()->json(['message' => 'Successfully logged out', 'success'=> true]);
            }
        }
    }

    //=========== resend otp
    public function otpRsend(Request $request) {
       Log::info('otpRsend: '. json_encode($request->all()));
       if(empty($request->phone) &&  empty($request->email) ) {
            return response()->json(['error'=> 'Enter phone or email address' , 'success' => false ], 401);
       }
       else {
           if(!empty($request->phone)) {
             if (empty($request->code)) {
                return response()->json(['error'=> 'Country Code is required' , 'success' => false ], 401);
             } else {
                 $phone_is_exist =  DB::table('b2b_users')->where('phone',  trim($request->code)  .  trim($request->phone))->count();
                  if($phone_is_exist == 0) {
                     return response()->json(['error'=> 'Phone number is not registered' , 'success' => false ], 401);
                  }else {
                     return $this->resendOTPFunctionality($request, 'phone');
                  }
             }
           }

            if(!empty($request->email)) {
              $phone_is_exist =  DB::table('b2b_users')->where('email', $request->email)->count();
              if ($phone_is_exist == 0) {
                 return response()->json(['error'=> 'Email address is not registered' , 'success' => false ], 401);
              }else {
                return $this->resendOTPFunctionality($request, 'email');
              }
           }
       }

    }

    //========================= forgot password api start=======================
    // ====================== step1
    public function sendForgotPasswordVerificationCode(Request $request) {
        Log::info('sendForgotPasswordVerificationCode: '. json_encode($request->all()));
       if(empty($request->phone) &&  empty($request->email) ) {
            return response()->json(['error'=> 'Enter phone or email address' , 'success' => false ], 401);
       }
       else {
           if(!empty($request->phone)) {
             if (empty($request->code)) {
                return response()->json(['error'=> 'Country Code is required' , 'success' => false ], 401);
             } else {
                 $phone_is_exist =  DB::table('b2b_users')->where('phone',  trim($request->code)  .  trim($request->phone))->count();
                  if($phone_is_exist == 0) {
                     return response()->json(['error'=> 'Phone number is not registered' , 'success' => false ], 401);
                  }else {
                     return $this->forgotPasswordFunctionality($request, 'phone');
                  }
             }
           }

           if(!empty($request->email)) {
             $phone_is_exist =  DB::table('b2b_users')->where('email', $request->email)->count();
              if($phone_is_exist == 0) {
                 return response()->json(['error'=> 'Email address is not registered' , 'success' => false ], 401);
              }else {
                return $this->forgotPasswordFunctionality($request, 'email');
              }
           }
       }

    }

    protected function sendSMS($mobileNumber, $message) {
        // Log::info('sendSMS:'.$mobileNumber .','. $message  );
        try{
            $c = DB::table('sms_config')->where('id', 1)->first();
            $apiToken = $c->api_token;
            $sid = $c->sid;
            $url = $c->url;


            // $apiToken = config('constants.sms_api_token');
            // $sid = config('constants.sms_sid');
            // $url = config('constants.sms_url');
            //Log::info('sendSMS:'.$apiToken .','. $sid . ','.   $url);
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

    protected function sendEmailForVerificationCode($request, $verify_token, $customer_id) {
            Log::info('sendEmailForVerificationCode: '. $request->email .' , '  . $verify_token . ' , ' .  $customer_id);
            try {
                $data = array(
                        'customer_id' => $customer_id,
                        'email' => $request->email,
                        'verify_token' => $verify_token,
                    );


                Mail::send('password-verify', $data, function ($message) use ($data) {
                        $message->from('info@ryanscomputers.com');
                        $message->to($data['email']);
                        $message->subject('Ryans Computers B2B APP - forget password verification');
                    });

                return 1;
            }catch(\Exception $e) {
                Log::info('Error: '. $e->getMessage());
                return 0;
            }
    }

    protected function resendOTPFunctionality($request, $notificationType) {
        $verify_token = rand(1111, 9993);

        $time = config('constants.phone_verify_token_time_expire') / 60;
        $message = 'Your Verification code for reset password is ' . $verify_token . ' Thank you. Ryans Computers Ltd.';


        if($notificationType == 'phone') {
            $mobileNumber = trim($request->code). trim($request->phone);

            $cust = DB::table('b2b_users')->where('phone', $mobileNumber)->first();
            $today = Carbon::now();
            $second = $today->diffInSeconds($cust->verify_password_token_time);
            Log::info('phone-block-diffInSeconds: '. $second);
            if($second > 10) {
                $isSendNotification = $this->sendSMS($mobileNumber, $message);
                Log::info('Yes::isSendNotification data:'.  json_encode($isSendNotification));
            }else{
                $isSendNotification = -1;
                Log::info('No::isSendNotification data:'.  json_encode($isSendNotification));
            }

        }

        if($notificationType == 'email') {
            $customer = DB::table('b2b_users')->select('b2b_user_id', 'phone')
            ->where('email',  trim($request->email))->first();

            Log::info('customer-detail-for-eamil: '. json_encode($customer));
            $mobileNumber = $customer->phone;

            $cust = DB::table('b2b_users')->where('phone', $mobileNumber)->first();
            $today = Carbon::now();
            $second = $today->diffInSeconds($cust->verify_password_token_time);
            Log::info('phone-block-diffInSeconds: '. $second);

            if($second > 10) {
              $isSendNotification = $this->sendEmailForVerificationCode($request, $verify_token, $customer->b2b_user_id);
            } else {
                $isSendNotification = -1;
            }
        }

        if($isSendNotification == 0) {
             return response()->json(['error'=> 'OTP Not Send' , 'success' => false ], 401);
        } else {

            if ($isSendNotification == -1) {
               return response()->json(['error'=> 'OTP will active after 10 seconds' , 'success' => false ], 401);
            } else {
                Log::info('here'. $verify_token. ' , ' .$message);

                // DB::table('b2b_users')
                // ->where('phone', $mobileNumber)
                // ->update(['verify_password_token' => null,
                // 'verify_password_token_time' => null,
                // 'verify_password_token_count' => null]);

                DB::table('b2b_users')
                        ->where('phone', $mobileNumber)
                        ->update([
                                    'verify_password_token' => $verify_token,
                                    'verify_password_token_time' => Carbon::now(),
                                    'verify_password_token_count' => 1
                                ]);

                return response()->json([ 'verify_token'=> $verify_token , 'msg'=> $message,  'success'=> true ]);
            }
        }
    }

    protected function forgotPasswordFunctionality($request, $notificationType) {

        $verify_token = rand(1111, 9993);

        $time = config('constants.phone_verify_token_time_expire') / 60;
        $message = 'Your Verification code for reset password is ' . $verify_token . ' Thank you. Ryans Computers Ltd.';


        if($notificationType == 'phone') {
            $mobileNumber = trim($request->code). trim($request->phone);

            // $cust = DB::table('b2b_users')->where('phone', $mobileNumber)->first();
            // $today = Carbon::now();
            // $second = $today->diffInSeconds($cust->verify_password_token_time);
            // Log::info('phone-block-diffInSeconds: '. $second);
            // if($second > 10) {

                $isSendNotification = $this->sendSMS($mobileNumber, $message);

            // }else{
            //     $isSendNotification = -1;
            // }
            Log::info('forgotPasswordFunctionality::isSendNotification data:'.  json_encode($isSendNotification));
        }

        if($notificationType == 'email') {
            $customer = DB::table('b2b_users')->select('b2b_user_id', 'phone')
                                ->where('email',  trim($request->email))->first();

            Log::info('customer-detail-for-eamil: '. json_encode($customer));
            $mobileNumber = $customer->phone;

            // $cust = DB::table('b2b_users')->where('phone', $mobileNumber)->first();
            // $today = Carbon::now();
            // $second = $today->diffInSeconds($cust->verify_password_token_time);
            // Log::info('phone-block-diffInSeconds: '. $second);

            // if($second > 10) {
              $isSendNotification = $this->sendEmailForVerificationCode($request, $verify_token, $customer->b2b_user_id);
            // }else {
            //     $isSendNotification = -1;
            // }
        }

        if($isSendNotification == 0) {
             return response()->json(['error'=> 'OTP Not Send' , 'success' => false ], 401);
        } else {

            if($isSendNotification == -1) {
               return response()->json(['error'=> 'OTP will active after 10 seconds' , 'success' => false ], 401);
            } else {
                Log::info('here'. $verify_token. ' , ' .$message);
                // DB::table('b2b_users')
                // ->where('phone', $mobileNumber)
                // ->update(['verify_password_token' => null,
                // 'verify_password_token_time' => null,
                // 'verify_password_token_count' => null]);

                DB::table('b2b_users')
                        ->where('phone', $mobileNumber)
                        ->update([
                            'verify_password_token' => $verify_token,
                            'verify_password_token_time' => Carbon::now(),
                            'verify_password_token_count' => 1
                        ]);

                return response()->json([ 'verify_token'=> $verify_token , 'msg'=> $message,  'success'=> true ]);
            }
        }
    }

    //================ forgot password verification code check
    //============= step2
    public function verificationCodeCechk(Request $request) {

        if(empty($request->phone) &&  empty($request->email) ) {
            return response()->json(['error'=> 'Enter phone or email address' , 'success' => false ], 401);
        }
        else {

            if(!empty($request->phone)) {
                if (empty($request->code)) {
                    return response()->json(['error'=> 'Country Code is required' , 'success' => false ], 401);
                } else {
                    $phone_is_exist =  DB::table('b2b_users')->where('phone',  trim($request->code)  .  trim($request->phone))->count();
                    if($phone_is_exist == 0) {
                        return response()->json(['error'=> 'Phone number is not registered' , 'success' => false ], 401);
                    }else {
                        $customer =  DB::table('b2b_users')
                                        ->where('phone', trim($request->code) . trim($request->phone))->first();
                        if($customer->verify_password_token == trim($request->verify_code) ) {
                            return response()->json(['msg'=> 'Verification code matched' , 'success' => true ]);
                        }else {
                            return response()->json(['error'=> 'Invalid verification code' , 'success' => false ], 401);
                        }
                    }
                }
            }

            if(!empty($request->email)) {
                $phone_is_exist =  DB::table('b2b_users')->where('email', $request->email)->count();
                if($phone_is_exist == 0) {
                    return response()->json(['error'=> 'Email address is not registered' , 'success' => false ], 401);
                }else {
                    $customer =  DB::table('b2b_users')
                                    ->where('email', trim($request->email) )->first();
                    if($customer->verify_password_token == trim($request->verify_code) ) {
                        return response()->json(['msg'=> 'Verification code matched' , 'success' => true ]);
                    }else {
                        return response()->json(['error'=> 'Invalid verification code' , 'success' => false ], 401);
                    }
                }
            }
        }

    }

    //=================== step3(final)
    public function resetPassword(Request $request) {

       if(empty($request->phone) &&  empty($request->email) ) {
            return response()->json(['error'=> 'Enter phone or email address' , 'success' => false ], 401);
       }
       else {
           // ==== for phone
            if(!empty($request->phone)) {
                if (empty($request->code)) {
                    return response()->json(['error'=> 'Country Code is required' , 'success' => false ], 401);
                } else {
                    $phone_is_exist =  DB::table('b2b_users')->where('phone',  trim($request->code)  .  trim($request->phone))->count();
                    if($phone_is_exist == 0) {
                        return response()->json(['error'=> 'Phone number is not registered' , 'success' => false ], 401);
                    } else {
                        $customer =  DB::table('b2b_users')
                                        ->where('phone', trim($request->code) . trim($request->phone))->first();
                        if($customer) {

                            // if(strcmp(  trim($request->password), trim($request->conf_password))) {
                            if( strlen($request->password)  < 6 ) {
                               return response()->json(['error'=> 'Password minimum 6 character' , 'success' => false ], 401);
                            }
                            else if(  trim($request->password) === trim($request->conf_password)  ) {
                                 $password_hash = Hash::make(trim($request->password));

                                 DB::table('b2b_users')
                                        ->where('phone', trim($request->code) . trim($request->phone))
                                        ->update([
                                            'password' => $password_hash,
                                            'verify_password_token' => null,
                                            'verify_password_token_time' => null,
                                            'verify_password_token_count' => null,
                                            'updated_at' => Carbon::now()
                                        ]);
                                return response()->json(['msg'=> 'Password reset successfully' , 'success' => true ]);
                            } else {
                                return response()->json(['error'=> 'Password and Confirm Password not matched' , 'success' => false ], 401);
                            }
                        }else {
                            return response()->json(['error'=> 'Invalid verification code' , 'success' => false ], 401);
                        }
                    }
                }
            }

           //================ for email
           if(!empty($request->email)) {
             $phone_is_exist =  DB::table('b2b_users')->where('email', $request->email)->count();
                if($phone_is_exist == 0) {
                    return response()->json(['error'=> 'Email address is not registered' , 'success' => false ], 401);
                } else {
                    $customer =  DB::table('b2b_users')
                                    ->where('email', trim($request->email) )->first();
                    if($customer) {

                        // if(strcmp(trim($request->password), trim($request->conf_password))) {
                        if( strlen($request->password)  < 6 ) {
                            return response()->json(['error'=> 'Password minimum 6 character' , 'success' => false ], 401);
                        }
                        else if( trim($request->password) === trim($request->conf_password)  ) {
                            $password_hash = Hash::make(trim($request->password));
                             DB::table('b2b_users')
                                        ->where('phone', $customer->phone)
                                        ->update([
                                            'password' => $password_hash,
                                            'verify_password_token' => null,
                                            'verify_password_token_time' => null,
                                            'verify_password_token_count' => null,
                                            'updated_at' => Carbon::now()
                                        ]);
                            return response()->json(['msg'=> 'Password reset successfully' , 'success' => true ]);
                        } else {
                            return response()->json(['error'=> 'Password and Confirm Password not matched' , 'success' => false ], 401);
                        }
                    } else {
                        return response()->json(['error'=> 'Invalid verification code' , 'success' => false ], 401);
                    }
                }
           }
       }
    }

    protected function _get_attribute_sets($productId, $itemTypeId)
    {
        $attribute_sets = DB::table("product_advanced")
                            ->where("product_id", "=", $productId)
                            ->join("attribute_sets", "attribute_sets.attribute_set_id", "=", "product_advanced.attribute_set_id")
                            ->leftJoin("attributes", "attributes.attribute_id", "=", "product_advanced.attribute_id")
                            ->join('attribute_set_item_type', function ($join) use ($itemTypeId) {
                                $join->on('attribute_set_item_type.attribute_set_id', '=', 'product_advanced.attribute_set_id')
                                        ->where('attribute_set_item_type.item_type_id', '=', $itemTypeId);
                            })
                            ->leftJoin("item_type_groups", "item_type_groups.id", "=", "attribute_set_item_type.group_id")
                            ->select("item_type_groups.name as group_name",
                                "attribute_sets.attribute_set_id",
                                "attribute_sets.attribute_set_name",
                                "attributes.attribute_name",
                                "product_advanced.attribute_value"
                            )
                            ->groupBy("item_type_groups.name",
                                "attribute_set_item_type.group_id",
                                "attribute_sets.attribute_set_id",
                                "attribute_sets.attribute_set_name",
                                "attributes.attribute_name",
                                "product_advanced.attribute_value",
                                "attribute_set_item_type.group_priority",
                                "attribute_set_item_type.priority"
                            )
                            ->orderByRaw("-attribute_set_item_type.group_priority DESC")
                            ->orderBy("attribute_set_item_type.priority", "ASC")
                            ->whereIn('attribute_set_item_type.position', ['0','2','3'])
                            ->get();

        $data = [];
        $prevGroup = 'Others';

        foreach ($attribute_sets as $attribute_set) {
            if ($attribute_set->group_name != $prevGroup) {
                $group = $attribute_set->group_name ? $attribute_set->group_name : 'Additional Info';
                $attribute_set_name = $attribute_set->attribute_set_name;
                $attribute_name = $attribute_set->attribute_name == null ? $attribute_set->attribute_value : $attribute_set->attribute_name;
                $data['data'][$group][$attribute_set_name] = $attribute_name;

                $prevGroup = $attribute_set->group_name;
            } else {
                $group = $attribute_set->group_name ? $attribute_set->group_name : 'Additional Info';
                $attribute_set_name = $attribute_set->attribute_set_name;
                $attribute_name = $attribute_set->attribute_name == null ? $attribute_set->attribute_value : $attribute_set->attribute_name;

                $data['data'][$group][$attribute_set_name] = $attribute_name;
            }
        }

        // File::put(storage_path() . '/app/public/attributes.json', json_encode($data));
        return $data;
    }

    public function homePageList(Request $request) {

        $user = trim($request->bearerToken());
        if (! $user) {
            return response()->json(['error'=> 'Token required' , 'success' => false ], 401);
        }
        else {

            $items = DB::table("b2b_inv_data")
                        ->distinct()
                        ->select(["item", "brand"])
                        ->take(3)
                        ->get();

            $responseData = [];
            $imagesList = [] ;
            foreach($items as $item) {

                $itemName = $item->brand;

                $products = DB::table("b2b_inv_data")
                    ->selectRaw("product_name, item, brand, sub_type_name , product_sku , qty, reg_price, sug_price" )
                    ->where('item', $item->item )
                    // ->where('item', $item->brand )
                    ->get();

                $temp = [];

                foreach($products as $product) {
                    $p = DB::table("products")
                                ->select('products.product_id')
                                ->where('products.product_code_inv', trim($product->product_sku) )->first();

                    $productID = isset($p->product_id) ? $p->product_id : null;
                    $image = DB::table("product_images")->select('product_image_name')->where('product_id', $productID)->first();

                    if ($image) {
                        $imagesList[$product->product_sku] =  [ isset($image->product_image_name) ? "https://www.ryans.com/storage/products/small/". $image->product_image_name : '' ];

                        $temp[] = [
                            "item" => $product->item ,
                            "item_name" => $product->product_name ,
                            "brand_name" => $product->brand ,
                            "sub_type_name" => isset($product->sub_type_name) ? $product->sub_type_name : null ,
                            "product_id" => $product->product_sku ,
                            "qty" => $product->qty ,
                            "reg_price" => $product->reg_price ,
                            "sug_price" => $product->sug_price ,
                            "img_base" => "https://www.ryans.com/storage/products/small/",
                            "image" => isset($image->product_image_name) ? $image->product_image_name  : null
                        ];
                    }
                }

                 $responseData[] = array_merge(['brand_name' =>$itemName], [ 'product_list' =>$temp ]);
            }



            return response()->json(['data'=> $responseData  , 'images' => [$imagesList],   'success' => true ]);
        }
    }

    public function homePageListDetail(Request $request) {

        $user = trim($request->bearerToken());
        if (! $user) {
            return response()->json(['error'=> 'Token required' , 'success' => false ], 401);
        }

        else if (empty($request->item)) {
            return response()->json(['error'=> 'Item required' , 'success' => false ], 401);
        }
        else {


            $items = DB::table("b2b_inv_data")
                        ->distinct()
                        ->select(["item", "brand"])
                        ->where("item", $request->item)
                        // ->take(3)
                        ->get();

            $responseData = [];
            $imagesList = [];

            foreach($items as $item) {

                $itemName = $item->brand;

                $products = DB::table("b2b_inv_data")
                    ->selectRaw("product_name, item, brand, sub_type_name , product_sku , qty, reg_price, sug_price" )
                    ->where('item', $item->item )
                    // ->where('item', $item->brand )
                    ->get();

                $temp = [];

                foreach($products as $product) {
                    $p = DB::table("products")
                    ->select('products.product_id')
                    ->where('products.product_code_inv', trim($product->product_sku) )->first();


                    $productID = isset($p->product_id) ? $p->product_id : null;
                    $image = DB::table("product_images")->select('product_image_name')->where('product_id', $productID)->first();

                    $imagesList[$product->product_sku] =  [ isset($image->product_image_name) ? "https://www.ryans.com/storage/products/small". $image->product_image_name : '' ];

                    $temp[] = [
                            "item" => $product->item ,
                            "item_name" => $product->product_name ,
                            "brand_name" => $product->brand ,
                            "sub_type_name" => isset($product->sub_type_name) ? $product->sub_type_name : null ,
                            "product_id" => $product->product_sku ,
                            "qty" => $product->qty ,
                            "reg_price" => $product->reg_price ,
                            "sug_price" => $product->sug_price ,

                            "image" => isset($image->product_image_name) ? $image->product_image_name  : null
                        ];
                }

                 $responseData[] = array_merge(['brand_name' =>$itemName], [ 'product_list' =>$temp ]);
            }




            return response()->json(['data'=> $responseData, 'images'=> $imagesList , 'success' => true ]);
        }
    }

    protected function getChild($category_id, $productIDFromCategoryList)
    {
        $subCategories = DB::table('categories')
                            ->select(['category_id', 'category_name' ])
                            ->where('category_parent_id', $category_id )
                            ->whereIn('category_id', $productIDFromCategoryList)
                            ->where('category_is_tag', 'BRAND')
                            //  ->whereNotIn('category_is_tag', ['TAG'])
                            ->orderBy('category_name', 'asc')
                            ->get();

        //  $productCodeList = DB::table('b2b_products')->select('inv_code')->pluck('inv_code');
        //  $productIDList = DB::table('products')->select('product_id')->whereIn('product_code_inv', $productCodeList)->pluck('product_id');
        //  $productIDFromCategoryList = DB::table('product_categories')->select('category_id')->whereIn('product_id', $productIDList)->pluck('category_id');


        // $subCategories = DB::table('categories')
        //                     ->select(['category_id', 'category_name' ])
        //                     ->where('category_parent_id', $category_id )
        //                      ->selectRaw("distinct category_name ,category_id")
        //                     ->whereIn('category_id', $productIDFromCategoryList)
        //                     ->where('category_is_visible', '1')
        //                     ->where('category_is_linkable', '1')
        //                     ->get();


        return  $subCategories;

    }

    public function getCategories() {

        $data = [];
        try {
            $data = $this->categoryLayers();
            return response()->json(['data'=> $data,'success' => true ]);
        }
        catch(\Exception $e) {
            return response()->json(['data'=> $data,'success' => false, 'msg' => $e->getMessage() ]);
        }

    }


    public function getParentCategory($category_id) {
        return DB::table('categories')
                            ->selectRaw("distinct category_name ,category_id")
                            ->where("category_parent_id", $category_id)
                            ->get();
    }



    protected function categoryLayers() {


        $productCodeList = DB::table('b2b_products')->select('inv_code')->pluck('inv_code');
        $productIDList = DB::table('products')->select('product_id')->whereIn('product_code_inv', $productCodeList)->pluck('product_id');
        $productIDFromCategoryList = DB::table('product_categories')->select('category_id')->whereIn('product_id', $productIDList)->pluck('category_id');



        $data = [];

        $categories = DB::table('categories')
                            ->selectRaw("distinct category_name ,category_id")
                            ->where('category_parent_id', '1')
                            ->whereNotIn('category_id', [ 14,131, 267,275, 2475 ])
                            ->where('category_is_exist', '1')
                            ->whereNotIn('category_is_tag', ['TAG'])
                            ->orderBy('category_id', 'desc')
                            ->get();

        foreach($categories as $cat) {
            // $data[] = $cat->category_id;
            $subCategories = $this->getChild($cat->category_id, $productIDFromCategoryList );
            $second = [];
            foreach($subCategories as $sc) {
                $subSubCategories = $this->getChild($sc->category_id, $productIDFromCategoryList );
                $third = [];
                foreach($subSubCategories as $ssc) {

                    $fourth =[];
                    $subSubSubCategories = $this->getChild($ssc->category_id ,$productIDFromCategoryList );
                    foreach($subSubSubCategories as $sssc) {
                        $fourth[] = ['category_id' => $sssc->category_id ,'category_name' => $sssc->category_name];
                    }

                    $third[] = ['category_id' => $ssc->category_id ,'category_name' => $ssc->category_name, 'sub_sub_sub' => $fourth ];
                }
                $second[] = ['category_id' => $sc->category_id  , 'category_name' => $sc->category_name , 'sub_sub' => $third ];
            }

            // $parent = $this->getParentCategory($cat->category_id);
            if(count($second) > 0) {
                $data[] = [ 'category_id' => $cat->category_id,'category_name' => $cat->category_name , "sub" => $second ];
            }
        }

        return $data;
        // return  ['data'=>$data,  'list' => $productIDFromCategoryList, 'parent' => $parents];
    }

    public function getFilterData(Request $request) {
        $responseData = [];
        try {
            $category_id = $request->category_id;
            $count = 10;

            $page = trim($request->page);
            $page = empty($page) || $page == 0 ? 1: $page;

            $limit = $count ;
            $offset = (($page-1) * $count);
            $attrParse = explode(",", $request->attr);


            $sort = isset($request->sortBy) ?  $request->sortBy : 'DF';


            if (count($attrParse) > 0) {
                $productsIdList =  DB::table("products")
                                        ->selectRaw("distinct products.product_id")
                                        ->join("product_categories", "product_categories.product_id", "=", "products.product_id")
                                        ->join("product_advanced", "products.product_id", "=", "product_advanced.product_id")
                                        ->join("b2b_products", "b2b_products.inv_code", "=", "products.product_code_inv")
                                        ->where("product_categories.category_id", $category_id)
                                        // ->whereIn("product_advanced.attribute_id", $attrParse)
                                        ->pluck("products.product_id");
            }
            else {
                $productsIdList =  DB::table("products")
                                        ->selectRaw("distinct products.product_id")
                                        ->join("product_categories", "product_categories.product_id", "=", "products.product_id")
                                        ->join("product_advanced", "products.product_id", "=", "product_advanced.product_id")
                                        ->join("b2b_products", "b2b_products.inv_code", "=", "products.product_code_inv")
                                        ->where("product_categories.category_id", $category_id)
                                        ->pluck("products.product_id");
            }


            $defaultMinPrice = DB::table("products")->whereIn("products.product_id", $productsIdList)->min("products.product_price1");
            $defaultMaxPrice = DB::table("products")->whereIn("products.product_id", $productsIdList)->max("products.product_price1");
            $defaultMaxPrice = $defaultMaxPrice  == 0 ? 10 : $defaultMaxPrice ;

            $sortQuery = ($sort == 'LH') ? 'asc' : 'desc';

            // if (isset($request->price) ) {

                // $isPrice = true;
                // $prices = explode(",", trim($request->price));

                // $minPrice = isset($prices[0]) ? $prices[0] : 0;
                // $maxPrice = isset($prices[1]) ? $prices[1] : 0;

                if ($sort == 'DF') {
                    $products =  DB::table("products")
                                        ->selectRaw("products.product_id,products.product_code_inv as product_sku,products.product_name,products.item_type_id, products.brand_id,products.product_price1,products.product_price2,products.product_slug , products.special_discount")
                                        //->join("product_categories", "product_categories.product_id", "=", "products.product_id")
                                        // ->where("product_categories.category_id", $category_id)

                                        ->whereIn("products.product_id", $productsIdList)
                                        // ->where("products.product_price2", ">=", $minPrice)
                                        // ->where("products.product_price2", "<=", $maxPrice)

                                        ->offset($offset)
                                        ->limit($limit)
                                        ->get();
                }  else {
                        $products =  DB::table("products")
                                        ->selectRaw("products.product_id,products.product_code_inv as product_sku,products.product_name,products.item_type_id, products.brand_id,products.product_price1,products.product_price2,products.product_slug , products.special_discount")
                                        //->join("product_categories", "product_categories.product_id", "=", "products.product_id")
                                        // ->where("product_categories.category_id", $category_id)

                                        ->whereIn("products.product_id", $productsIdList)
                                        // ->where("products.product_price2", ">=", $minPrice)
                                        // ->where("products.product_price2", "<=", $maxPrice)

                                        ->orderBy('products.product_price1', $sortQuery)
                                        ->offset($offset)
                                        ->limit($limit)
                                        ->get();
                }

            // } else {
            //     $isPrice = false;
            //     $minPrice = 0;
            //     $maxPrice = 0;
            //     $products =  DB::table("products")
            //                             ->selectRaw("products.product_id,products.product_code_inv as product_sku,products.product_name,products.item_type_id, products.brand_id,products.product_price1,products.product_price2,products.product_slug , products.special_discount")
            //                             //->join("product_categories", "product_categories.product_id", "=", "products.product_id")
            //                             // ->where("product_categories.category_id", $category_id)
            //                             ->whereIn("products.product_id", $productsIdList)
            //                             ->offset($offset)
            //                             ->limit($limit)
            //                             ->get();
            // }





            foreach($products as $item) {

                $i = DB::table("item_types")->where("item_type_id", $item->item_type_id)->first();
                $b = DB::table("brands")->where("brand_id", $item->brand_id)->first();
                $image = DB::table("product_images")->select("product_image_name")->where("product_id", $item->product_id)->first();

                $responseData[] = [
                                    'item'       => $i->item_type_name,
                                    'brand'      => $b->brand_name,
                                    'item_name'  => $item->product_name,
                                    'product_sku' => $item->product_sku,
                                    'product_id' => $item->product_id,
                                    'qty'        => 1,
                                    'reg_price'  => $item->product_price1,
                                    'sug_price'  => $item->product_price2,
                                    'img_base'   => isset($image->product_image_name) ? "https://www.ryans.com/storage/products/small" : "https://www.ryans.com/logo/",
                                    'image'      => isset($image->product_image_name) ? $image->product_image_name : 'Ryans.png'
                                ];
            }

            return response()->json(['data'=> $responseData,  'range' =>['min' => $defaultMinPrice, 'max' => $defaultMaxPrice], 'success' => true ]);
        }
        catch(\Exception $e) {
            return response()->json(['attr'=> [], 'range' =>['min'=> null, 'max' => null], 'success' => false, 'msg' => $e->getMessage() ]);
        }
    }

    protected function textAttributes($attribute_set_id, $list) {

        try {
            $attributeValues = DB::table("product_advanced")
                                    ->selectRaw("distinct product_advanced.attribute_value")
                                    ->where("product_advanced.attribute_id", 0)
                                    ->where("product_advanced.attribute_set_id", $attribute_set_id)
                                    ->whereIn("product_advanced.product_id", $list)
                                    ->groupByRaw("product_advanced.attribute_id, product_advanced.attribute_value")
                                    ->pluck("product_advanced.attribute_value");

            if (count($attributeValues) == 0 ) {
                $attributeIDList = DB::table("product_advanced")
                                            ->selectRaw("distinct product_advanced.attribute_id")
                                            ->where("product_advanced.attribute_id", ">", 0)
                                            ->where("product_advanced.attribute_set_id", $attribute_set_id)
                                            ->whereIn("product_advanced.product_id", $list)
                                            ->pluck("product_advanced.attribute_id");

                return DB::table("attributes")
                                            ->selectRaw("attributes.attribute_id, attributes.attribute_name")
                                            ->whereIn("attributes.attribute_id", $attributeIDList)
                                            ->get();
            } else {

                $attr_result = [];
                for ($i = 0 ; $i < count($attributeValues) ; $i++) {
                     $attr_result[] =[
                             'attribute_id'=> 'TXT#'. $i."_". $attribute_set_id  ,
                             'attribute_name'=> $attributeValues[$i]
                        ];
                }

                return  $attr_result;
            }
        }
        catch(\Exception $e) {
            return [];
        }

    }


    public function getAttributesData(Request $request) {
        Log::info('getAttributesData:: '. json_encode($request->all()));
        try {
            if (empty($request->category_id)) {
                return response()->json(['msg'=> 'Category required',  'success' => true ]);
            }
            else {

                $productsIDList = DB::table("product_categories")
                                        ->selectRaw("distinct product_id")
                                        ->where("category_id", $request->category_id)
                                        ->pluck("product_id");

                $categoryIdList1 = DB::table("product_categories")
                                            ->selectRaw("distinct category_id")
                                            ->whereIn("product_id", $productsIDList)
                                            ->pluck("category_id");

                $attributeSetsIdList1 = DB::table("attribute_set_category")
                                            ->selectRaw("attribute_set_id")
                                            ->whereIn("category_id", $categoryIdList1)
                                            ->pluck("attribute_set_id");

                $attributeSetsList1 = DB::table("attribute_sets")
                                        ->selectRaw("attribute_set_id, attribute_set_name")
                                        ->whereIn("attribute_set_id", $attributeSetsIdList1)
                                        ->get();

                $list = $attributeSetsList1->pluck('attribute_set_id');


                $results = [];
                foreach($attributeSetsList1 as $a) {
                    $attr_result = $this->textAttributes($a->attribute_set_id, $productsIDList);
                    if (count($attr_result) > 0) {
                     $results[] = [
                            'attribute_set_id' => $a->attribute_set_id,
                            'attribute_set_name' => $a->attribute_set_name,
                            'attributes' => $attr_result
                        ];
                    }
                }

                return response()->json(['data'=> $results, 'success' => true ]);
            }
        }
        catch(\Exception $e) {
            Log::info('getAttributesData::error:: '. $e->getMessage());
            return response()->json(['data'=> [],  'success' => false, 'msg' => $e->getMessage() ]);
        }
    }

    private function getChildNode($childs) {
        if (count($childs)) {
            foreach ($childs as $child) {
                array_push($this->category_id_array, $child->category_id);
                $this->getChildNode($child->childs);
            }
        }
        return $this->category_id_array;
    }




    public function getOrderStatusCounting(Request $request) {

        $user = trim($request->bearerToken());
        if (! $user) {
            return response()->json(['error'=> 'Token required' , 'success' => false ], 401);
        }

        else if (empty($request->phone)) {
            return response()->json(['error'=> 'Phone required' , 'success' => false ], 401);
        }
        else {
            try {
                $allCount = DB::table('b2b_customer_query')
                                                ->where('b2b_customer_query.phone', trim($request->phone) )
                                                // ->whereIn('is_active', ['Done', 'Pending', 'Processing', 'Partialy Done', ''])
                                                ->whereIn('is_active', [ 'Processing',  ''])
                                                ->orderBy('b2b_customer_query.b2b_cust_query_id', 'desc')
                                                ->count();

                $pendingCount = DB::table('b2b_customer_query')
                                            ->where('b2b_customer_query.phone', trim($request->phone) )
                                            ->whereIn('is_active', [ 'Pending' ])
                                            ->orderBy('b2b_customer_query.b2b_cust_query_id', 'desc')
                                            ->count();

                $processingCount = DB::table('b2b_customer_query')
                                        ->where('b2b_customer_query.phone', trim($request->phone) )
                                        ->whereIn('is_active', [ 'Processing' ])
                                        ->whereNotIn('is_active', ['Done', 'Partialy Done'])
                                        ->orderBy('b2b_customer_query.b2b_cust_query_id', 'desc')
                                        ->count();


                // $doneCount = DB::table('b2b_customer_query')
                //                             ->where('b2b_customer_query.phone', trim($request->phone) )
                //                             ->whereIn('is_active', [ 'Done', 'Partialy Done' ])
                //                             ->orderBy('b2b_customer_query.b2b_cust_query_id', 'desc')
                //                             ->count();


                $doneCount = DB::table('b2b_orders')
                                            ->where('phone', trim($request->phone) )
                                            ->whereIn('order_status', [ 'Done', 'Partialy Done' ])

                                            ->count();



                $unavailableCount = 0;
                // need to make all not available counting query


                return response()->json([ 'counting' => [
                                                              'allCount' => $allCount,

                                                            'pendingCount' => $pendingCount,
                                                            'processingCount'=> $processingCount,
                                                            'doneCount'=> $doneCount,
                                                            'unavailableCount' => $unavailableCount ]   ,  'success'=> true ]);
            }catch(\Exception $e) {
                return response()->json([ 'counting' => [
                                                            'allCount' => 0,
                                                            'pendingCount' => 0,
                                                            'processingCount'=> 0,
                                                            'doneCount'=> 0,
                                                            'unavailableCount' => 0
                                                        ]   ,  'success'=> true ]);
            }
        }

    }

    public function getWebQueryProducts(Request $request) {
        $user = trim($request->bearerToken());
        Log::info('getWebQueryProducts:: '. json_encode($request->all()) . "   token:". json_encode($user) );

        if (! $user) {
            return response()->json(['error'=> 'Token required' , 'success' => false, 'phone' => $request->phone  ], 401);
        }

        else if (empty($request->phone)) {
            return response()->json(['error'=> 'Phone required' , 'success' => false, 'phone' => $request->phone ], 401);
        }

        else {

            $q = DB::table("b2b_customer_query_temp")
                    ->where("phone",  $request->phone)
                    ->where("platform", "temp")->first();

            $results = [];
            if ( $q ) {
                $products = DB::table("b2b_customer_query_products_temp")
                            ->where("phone", $request->phone )
                            ->whereDate('created_at', Carbon::now()->format('Y-m-d'))->get();

                foreach($products as $p) {


                    $pp = DB::table("products")->where("product_code_inv", $p->product_sku)->first();
                    $image = DB::table("product_images")->select("product_image_name")->where("product_id", $pp->product_id)->first();

                    $img_name = isset($image->product_image_name) ? $image->product_image_name : 'Ryans.png';

                    $results[] = [
                            'item_name' => $p->product_name ,
                            'product_sku' => $p->product_sku ,
                            'product_id' => $pp->product_id ,
                            'item' => $p->item_name ,
                            'brand' => $p->brand_name ,
                            'qty' => $p->qty,
                            'reg_price' => $p->reg_price,
                            'sug_price' => $p->sug_price,
                            'is_active' => $p->is_active,
                            'check' => false,
                            'image_base' => isset($image->product_image_name) ? "https://www.ryans.com/storage/products/small"  : "https://www.ryans.com/logo/"  ,
                            'image' => isset($image->product_image_name) ? "https://www.ryans.com/storage/products/small/". $img_name : "https://www.ryans.com/logo/" . $img_name ,
                            'created_at' => Carbon::parse($q->created_at)->format('Y-m-d')
                        ];
                }

                return response()->json(['data'=> $results  ]);
            } else {
                return response()->json(['data'=> [] ]);
            }
        }
    }



    public function getWebQueryProductsRemove(Request $request) {
        $user = trim($request->bearerToken());
        Log::info('getWebQueryProductsRemove:: '. json_encode($request->all()) . "   token:". json_encode($user) );

        if (! $user) {
            return response()->json(['error'=> 'Token required' , 'success' => false  ], 401);
        }
        else if (empty($request->phone)) {
            return response()->json(['error'=> 'Phone required' , 'success' => false   ], 401);
        }

        else if (empty($request->created_at)) {
            return response()->json(['error'=> 'Date required' , 'success' => false   ], 401);
        }
        else if (empty($request->products)) {
            return response()->json(['error'=> 'Products required' , 'success' => false  ], 401);
        }
        else {
            try {
                foreach($request->products as $p) {
                    DB::table('b2b_customer_query_products_temp')->where('order_id', '')->where('product_sku', $p)->whereDate('created_at', $request->created_at)->where('phone', $request->phone)->delete();
                }
                DB::table('b2b_customer_query_temp')->where('phone', $request->phone)->whereDate('created_at', $request->created_at)->delete();

                return response()->json(['msg'=> 'removed' , 'success'=> true ]);
            }
            catch(\Exception $e) {
                return response()->json(['msg'=> $e->getMessage() , 'data'=> [], 'success'=> false  ], 500);
            }
        }
    }


    public function getAppVersion(Request $request) {
         $user = trim($request->bearerToken());
         if (! $user) {
            return response()->json(['error'=> 'Token required' , 'success' => false  ], 401);
        }else {
            // if (isset($request->platform) && $request->platform == 'ios')
            // {
            //     $result = B2BUtility::getAppVersion("ios");
            //     // $result = B2BUtility::getAppVersion(null);
            //     // DB::table('b2b_app_version_control')->where("platform", "ios")->selectRaw("app_version,donwload_url")->first();
            // }

            // else if (isset($request->platform) && $request->platform == 'android')
            // {
            //     // $result = B2BUtility::getAppVersion("android");
            //     $result = B2BUtility::getAppVersion(null);
            // }
            // else {
                // $result = B2BUtility::getAppVersion(null);
            // }
            try {
                $result = B2BUtility::getAppVersion(null);
                return response()->json(['data'=> $result , 'success'=> true ]);
            }
            catch(\Exception $e) {
                return response()->json(['data'=> [] , 'error'=> $e->getMessage() , 'success'=> false ]);
            }

        }
    }


    public function getAppVersionForIos(Request $request) {
        Log::info("getAppVersionForIos >> ". json_encode($request->all()));
        $user = trim($request->bearerToken());
        if (! $user) {
           return response()->json(['error'=> 'Token required' , 'success' => false  ], 401);
       }else {
           if (isset($request->platform) && $request->platform == 'ios')
           {
                //    $result = B2BUtility::getAppVersion("ios");
                $result = B2BUtility::getAppVersion(null);
               // DB::table('b2b_app_version_control')->where("platform", "ios")->selectRaw("app_version,donwload_url")->first();
           }

           else if (isset($request->platform) && $request->platform == 'android')
           {
            //    $result = B2BUtility::getAppVersion("android");
               $result = B2BUtility::getAppVersion(null);
           }
           else {
               $result = B2BUtility::getAppVersion(null);
           }

           return response()->json(['data'=> $result , 'success'=> true ]);
       }
   }







    //=========== search api for testing ==============
    public function globalSearch2(Request $request) {

       Log::info("globalSearch2:: ". json_encode($request->all() ) );

       $user =  trim($request->bearerToken());
       if (!$user) {
            return response()->json(['error'=> 'User not found !', 'success'=>false  ], 401);
       }else if( empty($request->phone) ) {
            return response()->json(['error'=> 'Phone number required', 'success'=> false  ], 401);
       }
       else if( empty($request->keyword) ) {
            return response()->json(['error'=> 'Search Keyword required', 'success'=> false  ], 401);
       }
       else {

           $keyword = trim($request->keyword);
           $keyword = strtolower($keyword);
           $count = 30;

           $page = trim($request->page);
           $page = empty($page) ? 1: $page;

           $limit = $count ;
           $offset = (($page-1) * $count);


            $words = explode(" ", $keyword);
            $item_types = [];
            $search_list = ["all in one pc", "usb converter", "hdmi converter", "dvi converter", "usb hub", "led strip", "gaming chair", "power supply", "ryans pc", "gaming console", "tv card" ];
            foreach ($words as $word) {
                $i = DB::table("item_types")->where("item_type_name", "like", "%". $word ."%")->first();
                if ($i) {
                    $item_types[] = ["item_type" => $word, "id" => $i->item_type_id ];
                }
            }

            if ( substr_count($keyword, '.') > 0 ) {

                $allProducts =    DB::table("products")
                                ->where("product_code_inv", "like" ,"". $keyword ."%")
                                ->orWhere("product_code_inv",  $keyword  )
                                ->where('product_price2', '>', 0)
                                ->where("product_is_exist", "1")
                                ->pluck("product_code_inv");

                if (count($allProducts->toArray()) == 0) {

                      $allProducts =    DB::table("products")
                                ->whereRaw("product_name like  '%". $keyword ."%' AND product_price2 > 0")

                                // ->where('product_price2', '>', 0)
                                // ->where("product_is_exist", "1")
                                ->pluck("product_code_inv");

                    // print_r($allProducts);
                }


            }
            else {

                if ($keyword == "ips monitor"  ) {
                     $allProducts = DB::table("products")
                                ->where("product_name", "like", "%".  $keyword ."%")
                                ->orWhere("product_name",    "%monitor%"  )
                                ->where('product_price2', '>', 0)
                                ->where("product_is_exist", "1")
                                ->pluck("product_code_inv");
                }

                else if ( in_array($keyword, $search_list) ) {

                     $allProducts = DB::table("products")
                                ->where("product_name", "like", "%".  $keyword ."%")
                                ->where('product_price2', '>', 0)
                                ->where("product_is_exist", "1")
                                ->pluck("product_code_inv");
                }

                else if ($keyword == "network cable"  ) {
                    $keyword = "cable";
                     $allProducts = DB::table("products")
                                ->where("product_name", "like", "%".  $keyword ."%")
                                ->where('product_price2', '>', 0)
                                ->where("product_is_exist", "1")
                                ->pluck("product_code_inv");
                }

                else if ($keyword == "network router"  ) {
                    $keyword = "router";
                     $allProducts = DB::table("products")
                                ->where("product_name", "like", "%".  $keyword ."%")
                                ->where('product_price2', '>', 0)
                                ->where("product_is_exist", "1")
                                ->pluck("product_code_inv");
                }




                else if (isset($words[0])) {
                    try {
                        $allProducts = DB::table("products")
                                ->where("item_type_id",  $item_types[0]['id'])
                                ->where("product_name", "like", "%".  $words[0] ."%")
                                ->orWhere("product_name",    $words[0]  )
                                ->where('product_price2', '>', 0)
                                ->where("product_is_exist", "1")
                                ->pluck("product_code_inv");
                    }
                    catch(\Exception $e) {
                         $allProducts = DB::table("products")
                                // ->where("item_type_id",  $item_types[0]['id'])
                                ->where("product_name", "like", "%".  $words[0] ."%")
                                ->orWhere("product_name",    $words[0]  )
                                ->where('product_price2', '>', 0)
                                ->where("product_is_exist", "1")
                                ->pluck("product_code_inv");
                    }
                }

                else {
                    $allProducts = DB::table("products")
                                ->where("item_type_id",  $item_types[0]['id'])
                                ->where('product_price2', '>', 0)
                                ->where("product_is_exist", "1")
                                ->pluck("product_code_inv");
                }
            }

            $products = DB::table('products')
                                ->leftJoin("item_types", "item_types.item_type_id", "=", "products.item_type_id")
                                ->leftJoin("brands", "brands.brand_id", "=", "products.brand_id")
                                ->selectRaw("item_types.item_type_name as item, brands.brand_name as brand, products.product_name as item_name,
                                                    products.product_code_inv as product_sku,   products.product_price1 as reg_price, products.product_price2 as sug_price")
                                ->where("products.product_price2", ">", 0)
                                ->where("products.product_is_exist", "1")
                                 ->whereIn("products.product_code_inv", $allProducts)
                                ->paginate(15);



            if(count($products) > 0 ) {

                $dataList = [];
                foreach($products as $p) {
                    $pp = DB::table('products')->where('products.product_code_inv', $p->product_sku)->first();
                    $image = DB::table('product_images')
                                                ->select(['product_image_name'])
                                                ->join('products', 'product_images.product_id', '=', 'products.product_id')
                                                ->where('products.product_code_inv', $p->product_sku)
                                                ->get();

                    $dataList[$p->product_sku] = [
                            'item' => $p->item ,
                            'brand' => $p->brand ,
                            'item_name' => $p->item_name,
                            'product_sku' => $p->product_sku,
                            'product_id' => $pp->product_id,
                            'qty' => isset($p->qty) ? $p->qty : 1,
                            'reg_price' => $p->reg_price,
                            'sug_price' => $p->sug_price,
                            'img_base' => isset($image[0]->product_image_name) ? "https://www.ryans.com/storage/products/small" : "https://www.ryans.com/logo/",
                            'image' => isset($image[0]->product_image_name) ?   $image[0]->product_image_name : 'Ryans.png'
                        ];
                }

                $data = [];
                foreach($dataList as $d) {
                    $data[] = $d;
                }

                return response()->json([
                                            'data'=> $data,
                                            'total' => count($products),
                                            'success' => true
                                        ]);
            }else {
                return response()->json([
                                        'msg'=> 'No result found' ,
                                         'data'=> [] ,
                                         'total' => 0,
                                        'success' => true
                                    ]);
            }
       }
    }




    public function orderSendToHistory(Request $request) {

       Log::info("orderSendToHistory:: ". json_encode($request->all() ) );

       $user =  trim($request->bearerToken());
       if (!$user) {
            return response()->json(['error'=> 'User not found !', 'success'=>false  ], 401);
       }else if( empty($request->phone) ) {
            return response()->json(['error'=> 'Phone number required', 'success'=> false  ], 401);
       }
       else if( empty($request->orderId) ) {
            return response()->json(['error'=> 'Order ID required', 'success'=> false  ], 401);
       }
       else {

            try {
                DB::table("b2b_customer_query")
                    ->where("order_id", $request->orderId)
                    ->update([  "is_history" => 1  ]);
                return response()->json([ 'msg'=> 'Order sent to history.' , 'success' => true  ]);
            }catch(\Exception $e) {
                return response()->json([ 'msg'=> 'Order not sent to history.' , 'success' => false  ]);
            }

       }
    }



   //====== customer remove for ios users ===========================
   public function customerRemove(Request $request) {

       if ( empty($request->phone) )
       {
          return response()->json(['error'=> 'Phone number required', 'success'=> false  ], 401);
       }
       else
       {
         $phone = trim($request->phone);
         $actual_part = substr( $phone, 3, strlen($phone)-1 );

         $count = DB::table("b2b_users")->where("phone" , $phone)->count();
         if ( $count > 0 ) {
           try {
               $obj = DB::table("b2b_users")->where("phone" , $phone)->first();
               $requestObj  = [
                            'b2b_user_id' => isset($obj->b2b_user_id) ? $obj->b2b_user_id : null,
                            'name' => isset($obj->name) ? $obj->name : null,
                            'org_name' => isset($obj->org_name) ? $obj->org_name : null,
                            'email' => isset($obj->email) ? $obj->email : null,
                            'phone' => isset($obj->phone) ? $obj->phone : null,
                            'address' => isset($obj->address) ? $obj->address : null,
                            'password' => isset($obj->password) ? $obj->password : null,
                            'avatar' => isset($obj->avatar) ? $obj->avatar : null,
                            'is_active' => isset($obj->is_active) ? $obj->is_active : 'Pending',
                            'b2b_customer_inv_id' => isset($obj->b2b_customer_inv_id) ? $obj->b2b_customer_inv_id : null,
                            'updated_by' => isset($obj->updated_by) ? $obj->updated_by : null,
                            'remember_token' => isset($obj->remember_token) ? $obj->remember_token: null,
                            'created_at' => isset($obj->created_at) ? $obj->created_at: null,
                            'updated_at' => isset($obj->updated_at) ? $obj->updated_at : null,
                            'verify_password_token' => isset($obj->verify_password_token) ? $obj->verify_password_token : null,
                            'verify_password_token_time' => isset($obj->verify_password_token_time) ? $obj->verify_password_token_time: null,
                            'verify_password_token_count' => isset($obj->verify_password_token_count) ? $obj->verify_password_token_count: null,
                            'city_id' => isset($obj->city_id) ? $obj->city_id: null,
                            'district_id' => isset($obj->district_id) ? $obj->district_id: null,
                            'location_id' => isset($obj->location_id) ? $obj->location_id: 0,
                            'remarks'=> isset($obj->remarks) ? $obj->remarks : null
                   ];
                DB::table("b2b_user_deletes")->insert($requestObj);
                DB::table("b2b_users")->where("phone" , $phone)->delete();

           		return response()->json(['msg'=> 'User '. $obj->name .' removed', 'success'=> true  ], 200);
           }
           catch(\Exception $e) {
                return response()->json(['msg'=> ''. $e->getMessage() , 'success'=> false  ], 200);
           }

         } else {
           	return response()->json(['msg'=> 'User not registered', 'success'=> false  ], 200);
         }
       }
   }



}


?>

