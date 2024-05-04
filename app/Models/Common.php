<?php
namespace App\Models;

use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class Common {

    public static function sendMailTest($to_email, $cc_email, $body_text,  $subject, $name, $template)
    {

        try {
            $to = $to_email;
            $message = "<html>
                            <head>
                                    <title>". $subject ."</title>
                            </head>
                            <body>
                                <p>Dear ". $name ."</p>
                                <p>". $body_text ."</p>
                            </body>
                            </html>";

                Mail::raw( " Dear ". $name ."  ".$body_text , function ($m) {
                                $m->to("samer@ryans.com")
                                ->subject('B2B Email');
                });

            // Mail::send([], [], function ($message) use ($to_email, $name, $subject, $body_text) {
            //     $message->to($to_email)
            //         ->subject($subject)
            //         ->setBody('Dear '. $name)
            //         ->setBody($body_text , 'text/html');
            // });
        }
        catch(\Exception $e) {
            Log::info("Common::sendMailTest >> ". $e->getMessage() );
        }

        // try {


        //     // $data = ['body_text' => $body_text , 'name' => $name, 'subject' => $subject ];

        //     // Mail::send('email.'. $template  , $data, function ($message) use ($to_email, $cc_email,$subject) {
        //     //     $message->to($to_email, '')
        //     //         // ->cc($cc_email)
        //     //         ->subject($subject);
        //     //     $message->from('ecom@ryans.com', 'Ryans Computers Team');
        //     // });
        // }
        // catch(\Exception $e) {
        //     Log::info("Common::sendMailX >> ". $e->getMessage() );
        // }
    }

    public static function sendMail($to_email, $cc_email, $body_text,  $subject, $name, $template)
    {

        // Log::info("data " . $subject);
        try {
            $to = $to_email;
            $message = "<html>
                            <head>
                                    <title>". $subject ."</title>
                            </head>
                            <body>
                                <p>Dear ". $name ."</p>
                                <p>". $body_text ."</p>
                            </body>
                            </html>";

            Mail::raw( " Dear ". $name ."  ".$body_text , function ($m) {
                                $m->to("b2b@ryans.com")
                                ->subject('B2B Email');
            });

            // Mail::send([], [], function ($message) use ($to_email, $name, $subject, $body_text) {
            //     $message->to($to_email)
            //         ->subject($subject)
            //         ->setBody('Dear '. $name)
            //         ->setBody($body_text , 'text/html');
            // });
        }
        catch(\Exception $e) {
            Log::info("Common::sendMail >> ". $e->getMessage() );
        }

        // try {
        //     $to = $to_email;

        //     $message = "<html>
        //                     <head>
        //                             <title>". $subject ."</title>
        //                     </head>
        //                     <body>
        //                         <p>Dear ". $name ."</p>
        //                         <p>". $body_text ."</p>
        //                     </body>
        //                     </html>";


        //     // Always set content-type when sending HTML email
        //     $headers = "MIME-Version: 1.0" . "\r\n";
        //     $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";


        //     // More headers
        //     $headers .= 'From: <ecom@ryans.com>' . "\r\n";
        //     // $headers .= 'Cc: myboss@example.com' . "\r\n";


        //     mail($to,$subject,$message,$headers);
        // }
        // catch(\Exception $e) {
        //     Log::info("Common::sendMail >> ". $e->getMessage() );
        // }
    }


    public static function notificationHolderEmail() {
      return "b2b@ryans.com";
        // return "shuvoroy@ryans.com";
        // return "samer@ryans.com";
    }

    public static function notificationHolderName() {
        // return "Shuvo Roy";
       return "B2B";
    }

}

