<?php
/*
    Date: 13.01.2024
    Customer Order Process Modification
*/

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Models\B2BUser;
use App\Models\B2BOrder;
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
use JWTAuth;

use App\Lib\B2BUtility;
use App\Lib\B2BOrderList;

class B2BCustomerOrderController extends Controller {

    //  order detail
    public function getSpecificOrderDetail(Request $request) {

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
            else if (empty($request->order_id)) {
                return response()->json(['error'=> 'Order ID required'  ], 401);
            }
            else {

                $num = 10;
                $phone = $request->phone;
                $order_id = $request->order_id;

                $orderObj =  DB::table("b2b_orders")
                        ->where("b2b_orders.phone", $phone)
                        ->where("b2b_orders.id", $order_id)
                        ->first();

                $data = [];
                $queries = B2BUtility::getSingleOrder($phone, $order_id);
                if (count($queries) > 0 ) {
                    foreach($queries as $od) {
                        $data[] = [
                                    'b2b_cust_query_id'  => $od->b2b_cust_query_id,
                                    'query_id' => $od->query_id,
                                    'phone'  => $od->phone,
                                    'location_id'  => $od->location_id,
                                    'total'  => $od->b2b_total,
                                    'b2b_total'  => $od->total,
                                    // 'query_status'  => $od->is_active,
                                    'platform'  => $od->platform ,
                                    'created_at'  => $od->created_at ,
                                    'order_date'  => $orderObj ? $orderObj->created_at : '' ,
                                    'item' => B2BUtility::getOrderedQueryProducs($od->b2b_cust_query_id),

                        ];
                    }
                }
                return response()->json(['data' => $data, 'success' => true ]);
            }
        }
    }



    //  ordered list
    public function getOrderedList(Request $request) {
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
                $skip =  ($page == 1) ?  -1: ($num * ($page -1)) ;
                $sort = $request->sort;
                $phone = $request->phone;

                $data = [];
                $result = B2BUtility::getOrderedList($phone, $skip, $num);

                foreach($result as $r) {
                    $data[] = [
                        'id' => $r->id,
                        'phone' => $r->phone,
                        'order_total' => $r->b2b_total,
                        'b2b_total' => $r->order_total,
                        'order_status' => $r->order_status,
                        'location_id' => $r->location_id,
                        'location_name' => B2BUtility::getLocationName($r->location_id),
                        'created_at' => $r->created_at ? $r->created_at  : '' ,
                        'order_id' => $r->order_id,
                        'no_of_query' => $r->no_of_query,
                    ];
                }

                return response()->json(['data' => $data, 'success' => true ]);
            }
        }
    }

    // query list modification
    public function getCustomerQueryLists(Request $request) {


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
                $skip =  ($page == 1) ?  -1: ($num * ($page -1)) ;
                $sort = $request->sort;
                $phone = $request->phone;

                $black_list_query_list = B2BUtility::getQueryStatusList($request->phone, 0);
                $showable_queries_id = B2BUtility::getQueryListWithoutNaAndEpired($black_list_query_list, $request->phone, 0);
                $flag = $sort;

                // skip($skip)->take($limit)
                //  ordered list
                if ($flag == 3) {
                    $result = B2BUtility::getOrderedQueryList($phone, $flag, $skip, $num);
                }

                // not available
                else if ($flag == 1) {

                    $not_available_query_id_list = B2BUtility::getPreviousOrdersNotAvailableList($phone);
                    $not_available_query_another_id_list = B2BUtility::getExistingQueryNotAvailableList($phone);
                    $result = B2BOrderList::getNotAvailableList($phone, $not_available_query_id_list, $not_available_query_another_id_list, $skip, $num);

                }

                // expired
                else if ($flag == 2) {

                    $expired_query_id_list = B2BUtility::getExistingQueryExpiredList($phone);
                    $expired_query_another_id_list = B2BUtility::getPreviousOrderExpiredList($phone);
                    $result = B2BOrderList::getExpiredList($phone, $expired_query_id_list, $expired_query_another_id_list, $skip, $num);
                }

                else {
                    //  default all  =0

                    // $black_list_query_list = DB::table("b2b_customer_query_status")->where("phone", $phone)->select("query_id")->pluck("query_id");
                    $black_list_query_list = B2BUtility::getBlackQueryList($phone);
                    $result = B2BOrderList::getDefaultAll($phone, $black_list_query_list, $skip, $num);


                    Log::info('default-all-list: '. json_encode($result));

                }


                // $result = B2BUtility::getUserQueryList($request->phone, $showable_queries_id, $sort);

                if (count($result) > 0 ) {
                    $data = [];
                    foreach($result as $od) {
                        $data[] = [
                                    'b2b_cust_query_id'  => $od->b2b_cust_query_id,
                                    'query_id' => $od->query_id,
                                    'phone'  => $od->phone,
                                    'location_id'  => $od->location_id,
                                    'total'  => $od->total,
                                    'b2b_total'  => $od->b2b_total,
                                    // 'query_status'  => $od->is_active,
                                    'platform'  => $od->platform ,
                                    'created_at'  => $od->created_at,
                                    'item' => B2BUtility::getOrderedQueryProducs($od->b2b_cust_query_id),
                                    'page' => $page ,
                                    'skip' => $skip
                        ];
                    }

                    return response()->json(['data' => $data , 'msg' => count($data) ? 'Queries found' : 'No Queries found', 'success' => true ]);

                }
                else
                    return response()->json(['data' => [], 'msg' => 'No Queries found', 'success' => true ]);

             }
        }
    }


    public function orderSendToExpiredAndNotAvailableHistory(Request $request) {

        Log::info("orderSendToExpiredAndNotAvailableHistory:: ". json_encode($request->all() ) );

        $user =  trim($request->bearerToken());
        if (!$user) {
             return response()->json(['error'=> 'User not found !', 'success'=>false  ], 401);
        }
        else if( empty($request->phone) ) {
             return response()->json(['error'=> 'Phone number required', 'success'=> false  ], 401);
        }
        else if( empty($request->query_id) ) {
             return response()->json(['error'=> 'Query ID required', 'success'=> false  ], 401);
        }

        else {

            $history_status = $request->history_status;

            // $history_status =1 = expired
            // $history_status= 2 = not available
            try {

                $isExistQueryStatus = DB::table("b2b_customer_query_status")
                    ->where("query_id", $request->query_id)
                    ->count();


                if ($history_status == 1) {
                    $status = 'Expired';
                }

                if ($history_status == 2) {
                    // $status = 'Not Available';
                    $status = 'Processing';
                }

                if ($isExistQueryStatus == 0) {
                    DB::table("b2b_customer_query_status")
                    ->insert([
                        "phone" => $request->phone ,
                        "query_id" => $request->query_id ,
                        "is_history" => $history_status
                    ]);

                    DB::table("b2b_customer_query")
                    ->where("phone", $request->phone )
                    ->where("b2b_cust_query_id", $request->query_id)
                    ->update([
                        "is_active" =>$status
                    ]);

                } else {

                    DB::table("b2b_customer_query_status")
                            ->where("phone", $request->phone )
                            ->where("query_id", $request->query_id)
                            ->update([  "is_history" => $history_status  ]);

                    DB::table("b2b_customer_query")
                        ->where("phone", $request->phone )
                        ->where("b2b_cust_query_id", $request->query_id)
                        ->update([
                            "is_active" =>$status
                        ]);
                }

                return response()->json([ 'msg'=> 'Order sent to history.' , 'success' => true ]);
            }
            catch(\Exception $e) {
                return response()->json([ 'msg'=> 'Order not sent to history . '. $e->getMessage() , 'success' => false ]);
            }

        }
    }



    // order with multiple query
    public function orderMultipleQuery(Request $request) {
        Log::info("orderMultipleQuery>>>>>>>Request::: ". json_encode($request->all() ) );

        $user =  trim($request->bearerToken());
        if (!$user) {
            return response()->json(['error'=> 'User not found !', 'success'=>false  ], 401);
        }
        else if( empty($request->phone) ) {
            return response()->json(['error'=> 'Phone number required', 'success'=> false  ], 401);
        }

        else if ( empty($request->queries) ) {
            return response()->json(['error'=> 'Query id required', 'success'=> false  ], 401);
        }

        else  {
                $eligible_products = [];

                if (isset($request->products)) {
                    echo "block 1 ";
                    foreach($request->products as $p) {
                        echo $p['phone'] ."   ". $p['b2b_cust_query_product_id'] . "   ". $p['customer_query_id'];
                        $f = DB::table("b2b_customer_query_products")

                            ->where("b2b_cust_query_product_id", $p['b2b_cust_query_product_id'])
                            // ->where("phone", $p['phone'])
                            ->where("customer_query_id", $p['customer_query_id'])
                            ->selectRaw("DATEDIFF(date_format(b2b_exp_date, '%Y-%m-%d'), CURRENT_DATE) as tf")
                            ->first();
                        print_r($f);
                        // if ($f) {
                        //     echo " block 2 ";
                        //     $eligible_products[] = $p['b2b_cust_query_product_id'];
                        // }
                    }
                }


                return response()->json(['ep' =>$eligible_products ]);
                // return response()->json(['ep' =>$eligible_products, 'products' =>$request->products ]);


                /*

            // if ($request->phone == "8801755554910" ) {

                $query_id_list_string = $request->queries;
                // $query_id_list_array_data_list = explode(",", $query_id_list_string);
                $query_id_list_array = explode(",", $query_id_list_string);


                // try {
                //     $valid_query_id_list = 0;
                //     foreach($query_id_list_array as $qq) {
                //         // query_id_list_array
                //         $checkIsExpiredItems = DB::table("b2b_customer_query_products")
                //                                 ->selectRaw("(DATEDIFF(date_format(b2b_exp_date, '%Y-%m-%d') , CURRENT_DATE) > 0) as is_expired")
                //                                 ->whereIn("customer_query_id", $qq)
                //                                 ->first();
                //         if ($checkIsExpiredItems->is_expired > 0) {
                //             // $query_id_list_array_new[] = $qq;
                //             $valid_query_id_list = 1;
                //         }
                //     }
                // } catch(\Exception $ee) {
                //     $valid_query_id_list = 0;
                //     Log::info('Customer-all-order-query_id_list_array-error >>> '. $ee->getMessage() );
                // }


                // SELECT DATEDIFF(date_format(b2b_exp_date, '%Y-%m-%d') , CURRENT_DATE) > 0
                // FROM `b2b_customer_query_products` WHERE customer_query_id in (14176 )

                // if ($valid_query_id_list > 0 ) {
                    // for testing
                    // ===========
                    if (DB::table("b2b_orders")->orderBy("created_at", "desc")->count() > 0) {
                        $lastOrderObj = DB::table("b2b_orders")->orderBy("created_at", "desc")->first();
                        $lastOrder = substr($lastOrderObj->order_id, 2, 12);
                    } else {
                        $lastOrder = DB::table("b2b_customer_query")->orderBy("created_at", "desc")->count()  ;
                    }

                    $ordID = 'BO' . ($lastOrder + 1) ;

                    $insert_data = [];
                    $total = 0;
                    $b2b_total = 0;

                    // =========== products ===============
                    try {
                        if (isset($request->products)) {
                            foreach($request->products as $p) {
                                DB::table("b2b_customer_query_products")
                                    ->where("phone", $p['phone'])
                                    ->where("b2b_cust_query_product_id", $p['b2b_cust_query_product_id'])
                                    ->where("customer_query_id", $p['customer_query_id'])
                                    ->update([
                                        "order_id" => $ordID,
                                        "order_qty" => $p['cartQty'],
                                        "is_active" => "Done",
                                        "order_date" => Carbon::now('GMT+6')
                                    ]);



                            }
                        }
                    }
                    catch(\Exception $e) {
                        Log::info("products ::: ". $e->getMessage() );
                    }
                    // =========== products ===============

                    // =========== queries ================
                    try {
                        foreach($query_id_list_array as $q) {

                            $query = DB::table("b2b_customer_query")->where("b2b_cust_query_id", $q )
                                        ->first();

                            $productDetObj = DB::table("b2b_customer_query_products")
                                        ->selectRaw("(b2b_req_price*order_qty) as b2b_subtotal,
                                        (b2b_req_price*b2b_qty) as reg_subtotal")
                                        ->where("customer_query_id", $q )
                                        ->first();

                            if ($productDetObj) {
                                $total += $productDetObj->reg_subtotal;
                                $b2b_total += $productDetObj->b2b_subtotal;
                            } else {
                                $total += $query->total;
                                $b2b_total += $query->b2b_total;
                            }
                            Log::info("productDetObj >>>> ".  json_encode($productDetObj));

                            $insert_data[] = [
                                "order_id" => $ordID,
                                "query_id" => $q ,
                                "query_status" => 'Done',
                                "created_at" => Carbon::now('GMT+6'),
                                "updated_at" => Carbon::now('GMT+6')
                            ];

                            DB::table("b2b_customer_query")
                            ->where("b2b_cust_query_id", $q )
                            ->update([
                                'order_id' => $ordID,
                                'total' => $total,
                                "is_active" => "Done",
                                "updated_at" =>  Carbon::now('GMT+6')
                            ]);


                            // DB::table("b2b_customer_query")
                            // ->where("b2b_cust_query_id", $q )
                            // ->update([
                            //     "updated_at" => Carbon::now(),
                            // ]);

                        }

                        DB::table("b2b_order_details")
                                ->insert($insert_data);
                    } catch(\Exception $e) {
                        Log::info("Queries ::: ". $e->getMessage() );
                    }
                    // =========== queries ================

                    // =========== users =================
                    try {
                        $u = DB::table("b2b_users")
                                ->where("phone", $request->phone)
                                ->first();



                        $insert_data_obj = [
                            "phone" => $request->phone,
                            "order_id" => $ordID,
                            "order_total" => $total,
                            "b2b_total" => $b2b_total,
                            "query_status" => "",
                            "order_status" => 'Done',
                            "platform" => 'app',
                            "location_id" => $u->location_id,
                            "created_at" => Carbon::now('GMT+6'),
                            "updated_at" => Carbon::now('GMT+6')
                        ];

                        DB::table("b2b_orders")
                            ->insert($insert_data_obj);
                    }
                    catch(\Exception $e) {
                        Log::info("users ::: ". $e->getMessage() );
                    }
                    // =========== users =================

                    //============= query notification ==============================
                    try {
                        $template = "query";
                        $body_text = "B2B App New Order: ". $ordID ." has been received. Please review the request.";
                        Log::info($body_text);
                        $subject = "B2B App Query#". $ordID ." received";
                        Common::sendMail(Common::notificationHolderEmail() , null, $body_text,  $subject, Common::notificationHolderName(), $template );
                    } catch(\Exception $e) {
                        Log::info("Query-Error:: ". $e->getMessage());
                    }
                    // ============= query notification ==============================

                    return response()->json([
                        'success' => true ,
                        'msg' => 'Order data saved'
                    ]);
                // }
                // else {
                //     return response()->json([
                //         'success' => true ,
                //         'msg' => 'Order canceled'
                //     ]);
                // }

            // }

            */

        }

    }


}
