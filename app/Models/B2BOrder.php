<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class B2BOrder extends Model
{
    protected $table = "b2b_orders";
    protected $primaryKey = "id";

    protected $fillable = [
        'phone',
        'order_id',
        'order_total',
        'b2b_total',
        'query_status',
        'order_status',
        'platform',
        'location_id'
    ];
}
