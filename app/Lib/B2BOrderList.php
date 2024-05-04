<?php

namespace App\Lib;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use JWTAuth;

use App\Lib\B2BUtility;

// history_status  ( 1 = Not Available, 2 = Expired, 3 = Ordered, 0 = Default All)

class B2BOrderList {

    public static function getNotAvailableList($phone, $not_available_query_id_list, $not_available_query_another_id_list, $skip, $num) {

        $prev_not_available_query_id_list =  DB::table("b2b_customer_query_status")
                                ->select("query_id")
                                ->where("is_history", 1)
                                ->where("phone", $phone)
                                ->pluck("query_id");

        $prev_exp_query_id_list = DB::table("b2b_customer_query_status")
                                ->select("query_id")
                                ->where("is_history", 2)
                                ->where("phone", $phone)
                                ->pluck("query_id");

        return DB::table("b2b_customer_query")
                    ->selectRaw("b2b_cust_query_id , phone , query_id, location_id, total,
                        b2b_total, is_active, platform,
                        date_format(created_at, '%Y-%m-%d %H:%i:%s') as created_at")
                ->whereIn("b2b_cust_query_id", $prev_not_available_query_id_list)
                ->whereNotIn("b2b_cust_query_id", $prev_exp_query_id_list)

                ->where("phone", $phone)
                ->orderBy("b2b_cust_query_id", "desc")
                ->orderBy("created_at", "desc")
                ->skip($skip)->take($num)
                ->get();
    }


    public static function getExpiredList($phone, $expired_query_id_list, $expired_query_another_id_list, $skip, $num) {
        $prev_exp_query_id_list = DB::table("b2b_customer_query_status")
                                    ->select("query_id")
                                    ->where("is_history", 2)
                                    ->where("phone", $phone)
                                    ->pluck("query_id");

        $prev_na_query_id_list = DB::table("b2b_customer_query_status")
                                    ->select("query_id")
                                    ->where("is_history", 1)
                                    ->where("phone", $phone)
                                    ->pluck("query_id");

        return DB::table("b2b_customer_query")
                ->selectRaw("b2b_cust_query_id , phone , query_id, location_id, total,
                    b2b_total, is_active, platform, date_format(created_at, '%Y-%m-%d %H:%i:%s') as created_at")

                ->whereIn("b2b_cust_query_id", $prev_exp_query_id_list)
                ->whereNotIn("b2b_cust_query_id", $prev_na_query_id_list)

                ->where("phone", $phone)
                ->orderBy("b2b_cust_query_id", "desc")
                ->orderBy("created_at", "desc")
                ->skip($skip)
                ->take($num)
                ->get();
    }


    public static function getDefaultAll($phone, $black_list_query_list, $skip, $num) {

        $prev_orders_list = DB::table("b2b_orders")
                                ->leftJoin("b2b_order_details", "b2b_order_details.order_id", "=", "b2b_orders.order_id")
                                ->select("b2b_order_details.query_id")
                                ->where("b2b_orders.phone", $phone)
                                ->whereNotNull("b2b_order_details.query_id")
                                ->pluck("b2b_order_details.query_id");


        $prev_send_to_history_list = DB::table("b2b_customer_query_status")
                                        ->where("phone", $phone)
                                        ->whereNotNull("query_id")
                                        ->pluck("query_id");

        // Log::info("getDefaultAll >> ". $phone);
        // Log::info("prev_orders_list >> ". json_encode($prev_orders_list));
        // Log::info("prev_send_to_history_list >> ". json_encode($prev_send_to_history_list));
        // Log::info("num >> ". $num);
        // Log::info("skip >> ". $skip);


        return  DB::table("b2b_customer_query")
                    ->selectRaw("b2b_cust_query_id , phone , query_id, location_id, total,
                            b2b_total, is_active, platform,
                            date_format(created_at, '%Y-%m-%d %H:%i:%s') as created_at")

                    ->whereNotIn("is_active", ["Done", "Partialy Done"])
                    ->whereNotIn("b2b_cust_query_id", $prev_orders_list)
                    ->whereNotIn("b2b_cust_query_id", $prev_send_to_history_list)

                    // ->orderBy("b2b_cust_query_id", "desc")
                    ->orderBy("created_at", "desc")
                    ->where("phone", $phone)
                    ->skip($skip)->take($num)
                    ->get();
    }


}

?>
