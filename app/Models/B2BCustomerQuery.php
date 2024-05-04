<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class B2BCustomerQuery extends Model
{
    protected $table = "b2b_customer_query";
    protected $primaryKey = "b2b_cust_query_id";

    protected $fillable = [        
        'phone',
        'order_id',                 
        'total', 
        'is_active', 
        'note', 
        'query', 
        'updated_by' 
    ];
}
