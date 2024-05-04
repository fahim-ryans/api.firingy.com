<?php

namespace App\Http\Controllers\V1;

use App\Models\CustomerSupport;
use App\Models\CustomerSupportLog;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;

class CustomerClaimController extends Controller
{
    public function customerClaims(): \Illuminate\Http\JsonResponse
    {
        $data = array();
        $customerId = Auth::guard('customer')->id();

        // Getting customer supports only
        $customerSupports = CustomerSupport::select(
            'customer_supports.id',
            'customer_supports.bill_date',
            'customer_supports.item_name as item',
            'customer_supports.item_type_name as item_type',
            'customer_supports.description',
            'customer_supports.ser_no',
            'customer_supports.inv_code',
            'customer_supports.created_at'
        )
            ->where('customer_supports.customer_id', '=', $customerId)
            ->orderBy('customer_supports.id', 'desc')
            ->get();

        // Getting customer support logs and prepare the data
        foreach ($customerSupports as $key => $customerSupport) {
            $customerSupportLogs = CustomerSupportLog::select(
                'ryans_response', 'action_taken',
                'findings_by_engineer', 'status',
                'created_at'
            )->where('customer_support_id', $customerSupport->id)
                ->where(function ($query) {
                    $query->orWhereNotNull('ryans_response')
                        ->orWhereNotNull('action_taken')
                        ->orWhereNotNull('findings_by_engineer');
                })
                ->orderBy('id', 'desc')
                ->get();

            $data[$key]['claim'] = $customerSupport;
            $data[$key]['logs'] = $customerSupportLogs;
        }

        // Sending data
        if ($data) {
            return response()->json([
                'status' => 'success',
                'data' => $data,
            ]);
        } else {
            return response()->json([
                'status' => 'error',
                'data' => 'No customer claims found',
            ]);
        }
    }
}
