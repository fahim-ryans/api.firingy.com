<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class B2BCustomerQueryProduct extends Model
{
    protected $table = "b2b_customer_query_products";
    protected $primaryKey = "b2b_cust_query_product_id";

    protected $fillable = [
      'phone',
      'order_id',
      'product_sku', 
      'product_name', 
      'item_name', 
      'brand_name', 
      'qty', 
      'reg_price', 
      'sug_price', 
      'is_active', 
      'updated_by'
    ];
}
