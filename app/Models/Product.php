<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $primaryKey = 'product_id';

    protected $fillable = [
        'item_type_id', 'sub_item_type_id', 'brand_id', 'category_id', 'product_head', 'product_name', 'product_slug',
        'product_code', 'product_code_value', 'product_code_inv', 'product_short_description', 'product_long_description',
        'product_price1', 'product_price2', 'product_price3', 'product_price4', 'expected_delivery_time',
        'product_meta_title', 'product_meta_keyword', 'product_meta_description',
        'product_is_home', 'desktop_b_type_ids', 'product_rating', 'product_is_upcoming', 'product_upcoming_expire_date',
        'product_upcoming_details', 'product_is_exist', 'status_time', 'created_by', 'updated_by'
    ];
}
