<?php

return [
    'base_url' => 'https://ryanscomputers.com/',
    'generate_path_initial' => env('GENERATE_PATH_INITIAL', 'laravel/'),
    'generate_inv_path' => env('GENERATE_INV_PATH', 'http://115.127.98.139/api/api_rgo.php'),
    'meta_title' => env('META_TITLE', 'Ryans Computers: Buy laptop, desktop, gaming accessories, camera, etc.'),
    'phone_verify_token_time_expire' => env('phone_verify_token_time_expire', '30000'),
    'phone_verify_token_time_count' => env('phone_verify_token_time_count', '5'),
    
    
    'generate_inv_b2b_path' => 'http://115.127.98.139/api/b2b_app/b2b_items/',
    // 'emi_tenure' => env('emi_tenure',6),
    'emi_tenure' => env('emi_tenure', [12, 9, 6, 3]),
    "sms_api_token" => "Ryansbd-d523645b-c3cd-4715-b36f-443a8040fde3",
    'sms_sid' => 'RYANS',
    'sms_url' => 'https://smsplus.sslwireless.com/api/v3/send-sms',
    
    //====== old credentials
    // 'sms_user' => 'ryansbd',
    // 'sms_pass' => '42V442a<',
    // 'sms_sid' => 'Ryanseng',
    // 'sms_url' => 'http://sms.sslwireless.com/pushapi/dynamic/server.php',
];