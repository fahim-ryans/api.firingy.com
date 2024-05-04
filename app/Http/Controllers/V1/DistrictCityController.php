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


class DistrictCityController extends Controller {

    public function getDistricts() {
        $data['data'] = DB::table("districts")->selectRaw(" concat(id, '') as id ,name as label,bn_name")->where('name', '!=', '')->orderBy('name', 'asc')->get();
        return response()->json($data);
    }


    public function getCities(Request $request) {
         $data['data'] = [];
        try {
            if (isset($request->district) &&  $request->district != '') {

                 $data['data'] = DB::table("b2b_district_city_relations")
                             ->join("districts", "districts.id", "=", "b2b_district_city_relations.district_id")
                             ->join("b2b_cities", "b2b_cities.id", "=", "b2b_district_city_relations.city_id")
                             ->selectRaw("concat(b2b_cities.id,'') as id,b2b_cities.name as label")
                             ->where("b2b_district_city_relations.district_id", $request->district)
                             ->where('b2b_cities.name', '!=', '')->orderBy('b2b_cities.name', 'asc')->get();

                //  DB::table("b2b_cities")->selectRaw("concat(id,'') as id,name as label")->where('name', '!=', '')->orderBy('name', 'asc')->get();
            } else {
                $data['data'] = DB::table("b2b_cities")->selectRaw("concat(id,'') as id,name as label")->where('name', '!=', '')->orderBy('name', 'asc')->get();
            }
        } catch(\Exception $e) {
                $data['data'] = DB::table("b2b_cities")->selectRaw("concat(id,'') as id,name as label")->where('name', '!=', '')->orderBy('name', 'asc')->get();
        }
        return response()->json($data);
    }


    public function sendEmail() {

        try {
            $template = "registration";
            $body_text = "B2B App New Customer: Test registration has been completed. Please approve your account and then you can login the app.";
            $subject = "B2B App Customer: Test Registration Confirmation";

            // Common::sendMail(Common::notificationHolderEmail() , null, $body_text,  $subject, Common::notificationHolderName(), $template );
            Common::sendMailTest( "samer@ryans.com" , null, $body_text,  $subject,"samer", $template);
        } catch(\Exception $e) {
            echo $e->getMessage();
        }

    }


}

?>
