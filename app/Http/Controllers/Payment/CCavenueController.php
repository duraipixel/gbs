<?php

namespace App\Http\Controllers\Payment;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Softon\Indipay\Facades\Indipay;  

class CCavenueController extends Controller
{
    public function index(Request $request)
    {
        $parameters = [
            'tid' => date('ymdhis'),
            'order_id' => '123654789',
            'amount' => '1.00',
            'billing_name' => 'Jon Doe',
            'billing_address' => 'annanagar, chennai',
            'billing_city' => 'chennai',
            'billing_state' => 'Tamil Nadu',
            'billing_zip' => '600032',
            'billing_country' => 'India',
            'billing_tel' => '9551706025',
            'billing_email' => 'duraibytes@gmail.com',
            'delivery_name' => 'Chaplin',
            'delivery_address' => 'room no.701 near bus stand',
            'delivery_city' => 'Hyderabad',
            'delivery_state' => 'Tamilnadu',
            'delivery_zip' => '600049',
            'delivery_country' => 'India',
            'delivery_tel' => '9551402025'

        ];

        $order = Indipay::prepare($parameters);
        return Indipay::process($order);

        return view('payment.ccavenue');
    }

    public function ccavRequestHandler(Request $request)
    {
        return view('payment.request_handler');
    }

    public function ccavResponseHandler(Request $request)
    {
        // $workingKey = 'B00B81683DCD0816F8F32551E2C2910B';        //Working Key should be provided here.
        // $encResponse = $_POST["enc_request"];            //This is the response sent by the CCAvenue Server
        // $rcvdString = ccDecrypt($encResponse, $workingKey);        //Crypto Decryption used as per the specified working key.
        // $order_status = "";
        // $decryptValues = explode('&', $rcvdString);
        // $dataSize = sizeof($decryptValues);
        // echo "<center>";

        // for ($i = 0; $i < $dataSize; $i++) {
        //     $information = explode('=', $decryptValues[$i]);
        //     if ($i == 3)    $order_status = $information[1];
        // }

        // if ($order_status === "Success") {
        //     echo "<br>Thank you for shopping with us. Your credit card has been charged and your transaction is successful. We will be shipping your order to you soon.";
        // } else if ($order_status === "Aborted") {
        //     echo "<br>Thank you for shopping with us.We will keep you posted regarding the status of your order through e-mail";
        // } else if ($order_status === "Failure") {
        //     echo "<br>Thank you for shopping with us.However,the transaction has been declined.";
        // } else {
        //     echo "<br>Security Error. Illegal access detected";
        // }

        // echo "<br><br>";

        // echo "<table cellspacing=4 cellpadding=4>";
        // for ($i = 0; $i < $dataSize; $i++) {
        //     $information = explode('=', $decryptValues[$i]);
        //     echo '<tr><td>' . $information[0] . '</td><td>' . $information[1] . '</td></tr>';
        // }

        // echo "</table><br>";
        // echo "</center>";

         // For default Gateway
         $response = Indipay::response($request);
        
         // For Otherthan Default Gateway
         $response = Indipay::gateway('CCAvenue')->response($request);
 
         dd($response);
    }
}
