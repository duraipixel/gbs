<?php

namespace App\Http\Controllers;

use App\Models\SmsTemplate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TestController extends Controller
{
    public function index(Request $request) {

        // $number = ['919551706025'];
        // $name   = 'Durairaj';
        // $orderId = 'IOP9090909P';
        // $companyName = 'Musee Musical';
        // $credentials = 'Password is 90909090';
        // $message = "Dear $name, Ref.Id $orderId, Thank you for register with $companyName. Your credentials are $credentials. -MUSEE MUSICAL";
        // sendSMS($number, $message);
        $this->sendSms('order_shipping');
        /***** All sms tempate working , checked with dynamci content from database is done and working **/
        
    }

    public function sendSms($sms_type)
    {
        $info = SmsTemplate::where('sms_type', $sms_type)->first();
        if( isset( $info ) && !empty( $info ) ) {

            $number = ['919551706025'];
            $name   = 'Durairaj';
            $reference_id = 'ORD2015';
            $company_name = $info->company_name;
            $credential = 'email/password';
            $subscribtion_id = '#SUB2022';
            $rupees = 'RS250000';
            $payment_method = 'online razorpay';
            $first_name  = 'Durai';
            $last_name  = 'raj';
            $order_no = 'ORD2013';
            $company_url  = 'https://www.onlinemuseemusical.com/';
            $latest_update = 'Latest Updates';
            $tracking_no = '#um89898990000009';
            $tracking_url = 'https://www.onlinemuseemusical.com/';

            $message = $info->template_content;
            eval("\$message = \"$message\";");

            $params = array(
                'entityid' => $info->peid_no,
                'tempid' => $info->tdlt_no,
                'sid'   => urlencode(current(explode(",",$info->header)))
            );

            sendSMS($number, $message, $params);
        }
    }
}
