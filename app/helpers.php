<?php

use Illuminate\Support\Facades\Mail;

function sendSMS($mobileNumber, $message)
{
    $user = config('constants.sms_user');
    $pass = config('constants.sms_pass');
    $sid = config('constants.sms_sid');
    $url = config('constants.sms_url');
    $param = "user=$user&pass=$pass&sms[0][0]= $mobileNumber &sms[0][1]=" . urlencode("$message") . "&sms[0][2]=1234567890&sid=$sid";

    if (checkUrl($url)) {
        $crl = curl_init();
        curl_setopt($crl, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($crl, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($crl, CURLOPT_URL, $url);
        curl_setopt($crl, CURLOPT_HEADER, 0);
        curl_setopt($crl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($crl, CURLOPT_POST, 1);
        curl_setopt($crl, CURLOPT_POSTFIELDS, $param);
        $response = curl_exec($crl);
        curl_close($crl);

        return $response;
    } else {
        $to_email = 'ah@ryansplus.com';
        $message = 'server down';
        $cc_email = ['tariqul.ryans@gmail.com', 'lutfor.ryans@gmail.com', 'jannatul.ryans@gmail.com', 'shiplu@ryansplus.com', 'samiron@saatdin.com', 'fahim@ryansplus.com', 'zaman@ryansplus.com'];
        sendMail($to_email, $cc_email, $message, $url);
        return false;
    }
}

function sendMail($to_email, $cc_email, $body_text, $url)
{
    $data = array('body_text' => $body_text, 'url' => $url);

    Mail::send('email.mail', $data, function ($message) use ($to_email, $cc_email) {
        $message->to($to_email, '')
            ->cc($cc_email)
            ->subject('Server down');
        $message->from('info@ryanscomputers.com', 'Ryans Computers Team');
    });
}

function checkUrl($url)
{
    $headers = @get_headers($url);

    // Use condition to check the existence of URL
    if ($headers && strpos($headers[0], '200')) {
        return true;
    } else {
        return false;
    }
}


