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
use JWTAuth;




class B2BCustomerSearchController extends Controller {


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
                                 ->pluck("product_code_inv");

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


}
