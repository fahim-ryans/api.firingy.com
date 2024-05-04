<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Models\Slider;

class SliderController extends Controller
{

    public function index()
    {
        $sliders = Slider::select('slider_title', 'slider_image_name', 'slider_url')->orderBy('created_at', 'DESC')->where('slider_is_exist', '=', '1')->get();

        foreach ($sliders as $index => $slider) {
            $imageUrl = 'https://www.ryans.com/storage/sliders/' . $slider->slider_image_name;
            $sliders[$index]->imageUrl = $imageUrl;
        }
        return response()->json($sliders);
    }

}
