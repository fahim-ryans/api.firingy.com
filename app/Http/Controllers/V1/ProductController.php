<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function trending()
    {
        return 'trending products';
    }

    public function featured()
    {
        return 'featured products';
    }
}
