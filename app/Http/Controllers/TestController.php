<?php

namespace App\Http\Controllers;

use App\Mail\TestMail;
use App\Models\GlobalSettings;
use App\Models\Master\EmailTemplate;
use App\Models\Order;
use App\Models\Product\Product;
use App\Models\Product\ProductCategory;
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
    public function index(Request $request)
    {

        // $number = ['919551706025'];
        // $name   = 'Durairaj';
        // $orderId = 'IOP9090909P';
        // $companyName = 'Musee Musical';
        // $credentials = 'durairamyb@mail.com/09876543456';
        // $message = "Dear $name, Ref.Id $orderId, Thank you for register with $companyName. Your credentials are $credentials. -MUSEE MUSICAL";
        // sendSMS($number, $message, []);
        $all_category = ProductCategory::where('status', 'published')->where('parent_id', 0)->get();

        $category = [];
        if (isset($all_category) && !empty($all_category)) {
            foreach ($all_category as $cat_item) {

                // dump( $cat_item->childCategory );
                if (isset($cat_item->childCategory) && !empty($cat_item->childCategory)) {
                    foreach ($cat_item->childCategory as $sub_item) {
                        // dump( $sub_item );
                        // dump( count($sub_item->products ) );
                        if (!isset($category[$cat_item->id])) {
                            $category[$cat_item->id] = array('id' => $cat_item->id, 'name' => $cat_item->name, 'slug' => $cat_item->slug);
                        }
                        if (count($sub_item->products) > 0) {

                            $category[$cat_item->id]['child'][] = array('id' => $sub_item->id, 'name' => $sub_item->name, 'slug' => $sub_item->slug);
                        }
                    }
                }
            }
        }
        $new_menu = [];
        if (!empty($category)) {
            foreach ($category as $key => $value) {

                $new_menu[] = $value;
            }
        }



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
        if (isset($info) && !empty($info)) {

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
                'sid'   => urlencode(current(explode(",", $info->header)))
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
        if (isset($order_info->pickup_store_id) && !empty($order_info->pickup_store_id) && !empty($order_info->pickup_store_details)) {
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
                dump($request);
                $api = new Api($keyId, $keySecret);

                $finalorder = $api->order->fetch($orderdata['order_id']);
                dd($finalorder);
            }
        }

        return  array('success' => $success, 'message' => $error_message);
    }

    function testPaymentStatusTracker()
    {
        $working_key = 'B00B81683DCD0816F8F32551E2C2910B';
        $response = '3601f58b98fb48e3e36112be0bc24a7869c14e5f97f911ddd32b6f9005c1218e5e64d88c473df41b88372004738221fe66ed9d7122a1a86284a16ba5d779434fcd8011a6661b017327eef055071ec1529a7cd01cc7fcab8ac1395c957d1cdae15036806abf6991bea63f524e9cc70a91ff0499e52bd70f5b7e510ab0695ea801e75cf03de70c6568e15e5c84f5f86b6aa6b5f90c6a4dca4e35b9f0aea100151a399caf3775e134a4d7104e3c3557ca389219fa845c2e2b504929302d7e284c7f530f129d23ec41259cb0475d68c8fbd20413ad41b96bf0b836f564cb8d4a11acc6a607e6ae7a194d40d5b48d18e778d624f78a06402d295ea2fb8e69d9d76ea5d8fd4d09b6df6b2bad2c943610b534afedc282ba6ce4082fa8409dc7cf612e269067de8eff012dca54c01425081d75ce11dfd65ae5eb980eedebe2beeffbd7fc56c2e9b6076fd93e9d6c851dc6abd56fd889a079fa5c0b76378b53e6da4a845593ca8a3f199027f77440ea7812352c955eb48d6427a250f2560aa3c3632a9e14c68a75fb09d080565ebb43ab812e4867726d8036878c87585705dd1289beaa74b993a0b9186861ce6477123999fc65abb5808a0647e375d0b0a44e924a12a09988d61246290bca7f89c2bb2c4193982ea87c8f4dbd911e7c1fdd6387afa36362de44df334f930795c2e35c62910e5a4ac025930bc0775d829ba5e0015ee23444b114cd4850e6e3dbafdf6ffe475c3990863952e6658d80af1d8eb14d48004af32085e2ead22b065adef07afbef01cd532c642b1ac4abde264856ce20d2f3d7a425ab43a3c30055cbb952d382a47a072d59a5d34c9a95d65a7eafe928f90e30b5e72331cddedd6a29a422c8d99be54ca2044daba9b033007bd5d0710956b00a0b2a540953d9c50b16e756555ff7714e8af8dd16320696b3cdd79d8697449cff4adf7cf476045cde2753e561d38819fc34b162a41adb506d401d5a012324ee7a6d6325f5bf4b3db8ec0ca58d07c90128348e08aa5fd110b9925be489e1c22781033538ff41d89dbe8d7d6330a99595be3d94bce0f17699d345d5db5e06aa32abae54d2587cefd198214d4d6edb7085502364a89688b7dcec007f83f992cb351a55e2392a652cf99c56997ee0cff79da06f7c9a8f66d993c35588b39e0e2893d53b502b59fb35eb850e38ec1e6b7f65c8a35aa86ce5921c54d50c469bd841e2ac8767417d53ffae5b3dbedb0fd52cfbc27c89b67a3613c2882cfc0970160602b6f6684c4aef2ad10241e83ded96ff76975c04b360bdecac49e732531cfe86019289192df519d5ab8525c351ee6ca3f88a3076c81f45671596418eed2c55567bbd65526d5a021d13afaa4e987796d2f3c631c944f7c510d149403fcc91703aca032f0db28cd1f781e9059bb10ca1766d9356058120e3f81ecd446bc8d828c63f2192687c9cd9ccb363fd40536d726abb59536eb5f22b9d8341f4316425b9481d7689f275cb66c6f41b0cb87608e9636ca69d9783bee227e7532b73e4738f1373712c61f1f3929754a8debe2baaaa9643048669713da34675657112798744be0487c7eb3ef2773abacc6f69dbf20f8b1d1d56b54ca221919061365ba2c2a3222a800d62d8a7d683241f9a032f5e5662ac2fad7be6050ddaf5f8af216c65e83a1d647e972e67302f95379c60e792c6c0509f211923912f9c10ecc0d55776ef7a1df72f4950ed50c6d7de0688310b1ab3e5905da8f8968db91ebd580437f7014b0d433e4768fba0f99b01e04850bf0b63d12d83904780e4cea5c3bb233f639860c1d9bcbdf71528a27fef4298529f387b2ba0d0447e5aef0d2725a4471db599a3f0a843655f02402f5c6bd4dc4f216a09abac63';

        $return = $this->statusDecrypt(trim($response), $working_key);
        dump( $return );
        dd( json_decode( $return ));

    }
     
    function statusDecrypt($encryptedText, $key)
    {
        $key = $this->statusHextobin(md5($key));
        $initVector = pack("C*", 0x00, 0x01, 0x02, 0x03, 0x04, 0x05, 0x06, 0x07, 0x08, 0x09, 0x0a, 0x0b, 0x0c, 0x0d, 0x0e, 0x0f);
        $encryptedText = $this->statusHextobin($encryptedText);
        $decryptedText = openssl_decrypt($encryptedText, 'AES-128-CBC', $key, OPENSSL_RAW_DATA, $initVector);
        return $decryptedText;
    }

    function status_pkcs5_pad($plainText, $blockSize)
    {
        $pad = $blockSize - (strlen($plainText) % $blockSize);
        return $plainText . str_repeat(chr($pad), $pad);
    }

    //********** Hexadecimal to Binary function for php 4.0 version ********

    function statusHextobin($hexString)
    {
        $length = strlen($hexString);
        $binString = "";
        $count = 0;
        while ($count < $length) {
            $subString = substr($hexString, $count, 2);
            $packedString = pack("H*", $subString);
            if ($count == 0) {
                $binString = $packedString;
            } else {
                $binString .= $packedString;
            }

            $count += 2;
        }
        return $binString;
    }
}
