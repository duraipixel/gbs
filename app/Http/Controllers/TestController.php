<?php

namespace App\Http\Controllers;

use App\Mail\TestMail;
use App\Models\GlobalSettings;
use App\Models\Master\EmailTemplate;
use App\Models\Order;
use App\Models\SmsTemplate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use PDF;
use Mail;
use Razorpay\Api\Api;
use Exception;

class TestController extends Controller
{
    public function index(Request $request) {

        $number = ['919551706025'];
        $name   = 'Durairaj';
        $orderId = 'IOP9090909P';
        $companyName = 'Musee Musical';
        $credentials = 'durairamyb@mail.com/09876543456';
        $message = "Dear $name, Ref.Id $orderId, Thank you for register with $companyName. Your credentials are $credentials. -MUSEE MUSICAL";
        sendSMS($number, $message, []);
        
        // $response = Http::post('https://apiv2.shiprocket.in/v1/external/auth/login',[
        //     'header' => 'Content-Type: application/json',
        //     'email' => 'duraibytes@gmail.com',
        //     'password' => 'Pixel@2022'
        // ]);

        // dd( $response );
        
    }

    public function sendSms($sms_type, $details = [])
    {
        $info = SmsTemplate::where('sms_type', $sms_type)->first();
        if( isset( $info ) && !empty( $info ) ) {

            $number = ['919551706025'];
            $details = array(
                'name' => 'durairja',
                'reference_id' => '88978979',
                'company_name' => env('APP_NAME'),
                'login_details' => 'loginId:durairamyb@mail.com,password:09876543456',
                'mobile_no' => ['919551706025']
            );
            // $name   = 'Durairaj';
            // $reference_id = 'ORD2015';
            // $company_name = $info->company_name;
            // $credential = 'email/password';
            // $subscribtion_id = '#SUB2022';
            // $rupees = 'RS250000';
            // $payment_method = 'online razorpay';
            // $first_name  = 'Durai';
            // $last_name  = 'raj';
            // $order_no = 'ORD2013';
            // $company_url  = 'https://www.onlinemuseemusical.com/';
            // $latest_update = 'Latest Updates';
            // $tracking_no = '#um89898990000009';
            // $tracking_url = 'https://www.onlinemuseemusical.com/';

            $templateMessage = $info->template_content;
            $templateMessage = str_replace("{", "", addslashes($templateMessage));
            $templateMessage = str_replace("}", "", $templateMessage);
            
            extract($details);
            
            eval("\$templateMessage = \"$templateMessage\";");

            $params = array(
                'entityid' => $info->peid_no,
                'tempid' => $info->tdlt_no,
                'sid'   => urlencode(current(explode(",",$info->header)))
            );

            sendSMS($number, $templateMessage, $params);
        }
    }

    public function invoiceSample(Request $request)
    {
        $info = 'teste';
        
        $order_info = Order::find(5);
        $globalInfo = GlobalSettings::first();
        // $pdf = PDF::loadView('platform.invoice.index', compact('order_info', 'globalInfo'));    
        // Storage::put('public/invoice_order/'.$order_info->order_no.'.pdf', $pdf->output());
        $pickup_details = [];
        if( isset( $order_info->pickup_store_id ) && !empty( $order_info->pickup_store_id) && !empty($order_info->pickup_store_details )) {
            $pickup = unserialize($order_info->pickup_store_details);
            
            $pickup_details = $pickup;
        }
        
        $pdf = PDF::loadView('platform.invoice.index', compact('order_info', 'globalInfo', 'pickup_details'))->setPaper('a4', 'landscape');
        return $pdf->stream('test.pdf');
    }

    public function sendMail()
    {
        $email = 'durairaj.pixel@gmail.com';
   
        $mailData = [
            'title' => 'Demo Email',
            'url' => 'https://www.positronx.io'
        ];
        $data = 'Durairaj mail is testing';
  
        Mail::to($email)->send(new TestMail($data, 'Test Mail From Durariaj'));
   
        return response()->json([
            'message' => 'Email has been sent.'
        ]);

        // $emailTemplate = EmailTemplate::select('email_templates.*')
        //                         ->join('sub_categories', 'sub_categories.id', '=', 'email_templates.type_id')
        //                         ->where('sub_categories.slug', 'new-registration')->first();
        
        // $globalInfo = GlobalSettings::first();
        

        // $extract = array(
        //                 'name' => 'Durairaj', 
        //                 'regards' => $globalInfo->site_name, 
        //                 'company_website' => '',
        //                 'company_mobile_no' => $globalInfo->site_mobile_no,
        //                 'company_address' => $globalInfo->address 
        //             );
        // $templateMessage = $emailTemplate->message;
        // $templateMessage = str_replace("{","",addslashes($templateMessage));
        // $templateMessage = str_replace("}","",$templateMessage);
        // extract($extract);
        // eval("\$templateMessage = \"$templateMessage\";");

        // $body = [
        //     'content' => $templateMessage,
        //     'title' => $emailTemplate->title
        // ];
        // $send_mail = new TestMail($templateMessage, $emailTemplate->title);
        // // return $send_mail->render();
        // Mail::to("durairaj.pixel@gmail.com")->send($send_mail);
        
    }

    public function payment()
    {
        
        $keyId = env('RAZORPAY_KEY');
        $keySecret = env('RAZORPAY_SECRET');

        $order_id = 'ORDE890980';
        $pay_amount = 1;

        $shipping_address = array(
                                'name' => 'durairaj',
                                'email' => 'durairaj.pixel@gmail.com',
                                'mobile_no' => '9551706025'
                            );
        try {

            $api = new Api($keyId, $keySecret);
            $orderData = [
                'receipt'         => $order_id,
                'amount'          => $pay_amount * 100,
                'currency'        => "INR",
                'payment_capture' => 1 // auto capture
            ];

            $razorpayOrder = $api->order->create($orderData);
            $razorpayOrderId = $razorpayOrder['id'];
            
            $amount = $orderData['amount'];
            $displayCurrency        = "INR";
            $data = [
                "key"               => $keyId,
                "amount"            => round($amount),
                "currency"          => "INR",
                "name"              => 'GBS ',
                "image"             => asset(gSetting('logo')),
                "description"       => "Secure Payment",
                "prefill"           => [
                    "name"              => $shipping_address['name'],
                    "email"             => $shipping_address['email'],
                    "contact"           => $shipping_address['mobile_no'],
                ],
                "notes"             => [
                    "address"           => "",
                    "merchant_order_id" => $order_id,
                ],
                "theme"             => [
                    "color"             => "#F37254"
                ],
                "order_id"          => $razorpayOrderId,
            ];
            
            return view('test.razor_pay', ['data' => $data]);
        } catch (Exception $e) {
            dd($e);
        }

    }

    public function verifySignature(Request $request)
    {

        $keyId = env('RAZORPAY_KEY');
        $keySecret = env('RAZORPAY_SECRET');

        $customer_id = $request->customer_id;
        dd($request);
        $razor_response = $request->razor_response;
        $status = $request->status;

        $success = true;
        $error_message = "Payment Success";

        if (isset($razor_response['razorpay_payment_id']) && empty($razor_response['razorpay_payment_id']) === false) {
            $razorpay_order_id = $razor_response['razorpay_order_id'];
            $razorpay_signature = $razor_response['razorpay_signature'];
            // $razorpay_order_id = session()->get('razorpay_order_id');

            $api = new Api($keyId, $keySecret);
            $finalorder = $api->order->fetch($razorpay_order_id);

            try {
                $attributes = array(
                    'razorpay_order_id' => $razorpay_order_id,
                    'razorpay_payment_id' => $razor_response['razorpay_payment_id'],
                    'razorpay_signature' => $razor_response['razorpay_signature']
                );

                $api->utility->verifyPaymentSignature($attributes);
            } catch (SignatureVerificationError $e) {
                $success = false;
                $error_message = 'Razorpay Error : ' . $e->getMessage();
            }

           
        } else {
            $success = false;
            $error_message = 'Payment Failed';

            if (isset($request->razor_response['error']) && !empty($request->razor_response['error'])) {

                $orderdata = $request->razor_response['error']['metadata'];
                $razorpay_payment_id = $orderdata['payment_id'];
                $razorpay_order_id = $orderdata['order_id'];
                dump( $request );
                $api = new Api($keyId, $keySecret);

                $finalorder = $api->order->fetch($orderdata['order_id']);
                dd( $finalorder );

                
            }
        }

        return  array('success' => $success, 'message' => $error_message);
    }
}
