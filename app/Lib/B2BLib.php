<?php

namespace App\Lib;

use App\Models\Product;
use App\Models\Item_type;
use App\Models\Brand;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;


class B2BLib {



    public static function allConfirmationTexts($index) {
        $responseMessages = [
            'user_validation'                           => 'User not found!',
            'country_code_validation'                   => 'Country Code is required',
            'name_validation'                           => 'Name required!',

            'phone_validation'                          => 'Phone number required',
            'phone_reg_validation'                      => 'Phone number is not registered',

            'phone_length_10_validation'                => 'Mobile number must be 10 digit',
            'phone_length_13_validation'                => 'Mobile number maximum 13 digit',
            'phone_format_validation'                   => 'Mobile number numbers only',


            'password_validation'                       => 'Password is required',
            'password_length_validation'                => 'Password minimum 6 character',
            'password_confirm_password_mismatched'      => 'User password and confirm password not matched!',
            'incorrect_password_validation'             => 'Enter correct password',
            'incorrect_phone_validation'                => 'Enter correct phone number',


            'email_validation'                          => 'Email is required',
            'incorrect_email_validation'                => 'Please enter valid email address',
            'organization_validation'                   => 'Organization name is required',



            'query_id_validation'                       => 'Query id required',
            'product_validation'                        => 'Product not found',
            'address_validation'                        => 'Address required',
            'order_id_validation'                       => 'Order ID required',


            'account_approved_validation'               => 'Account is not approved',
            'order_send_to_history_validation'          => 'Order not sent to history .',

            'order_detail_not_found'                    => 'Order Detail no data found',

            'reg_is_pending_submit'                     => 'Approval is pending.Contact: +8801755513901 for details',
            'reg_is_reject_submit'                      => 'Account is pending.Contact: +8801755513901 for details',

            'query_submit'                              => 'Request saved successfully',
            'profile_update_submit'                     => 'Thank you for submission, admin will review and update',
            'multiple_or_single_query_submit'           => 'Order data saved',
            'order_send_to_history_submit'              => 'Order sent to history.',

            'reg_success_submit'                        => 'Registered successfuly',
            'reg_failed_submit'                         => 'Registered failed'

        ];
        return $responseMessages[$index];
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

    public static function getSpecificProductDetail($productID)
    {
        return Product::where('product_id', $productID)->first();
    }

    public static function getSpecificItemTypeDetail($item_type_id)
    {
        return Item_type::where('item_type_id', $item_type_id)->first();
    }

    public static function getSpecificBrandDetail($brand_id)
    {
        return Brand::where('brand_id', $brand_id)->first();
    }

    public static function customerQueryProductEntry($product, $item_type, $brand, $phone, $ordID, $orderID)
    {
        DB::table('b2b_customer_query_products')
            ->insert([
                'phone'        => $phone,
                'new_order_id' => $ordID,
                'customer_query_id' => $orderID,
                'product_sku'  => $product->product_code_inv ,
                'product_name' => $product->product_name,
                'item_name'    => $item_type->item_type_name,
                'brand_name'   => $brand->brand_name,
                'reg_price'    => $product->product_price1,
                'sug_price'    => $product->product_price2,
                'qty'          => 1,
                'is_active'    => 'Pending',
                'created_at'   => Carbon::now()
            ]);

        return $pid = DB::getPdo()->lastInsertId();
    }

    public static function customerQueryEntry($ordID, $orderID, $phone, $totalAmount, $pid)
    {
        DB::table('b2b_customer_query')
                    ->insert([
                        'query_id'   => $ordID,
                        'phone'      => $phone,
                        'order_id'   => $ordID,
                        'total'      => $totalAmount,
                        'query'      => "Product Request From Website",
                        'is_active'  => 'Pending',
                        'platform'  => 'temp',
                        'created_at' => Carbon::now()
                    ]);

        $qid = DB::getPdo()->lastInsertId();
        DB::table('b2b_customer_query_products')
                    ->where("phone", $phone)
                    ->where("b2b_cust_query_product_id", $pid)
                    ->update([
                        'customer_query_id' => $qid
                    ]);


        DB::table('b2b_customer_query')->where( 'phone', $phone)
                    ->where("b2b_cust_query_id", $qid)
                    // ->where("order_id", $ordID)
                    ->update([
                        // 'customer_query_id' => $qid,
                        'total' => $totalAmount,
                    ]);

    }

    public static function getCustomerQueryTotal($phone, $pid) {
        return DB::table('b2b_customer_query_products')
                            ->selectRaw("sum(reg_price*qty) as total")
                            ->where("phone", $phone)
                            ->where("b2b_cust_query_product_id", $pid)
                            ->get();
    }

}

?>
