<?php

namespace App\Http\Controllers\V1;

use App\Models\Customer;
use Illuminate\Http\Request;
use App\Models\CustomerSupport;
use Illuminate\Support\Facades\DB;
use App\Models\CustomerSupportLog;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class CustomerController extends Controller
{
    public function purchaseHistory()
    {
        $customerId = Auth::guard('customer')->id();

        if ($customerId) {
            $inv_customer_id = DB::select('select customer_id_inv,phone from customers WHERE customer_id = ' . $customerId);

            // Get Bills
            /*$getBills = array('appid' => 'rgo', 'call' => 'get_bills', 'customer_id' => $customerId, 'inv_customer_id' => $inv_customer_id[0]->customer_id_inv);
            $customerBills = $this->_invApi($getBills);*/

            // Get Quotation
            /*$getQuotations = array('appid' => 'rgo', 'call' => 'get_quotations', 'customer_id' => $customerId, 'inv_customer_id' => $inv_customer_id[0]->customer_id_inv);
            $customerQuotations = $this->_invApi($getQuotations);*/

            // Get Products
            $getProducts = array('appid' => 'rgo', 'call' => 'get_products_for_customer_app', 'customer_id' => $customerId, 'inv_customer_id' => $inv_customer_id[0]->customer_id_inv, 'mobile'=> $inv_customer_id[0]->phone);
            $customerProducts = $this->_invApi($getProducts);

            $data = array();
            // $data['customerBills'] = $customerBills;
            // $data['customerQuotations'] = $customerQuotations;
            $data['customerProducts'] = $customerProducts;

            return response()->json($data);
        } else {
            return response()->json([
                'status' => 'error',
                'message' => 'Customer ID not found',
            ]);
        }
    }

    public function accountInformation()
    {
        $customerId = Auth::guard('customer')->id();
        if ($customerId) {
            $customer = DB::table('customers')
                ->leftJoin('customer_details', 'customers.customer_id', '=', 'customer_details.customer_id')
                ->leftJoin('groups', 'groups.group_id', '=', 'customers.group_id')
                ->where('customers.customer_id', $customerId)
                ->select('customers.customer_id', 'customer_details.customer_detail_id', 'customer_details.customer_name', 'customer_details.customer_address', 'customers.email', 'customers.code', 'customers.phone', 'customers.group_id', 'groups.group_name')
                ->first();

            $customer->customer_id = strval($customer->customer_id);
            return response()->json([
                "status" => true,
                'message' => 'Customer Information',
                'customerInfo' => $customer
            ]);

        } else {
            return response()->json([
                'status' => 'error',
                'message' => 'Customer ID not found',
            ]);
        }
    }

    private function _invApi($user_data)
    {
        $ch = curl_init(config('constants.generate_inv_path') . '?rand=' . time());
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS, base64_encode(json_encode($user_data)));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        $response = curl_exec($ch);
        curl_close($ch);

        return json_decode(base64_decode($response));
    }

    public function profileUpdate(Request $request): \Illuminate\Http\JsonResponse
    {
        $customerId = Auth::guard('customer')->id();
        $customerName = $request->customer_name;
        $email = $request->email;
        $customerAddress = $request->customer_address;

        // Validate data

        // Check if email already exists
        if ($email) {
            if (Customer::where('email', $email)->where('customer_id', '!=', $customerId)->exists()) {
                return response()->json([
                    "status" => 'error',
                    'message' => 'Email address already used with another user',
                ]);
            }

            // Update email address
            DB::table('customers')->where('customer_id', $customerId)->update(['email' => $email]);
        } else {
            return response()->json([
                "status" => 'error',
                'message' => 'You must enter email address',
            ]);
        }

        // Check if customer details exists
        $this->_createOrUpdateCustomerDetail($customerId, $customerName, $customerAddress);

        return response()->json([
            "status" => 'success',
            'message' => 'customer profile updated successfully',
        ]);
    }

    public function changePassword(Request $request)
    {
        $oldPassword = $request->old_password;
        $newPassword = $request->new_password;
        $confirmPassword = $request->confirm_password;

        // check if any field is empty
        if (!$oldPassword or !$newPassword or !$confirmPassword) {
            return response()->json([
                "status" => 'error',
                'message' => 'Enter all fields',
            ]);
        }

        // check if new and confirm password are matched
        if ($newPassword != $confirmPassword) {
            return response()->json([
                "status" => 'error',
                'message' => 'New password and confirm password not matched',
            ]);
        }

        // check if old password is correct
        $user = Auth::user();
        $checkOldPassword = Hash::check($oldPassword, $user->password);

        if (!$checkOldPassword) {
            return response()->json([
                "status" => 'error',
                'message' => 'Your old password is not correct',
            ]);
        }

        // update password into database
        try {
            Customer::find(Auth::id())->update(['password' => Hash::make($newPassword)]);
            return response()->json([
                "status" => 'success',
                'message' => 'Password changed successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                "status" => 'error',
                'message' => 'Error on updating password ' . $e->getMessage(),
            ]);
        }
    }

    public function customerOrders()
    {
        $customerId = Auth::guard('customer')->id();
        if ($customerId) {
            $orders = DB::table('orders')
                ->leftJoin('customer_details', 'orders.f_customer_id', '=', 'customer_details.customer_id')
                ->leftJoin('orders_deliveries', 'orders_deliveries.order_id', '=', 'orders.order_id')
                ->leftJoin('fsps', 'fsps.id', '=', 'orders_deliveries.fsp_id')
                ->leftJoin('users', 'users.id', '=', 'fsps.user_id')
                ->where('orders.f_customer_id', '=', $customerId)
                ->select(
                    'orders.order_id',
                    'orders.created_at',
                    'orders.grand_total',
                    'orders.order_status',
                    'orders.order_status_time',
                    'orders.payment_expire',
                    'orders_deliveries.customer_confirmation',
                    'customer_details.customer_name',
                    'users.name as fsp_name'
                )
                ->orderBy('orders.order_id', 'desc')
                ->orderBy('orders.created_at', 'desc')
                ->get();

            $previousOrders = DB::select("
                SELECT
                sales_flat_order_grid.entity_id,
                sales_flat_order_grid.increment_id,
                sales_flat_order_grid.billing_name,
                sales_flat_order_grid.shipping_name,
                sales_flat_order_grid.base_grand_total,
                sales_flat_order_grid.grand_total,
                sales_flat_order_grid.status,
                sales_flat_order_grid.created_at,
                sales_flat_order_address.email,
                sales_flat_order_address.telephone
                FROM sales_flat_order_grid
                JOIN sales_flat_order_address on (sales_flat_order_address.parent_id = sales_flat_order_grid.entity_id AND sales_flat_order_address.address_type = 'billing')
                WHERE sales_flat_order_address.email = '" . Auth::guard('customer')->user()->email . "'
                ORDER BY sales_flat_order_grid.created_at DESC
                ");

            return response()->json([
                "status" => true,
                'message' => 'Customer Order Information',
                'orders' => $orders,
                'previousOrders' => $previousOrders
            ]);
        } else {
            return response()->json([
                'status' => 'error',
                'message' => 'Customer ID not found',
            ]);
        }
    }

    public function customerOrderDetails($order_id)
    {
        $customerId = Auth::guard('customer')->id();
        if ($customerId) {
            $order = DB::table('orders')
                ->where('orders.order_id', $order_id)
                ->select(
                    'orders.order_id',
                    'orders.created_at',
                    'orders.updated_at',
                    'order_status',
                    'order_status_time',
                    'payment_expire',
                    'cancel_reason',
                    'cancel_time',
                    'f_customer_id',
                    'currency',
                    'grand_total',
                    'is_store',
                    'point',
                    'special_discount',
                    'shippings.shipping_type_name',
                    'orders_deliveries.customer_confirmation',
                    'users.name as fsp_name',
                    'users.user_avatar as fsp_avatar',
                    'users.email as fsp_email',
                    'fsps.contact_no as fsp_contact',
                    'fsps.detail as fsp_detail'
                )
                ->leftJoin('shippings', 'orders.order_id', '=', 'shippings.order_id')
                ->leftJoin('orders_deliveries', 'orders_deliveries.order_id', '=', 'orders.order_id')
                ->leftJoin('fsps', 'fsps.id', '=', 'orders_deliveries.fsp_id')
                ->leftJoin('users', 'users.id', '=', 'fsps.user_id')
                ->where('f_customer_id', $customerId)
                ->first();

            if ($order) {
                $cancelReasons = DB::table('cancel_reasons')
                    ->orderBy('id', 'asc')->get();
                $ordered_products = $this->_productsByOrderId($order_id);

                foreach ($ordered_products as $i => $ordered_product) {
                    $ordered_products[$i]->product_name = $ordered_product->od_pn ? $ordered_product->od_pn : $ordered_product->product_name;
                }
                return response()->json([
                    "status" => true,
                    'message' => 'Customer Order Information',
                    'ordered_products' => $ordered_products,
                    'order' => $order,
                    'cancelReasons' => $cancelReasons,
                    //'specialDiscountLabel' => $this->specialDiscountLabel(),
                ]);
            } else {
                return response()->json([
                    'status' => 'error',
                    'message' => 'No order details found for ID: ' . $order_id,
                ]);

            }
        } else {
            return response()->json([
                'status' => 'error',
                'message' => 'Customer ID not found',
            ]);
        }
    }

    public function customerPreviousOrderDetails($order_id)
    {
        $customerId = Auth::guard('customer')->id();
        if ($customerId) {
            $customerEmail = DB::select("
            SELECT
            sales_flat_order_grid.entity_id,
            sales_flat_order_address.email
            FROM sales_flat_order_grid
            JOIN sales_flat_order_address on (sales_flat_order_address.parent_id = sales_flat_order_grid.entity_id AND sales_flat_order_address.address_type = 'billing')
            WHERE sales_flat_order_address.email = '" . Auth::user()->email . "'
            AND sales_flat_order_grid.entity_id = " . $order_id . "
            ");

            if ($customerEmail) {
                $products = DB::select("SELECT * from sales_flat_order_item WHERE order_id = $order_id");
                $shippingCost = DB::select("SELECT base_shipping_amount FROM sales_flat_order WHERE entity_id = $order_id");
                return response()->json([
                    "status" => true,
                    'message' => 'Customer Order Information',
                    'products' => $products,
                    'shippingCost' => $shippingCost,
                ]);
            } else {
                return response()->json([
                    'status' => 'error',
                    'message' => 'No order details found for ID: ' . $order_id,
                ]);
            }
        } else {
            return response()->json([
                'status' => 'error',
                'message' => 'Customer ID not found',
            ]);
        }
    }

    private function _productsByOrderId($order_id)
    {
        $customerId = Auth::guard('customer')->id();
        $ordered_products = DB::table('orders')
            ->leftJoin('order_details', 'orders.order_id', '=', 'order_details.f_order_id')
            ->join('products', 'products.product_id', '=', 'order_details.f_product_id')
            ->where('orders.order_id', '=', $order_id)
            ->where('orders.f_customer_id', '=', $customerId)
            ->select(
                'products.product_name',
                'order_details.product_name as od_pn',
                'products.product_code_inv',
                'order_details.unit_price',
                'order_details.quantity',
                'order_details.total_price',
                'order_details.pre_order_text',
                'order_details.special_discount as od_special_discount'
            )
            ->get();

        foreach ($ordered_products as $i => $ordered_product) {
            $ordered_products[$i]->product_name = $ordered_product->od_pn ? $ordered_product->od_pn : $ordered_product->product_name;
        }

        return $ordered_products;
    }

    public function customerSupport(Request $request)
    {
        $serial = $request->ser_no;
        $item = $request->item_name;
        $customerId = Auth::guard('customer')->id();

        Log::info("Customer support submit, customer id " . $customerId);

        if (CustomerSupport::join('customer_support_logs', 'customer_supports.id', '=', 'customer_support_logs.customer_support_id')
            ->where('ser_no', $serial)
            ->where('item_name', $item)
            ->where('status', 'Pending')
            ->where('customer_id', $customerId)
            ->exists()
        ) {
            return response()->json([
                'status' => 'error',
                'message' => 'You already submitted',
            ]);
        }

        try {
            $customerSupport = new CustomerSupport();
            $customerSupport->customer_id = Auth::guard('customer')->id();
            $customerSupport->bill_date = $request->bill_date;
            $customerSupport->bill_no = $request->bill_no;
            $customerSupport->brand_name = $request->brand_name;
            $customerSupport->inv_code = $request->inv_code;
            $customerSupport->item_name = $request->item_name;
            $customerSupport->item_type_name = $request->item_type_name;
            $customerSupport->location = $request->location;
            $customerSupport->operator = $request->operator;
            $customerSupport->description = $request->description;
            $customerSupport->qty = $request->qty;
            $customerSupport->ser_no = $request->ser_no;
            if ($customerSupport->save()) {
                $customerSupportLog = new CustomerSupportLog();
                $customerSupportLog->customer_support_id = $customerSupport->id;
                if ($customerSupportLog->save()) {
                    return response()->json([
                        'status' => 'success',
                        'message' => 'Record inserted successfully',
                    ]);
                }
            }
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e,
            ]);
        }
    }

    protected function _createOrUpdateCustomerDetail($customerId, $customerName, $customerAddress)
    {
        if (DB::table('customer_details')->where('customer_id', $customerId)->exists()) {
            // Update customer detail
            DB::table('customer_details')->where('customer_id', $customerId)
                ->update(['customer_name' => $customerName, 'customer_address' => $customerAddress]);
        } else {
            // Insert customer detail
            DB::table('customer_details')->insert([
                'customer_name' => $customerName,
                'customer_address' => $customerAddress,
                'customer_id' => $customerId,
            ]);
        }
    }
}
