<?php

namespace App\Lib;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use JWTAuth;

use App\Lib\B2BLib;


class B2BUtility {


    public static function getBlackQueryList($phone) {
        return DB::table("b2b_customer_query_status")
        ->where("phone", $phone)->select("query_id")->pluck("query_id");
    }


    // ========= expired ========================================
    public static function getPreviousOrderExpiredList($phone) {
        return DB::table("b2b_customer_query_status")
                        ->selectRaw("distinct b2b_customer_query_status.query_id")
                        ->where("b2b_customer_query_status.phone", $phone)
                        ->where("b2b_customer_query_status.is_history", 2)
                        ->pluck("b2b_customer_query_status.query_id");
    }

    public static function getExistingQueryExpiredList($phone) {
        return DB::table("b2b_customer_query_products")
                    ->selectRaw("b2b_customer_query_products.customer_query_id,
                            (CASE
                                WHEN  (DATEDIFF(DATE_FORMAT(b2b_customer_query_products.b2b_exp_date, '%Y-%m-%d') , curdate() ) < 0)  THEN 'Expired'
                                ELSE ''
                            END) AS b2b_exp_status")
                    ->where("b2b_customer_query_products.phone", $phone)
                    ->whereRaw("(DATEDIFF(DATE_FORMAT(b2b_customer_query_products.b2b_exp_date, '%Y-%m-%d') , curdate() ) <  0 )")
                    ->pluck("b2b_customer_query_products.customer_query_id");
    }
    // ========= expired ========================================




    //====================not available=============================
    public static function getPreviousOrdersNotAvailableList($phone) {
        return DB::table("b2b_customer_query_status")
                        ->selectRaw("distinct query_id")
                        ->where("phone", $phone)
                        ->where("is_history", 1)
                        ->pluck("query_id");
    }


    public static function getExistingQueryNotAvailableList($phone) {
        return DB::table("b2b_customer_query_products")
                        ->where("phone", $phone)
                        ->where("b2b_qty",  0)
                     ->pluck("customer_query_id");
    }
    //====================not available=============================



    // =================== ordered =========================================
    public static function getOrderedQueryList($phone, $flag, $skip, $num) {
        $ordered_query_id_list = DB::table("b2b_orders")
                        ->selectRaw("distinct b2b_order_details.query_id")
                        ->leftJoin("b2b_order_details", "b2b_order_details.order_id", "=", "b2b_orders.order_id")
                        ->where("b2b_orders.phone", $phone)
                        ->pluck("b2b_order_details.query_id");

        return  DB::table("b2b_customer_query")
            ->selectRaw("b2b_cust_query_id , phone , query_id, location_id, total,
                b2b_total, is_active, platform, date_format(created_at, '%Y-%m-%d %H:%i:%s') as created_at")
        ->whereIn("b2b_cust_query_id", $ordered_query_id_list)

        ->where("phone", $phone)
        ->orderByRaw("date_format(created_at, '%Y-%m-%d %H:%i:%s') desc")
        ->skip($skip)
        ->take($num)
        ->get();
    }



    public static function getOrderedList($phone, $skip, $num) {
        return DB::table("b2b_orders")
                        ->selectRaw("b2b_orders.id, b2b_orders.phone, b2b_orders.order_total, b2b_orders.b2b_total,
                                    b2b_orders.order_status , b2b_orders.location_id  ,b2b_orders.created_at,
                                    b2b_order_details.order_id, count( b2b_order_details.order_id) as no_of_query")

                        ->leftJoin("b2b_order_details", "b2b_order_details.order_id", "=", "b2b_orders.order_id")
                        ->where("b2b_orders.phone", $phone)
                        ->where("b2b_orders.order_status", "Done")
                        ->groupByRaw("b2b_orders.id, b2b_orders.phone,
                                        b2b_orders.order_total, b2b_orders.b2b_total,
                                        b2b_orders.order_status , b2b_orders.location_id  ,
                                        b2b_orders.created_at, b2b_order_details.order_id")
                        ->orderByRaw("b2b_orders.updated_at desc")
                        ->skip($skip)
                        ->take($num)
                        ->get();

    }



    public static function getSingleOrder($phone, $order_id) {

        $order_query_id_list =  DB::table("b2b_orders")
                        ->leftJoin("b2b_order_details", "b2b_order_details.order_id", "=", "b2b_orders.order_id")
                        ->where("b2b_orders.phone", $phone)
                        ->where("b2b_orders.id", $order_id)
                        ->pluck("b2b_order_details.query_id");

       return  DB::table("b2b_customer_query")
        ->selectRaw("b2b_cust_query_id,query_id, phone,location_id,total,b2b_total,is_active,platform, created_at")
        ->whereIn("b2b_customer_query.b2b_cust_query_id", $order_query_id_list)
        ->get();
    }

    // ===================== ordered =======================================


    public static function getLocationName($location_id) {
        $l = DB::table("b2b_service_locations")->where("id", $location_id)->first();
        if ($l) {
            return $l->location_name;
        } else
        return "";
    }



    public static function getOrderedQueryProducs($query_id)
    {

        try
        {

            $products = DB::table("b2b_customer_query_products")
                    ->selectRaw("b2b_cust_query_product_id ,customer_query_id,
                     phone, product_sku, product_name, item_name,brand_name, qty, reg_price,
                       sug_price, b2b_req_price as b2b_given_price, b2b_qty, b2b_exp_date,
                       is_active as status,
                       (CASE
                            WHEN (is_active='Pending') THEN 'Query for Price'
                            WHEN (is_active='Processing'  ) THEN 'Price Given'
                            WHEN (is_active='Done') THEN 'Order Done'
                            WHEN (is_active='Not Available') THEN 'Not Available'
                            WHEN (is_active='Partialy Done') THEN 'Partialy Done'
                            WHEN (is_active='') THEN null
                            ELSE ''
                        END) AS new_status, order_qty,
                       (CASE
                             WHEN  (DATEDIFF(DATE_FORMAT(b2b_exp_date, '%Y-%m-%d') , curdate() ) < 0)  THEN 'Expired'
                       ELSE ''      END
                        ) AS b2b_exp_status,
                        date_format(order_date, '%Y-%m-%d %H:%i:%s') as order_date,
                        date_format(created_at, '%Y-%m-%d %H:%i:%s') as created_at")
                    ->where("customer_query_id", $query_id)
                    ->orderBy("created_at", "desc")
                ->get();

            $result = [];
            foreach ($products as $p) {

                if ($p->b2b_qty == 0  && $p->b2b_given_price == 0 && $p->status == 'Pending') {
                    $status = 'Pending';
                }
                else if ( ($p->b2b_qty == 0  && $p->b2b_given_price == 0) &&  $p->status != 'Pending') {
                    $status = 'Not Available';
                }
                else {
                    if ( ($p->b2b_qty > 0  && $p->b2b_given_price > 0) && B2BLib::isExpired($p->b2b_exp_date) > 0) {
                        $status = 'Expired';
                    }
                    else {
                        $status  = $p->new_status;
                    }
                }

                $result[] = [
                    'b2b_cust_query_product_id' => $p->b2b_cust_query_product_id,
                    'customer_query_id' => $p->customer_query_id,
                    'phone' => $p->phone,
                    'product_sku' => $p->product_sku,
                    'product_name' => $p->product_name,
                    'item_name' => $p->item_name,
                    'brand_name' => $p->brand_name,
                    'qty' => $p->qty,
                    'order_qty' => $p->order_qty ,
                    'reg_price' => $p->reg_price,
                    'sug_price' => $p->sug_price,
                    'b2b_given_price' => $p->b2b_given_price,
                    'b2b_qty' => $p->b2b_qty,
                    'b2b_exp_date' => $p->b2b_exp_date,
                    'status' => $status
                ];
            }

            return $result;




        }
        catch(\Exception $e) {
            Log::info("getOrderedQueryProducs>>error:: ". $e->getMessage() );
            return [] ;
        }
    }



    public static function getOrderedQueryProducsBackup($query_id)
    {

        try
        {
            return DB::table("b2b_customer_query_products")
                    ->selectRaw("b2b_cust_query_product_id ,
                    customer_query_id,
                     phone, product_sku,
                     product_name, item_name,
                      brand_name, qty, reg_price,
                       sug_price, b2b_req_price as b2b_given_price,
                       b2b_qty, b2b_exp_date,
                       is_active as status,
                       (CASE
                            WHEN (is_active='Pending') THEN 'Query for Price'
                            WHEN (is_active='Processing'  ) THEN 'Price Given'
                            WHEN (is_active='Done') THEN 'Order Done'
                            WHEN (is_active='Partialy Done') THEN 'Partialy Done'
                            WHEN (is_active='') THEN null
                            ELSE ''
                        END) AS new_status,
                       order_qty,
                       (CASE
                             WHEN  (DATEDIFF(DATE_FORMAT(b2b_exp_date, '%Y-%m-%d') , curdate() ) < 0)  THEN 'Expired'
                       ELSE ''
                       END
                        ) AS b2b_exp_status,
                        date_format(order_date, '%Y-%m-%d %H:%i:%s') as order_date,
                        date_format(created_at, '%Y-%m-%d %H:%i:%s') as created_at")
                    ->where("customer_query_id", $query_id)
                ->get();




        }
        catch(\Exception $e) {
            return [] ;
        }
    }



    public static function getOrderedQueries($allowed_query_list_id, $phone, $flag ) {
        try
        {
            if ($flag == 3) {
                $ordered_query_id_list = DB::table("b2b_orders")
                ->selectRaw("distinct b2b_order_details.query_id")
                ->leftJoin("b2b_order_details", "b2b_order_details.order_id", "=", "b2b_orders")
                ->where("b2b_orders.phone", $phone)
                ->pluck("query_id");

                return  DB::table("b2b_customer_query")
                    ->selectRaw("b2b_cust_query_id , phone , query_id, location_id, total,
                        b2b_total, is_active, platform, date_format(created_at, '%Y-%m-%d %H:%i:%s') as created_at")
                ->whereIn("b2b_cust_query_id", $ordered_query_id_list)

                ->where("phone", $phone)
                ->get();
            }

            // not available
            else if ($flag == 1) {
                $not_available_query_id_list = DB::table("b2b_customer_query_status")
                ->selectRaw("distinct b2b_order_details.query_id")
                ->leftJoin("b2b_order_details", "b2b_order_details.order_id", "=", "b2b_orders")
                ->where("b2b_orders.phone", $phone)
                ->pluck("query_id");

                return  DB::table("b2b_customer_query")
                    ->selectRaw("b2b_cust_query_id , phone , query_id, location_id, total,
                        b2b_total, is_active, platform, date_format(created_at, '%Y-%m-%d %H:%i:%s') as created_at")
                ->whereIn("b2b_cust_query_id", $not_available_query_id_list)
                ->where("phone", $phone)
                ->get();
            }

            // expired
            else if ($flag == 2) {
                $expired_query_id_list = DB::table("b2b_customer_query_status")
                ->selectRaw("distinct b2b_order_details.query_id")
                ->leftJoin("b2b_order_details", "b2b_order_details.order_id", "=", "b2b_orders")
                ->where("b2b_orders.phone", $phone)
                ->pluck("query_id");

                return  DB::table("b2b_customer_query")
                    ->selectRaw("b2b_cust_query_id , phone , query_id, location_id, total,
                        b2b_total, is_active, platform, date_format(created_at, '%Y-%m-%d %H:%i:%s') as created_at")
                ->whereIn("b2b_cust_query_id", $expired_query_id_list)
                ->where("phone", $phone)
                ->get();
            }

            else {
                //  default all  =0
                return  DB::table("b2b_customer_query")
                ->selectRaw("b2b_cust_query_id , phone , query_id, location_id, total,
                        b2b_total, is_active, platform, date_format(created_at, '%Y-%m-%d %H:%i:%s') as created_at")
                ->whereIn("b2b_cust_query_id", $allowed_query_list_id)

                ->where("phone", $phone)
                ->get();
            }


            // SELECT DISTINCT query_id FROM `b2b_orders`
            // left join b2b_order_details on (b2b_order_details.order_id=b2b_orders.order_id)
            // where b2b_orders.phone = '8801793497940';



        }
        catch(\Exception $e) {
            return [];
        }
    }

    public static function getQueryStatusList($phone, $flag = 0) {

        if ($flag == 0)  {
            return  DB::table("b2b_customer_query_status")
                ->where("phone", $phone)
                ->pluck("query_id");
        } else
            return  DB::table("b2b_customer_query_status")
            ->where("phone", $phone)
            ->where("is_history", $flag)
            ->pluck("query_id");
    }

    public static function getQueryListWithoutNaAndEpired($black_list_query_list, $phone, $flag) {
        try {
            // flag 1=NA, 2=Expired

            $prev_order_queries_id_list = DB::table("b2b_customer_query")
                        ->selectRaw("b2b_customer_query.b2b_cust_query_id as query_id ")
                        ->where("b2b_customer_query.is_active", "!=", "Done")
                        ->where("b2b_customer_query.phone", $phone)
                        ->pluck("query_id");


            if ($flag == 1 || $flag == 2) {
                return DB::table("b2b_orders")
                   ->leftJoin("b2b_order_details", "b2b_order_details.order_id", "=", "b2b_orders.order_id")
                    ->selectRaw("DISTINCT b2b_order_details.query_id")
                    ->where("b2b_orders.phone", $phone)
                    ->whereIn("b2b_order_details.query_id", $black_list_query_list)
                    ->pluck("query_id");
            }

            else
                return DB::table("b2b_orders")
                    ->leftJoin("b2b_order_details", "b2b_order_details.order_id", "=", "b2b_orders.order_id")
                    ->selectRaw("DISTINCT b2b_order_details.query_id")
                    ->where("b2b_orders.phone", $phone)
                    ->whereNotIn("b2b_order_details.query_id", $prev_order_queries_id_list)
                    ->whereNotIn("b2b_order_details.query_id", $black_list_query_list)
                    ->pluck("query_id");

        }
        catch(\Exception $e) {
            return [];
        }
    }





    public static function getUserQueryList($phone, $showable_queries_id , $flag) {

        try
        {
            $query_list = self::getOrderedQueries($showable_queries_id, $phone, $flag );
            $data = [];
            foreach($query_list as $od) {
                $data[] = [
                            'b2b_cust_query_id'  => $od->b2b_cust_query_id,
                            'phone'  => $od->phone,
                            'location_id'  => $od->location_id,
                            'total'  => $od->total,
                            'b2b_total'  => $od->b2b_total,
                            'query_status'  => $od->is_active,
                            'platform'  => $od->platform ,
                            'created_at'  => $od->created_at,
                            'products' => self::getOrderedQueryProducs($od->b2b_cust_query_id)
                ];
            }

            return $data;
        }
        catch(\Exception $e) {
            return [];
        }

    }


    public static function isExpired($b2b_exp_date_time) {
        $exp_time = Carbon::parse($b2b_exp_date_time);
        $diff = Carbon::now()->gt($exp_time);
        if ($diff) {
            return 1;
        }
        else
        return 0;
    }


    public static function getAppVersion($platform) {
        if (isset($platform)) {
            return DB::table('b2b_app_version_control')
                ->selectRaw("app_version,platform, donwload_url")
                ->where("platform", $platform)
                ->selectRaw("app_version,donwload_url")
                ->first();
        } else
            return DB::table('b2b_app_version_control')
            ->selectRaw("app_version,platform, donwload_url")
            ->whereIn("platform", ['android', 'ios'])
            ->selectRaw("app_version,donwload_url")
            ->get();
    }

}
