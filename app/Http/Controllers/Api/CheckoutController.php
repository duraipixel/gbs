<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\OrderMail;
use App\Models\Cart;
use App\Models\GlobalSettings;
use App\Models\Master\EmailTemplate;
use App\Models\Master\OrderStatus;
use App\Models\Offers\Coupons;
use App\Models\Order;
use App\Models\OrderHistory;
use App\Models\OrderProduct;
use App\Models\OrderProductWarranty;
use App\Models\Payment;
use App\Models\Product\OrderProductAddon;
use App\Models\Product\Product;
use App\Models\ShippingCharge;
use App\Models\Warranty;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Razorpay\Api\Api;
use PDF;
use Mail;

class CheckoutController extends Controller
{
    public function proceedCheckout(Request $request)
    {

        $keyId = env('RAZORPAY_KEY');
        $keySecret = env('RAZORPAY_SECRET');

        $checkout_infomation = json_decode($request->checkout_infomation);
        // dd( $checkout_infomation );
        // dump( $checkout_infomation->checkout_data );
        // dd( $checkout_infomation->cart_items );
        /***
         * Check order product is out of stock before proceed, if yes remove from cart and notify user
         * 1.insert in order table with status init
         * 2.INSERT IN Order Products
         * 
         */
        $customer_id            = $request->customer_id;
        $order_status           = OrderStatus::where('status', 'published')->where('order', 1)->first();
        $shipping_method        = $checkout_infomation->shipping_method;

        $checkout_data          = $checkout_infomation->checkout_data;
        $cart_items             = $checkout_infomation->cart_items;
        $shipping_address       = $checkout_infomation->shipping_address;
        $billing_address        = $checkout_infomation->billing_address;
        $coupon_data            = $checkout_infomation->coupon_data;
        $pickup_store_address   = $checkout_infomation->pickup_store_address;

        $coupon_details = '';
        $coupon_code = '';
        $coupon_amount = 0;
        $coupon_id = 0;
        if (isset($coupon_data) && !empty($coupon_data)) {
            $coupon_code = $coupon_data->coupon_code;
            $coupon_id = $coupon_data->coupon_id;
            $coupon_amount = $coupon_data->coupon_amount;
            $coupon_details = serialize($coupon_data);
        }

        $pickup_address_details = '';
        if (isset($pickup_store_address) && !empty($pickup_store_address)) {
            $pickup_address_details = serialize($pickup_store_address);
        }

        #check product is out of stock
        $errors                 = [];
        if (isset($cart_items) && !empty($cart_items)) {
            foreach ($cart_items as $citem) {
                $product_id = $citem->id;
                $product_info = Product::find($product_id);
                if ($product_info->quantity < $citem->quantity) {
                    $errors[]     = $citem->product_name . ' is out of stock, Product will be removed from cart.Please choose another';
                    $response['error'] = $errors;
                }
            }
        }
        if (!$shipping_method) {
            $message     = 'Shipping Method not selected';
            $error = 1;
            $response['error'] = $error;
            $response['message'] = $message;
        }
        if (!empty($errors)) {

            $error = 1;
            $response['error'] = $error;
            $response['message'] = implode(',', $errors);

            return $response;
        }

        $checkout_total = str_replace(',', '', $checkout_data->total);
        $pay_amount  = filter_var($checkout_total, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);


        $order_ins['customer_id'] = $customer_id;
        $order_ins['customer_id'] = $customer_id;
        $order_ins['order_no'] = getOrderNo();


        $order_ins['amount'] = $pay_amount;
        $order_ins['tax_amount'] = $checkout_data->tax_total ? str_replace(',', '', $checkout_data->tax_total) : 0;
        $order_ins['tax_percentage'] = $checkout_data->tax_percentage ?? 0;
        $order_ins['shipping_amount'] = $checkout_data->shipping_charge ?? 0;

        $order_ins['coupon_amount'] = $coupon_amount ?? 0;
        $order_ins['coupon_code'] = $coupon_code ?? '';
        $order_ins['coupon_details'] = $coupon_details ?? '';
        $order_ins['sub_total'] = $checkout_data->product_tax_exclusive_total_without_format;
        $order_ins['description'] = '';
        $order_ins['order_status_id'] = $order_status->id;
        $order_ins['status'] = 'pending';
        $order_ins['pickup_store_details'] = $pickup_address_details;

        $order_ins['billing_name'] = $billing_address->name;
        $order_ins['billing_email'] = $billing_address->email;
        $order_ins['billing_mobile_no'] = $billing_address->mobile_no;
        $order_ins['billing_address_line1'] = $billing_address->address_line1;
        $order_ins['billing_address_line2'] = $billing_address->address_line2 ?? null;
        $order_ins['billing_landmark'] = $billing_address->landmark ?? null;
        $order_ins['billing_country'] = $billing_address->country ?? null;
        $order_ins['billing_post_code'] = $billing_address->post_code ?? null;
        $order_ins['billing_state'] = $billing_address->state ?? null;
        $order_ins['billing_city'] = $billing_address->city ?? null;

        $order_ins['shipping_name'] = $shipping_address->name ?? $billing_address->name;
        $order_ins['shipping_email'] = $shipping_address->email ?? $billing_address->email;
        $order_ins['shipping_mobile_no'] = $shipping_address->mobile_no ?? $billing_address->mobile_no;
        $order_ins['shipping_address_line1'] = $shipping_address->address_line1 ?? $billing_address->address_line1;
        $order_ins['shipping_address_line2'] = $shipping_address->address_line2 ?? $billing_address->address_line2 ?? null;
        $order_ins['shipping_landmark'] = $shipping_address->landmark ?? $billing_address->landmark ?? null;
        $order_ins['shipping_country'] = $shipping_address->country ?? $billing_address->country ?? null;
        $order_ins['shipping_post_code'] = $shipping_address->post_code ?? $billing_address->post_code;
        $order_ins['shipping_state'] = $shipping_address->state ?? $billing_address->state ?? null;
        $order_ins['shipping_city'] = $shipping_address->city ?? $billing_address->city ?? null;

        if (isset($shipping_method) && $shipping_method != 'PICKUP_FROM_STORE' && isset($shipping_address) && !empty($shipping_address)) {

            $shipping_type_info = ShippingCharge::find($checkout_infomation->standard_shipping_charge_id);

            $order_ins['shipping_options'] = $checkout_infomation->standard_shipping_charge_id ?? 0;
            if ($shipping_type_info) {
                $order_ins['shipping_type'] = $shipping_type_info->shipping_title ?? 'Free';
            }
        } else {

            $order_ins['pickup_store_id'] = $checkout_infomation->pickup_store_id;
        }

        $order_info = Order::create($order_ins);
        $order_id = $order_info->id;

        if (isset($cart_items) && !empty($cart_items)) {
            foreach ($cart_items as $item) {
                $product_info = Product::find($item->id);

                $items_ins['order_id'] = $order_id;
                $items_ins['product_id'] = $item->id;
                $items_ins['product_name'] = $item->product_name;
                $items_ins['image'] = $item->image;
                $items_ins['hsn_code'] = $item->hsn_no;
                $items_ins['sku'] = $item->sku;
                $items_ins['quantity'] = $item->quantity;
                $items_ins['price'] = $item->price;
                $items_ins['strice_price'] = $item->strike_price;
                $items_ins['save_price'] = $item->save_price;
                $items_ins['base_price'] = $item->tax->basePrice;
                $items_ins['tax_amount'] = ($item->tax->gstAmount ?? 0) * $item->quantity;
                $items_ins['tax_percentage'] = $item->tax->tax_percentage ?? 0;
                $items_ins['sub_total'] = $item->sub_total;

                $order_product_info = OrderProduct::create($items_ins);
                if (isset($product_info->warranty_id) && !empty($product_info->warranty_id)) {
                    $warranty_info = Warranty::find($product_info->warranty_id);
                    if ($warranty_info) {
                        $war = [];
                        $war['order_product_id'] = $order_product_info->id;
                        $war['product_id'] = $order_product_info->product_id;
                        $war['warranty_id'] = $warranty_info->id;
                        $war['warranty_name'] = $warranty_info->name;
                        $war['description'] = $warranty_info->description;
                        $war['warranty_period'] = $warranty_info->warranty_period;
                        $war['warranty_period_type'] = $warranty_info->warranty_period_type;
                        $war['warranty_start_date'] = date('Y-m-d');
                        $war['warranty_end_date'] = getEndWarrantyDate($warranty_info->warranty_period, $warranty_info->warranty_period_type);
                        $war['status'] = 'active';
                        OrderProductWarranty::create($war);
                    }
                }

                /**
                 * insert addons data
                 */
                if (isset($item->addons) && !empty($item->addons)) {
                    foreach ($item->addons as $aitems) {
                        $add_ins = [];
                        $add_ins['order_id'] = $order_id;
                        $add_ins['product_id'] = $item->id;
                        $add_ins['addon_id'] = $aitems->addon_id;
                        $add_ins['addon_item_id'] = $aitems->addon_item_id;
                        $add_ins['title'] = $aitems->title;
                        $add_ins['addon_item_label'] = $aitems->addon_item_label;
                        $add_ins['amount'] = $aitems->amount;
                        $add_ins['icon'] = $aitems->icon;
                        $add_ins['description'] = $aitems->description;

                        OrderProductAddon::create($add_ins);
                    }
                }
            }
        }

        /**** order history */
        $his['order_id'] = $order_info->id;
        $his['action'] = 'Order Initiate';
        $his['description'] = 'Order has been Initiated successfully';
        OrderHistory::create($his);

        try {

            $api = new Api($keyId, $keySecret);
            $orderData = [
                'receipt'         => $order_info->order_no,
                'amount'          => $pay_amount * 100,
                'currency'        => "INR",
                'payment_capture' => 1 // auto capture
            ];

            $razorpayOrder = $api->order->create($orderData);
            $razorpayOrderId = $razorpayOrder['id'];

            session()->put('razorpay_order_id', $razorpayOrderId);

            $amount = $orderData['amount'];
            $displayCurrency        = "INR";
            $data = [
                "key"               => $keyId,
                "amount"            => round($amount),
                "currency"          => "INR",
                "name"              => 'GBS Systems & Services Private Limited',
                "image"             => asset(gSetting('logo')),
                "description"       => "Secure Payment",
                "prefill"           => [
                    "name"              => $billing_address->name,
                    "email"             => $billing_address->email,
                    "contact"           => $billing_address->mobile_no,
                ],
                "notes"             => [
                    "address"           => "",
                    "merchant_order_id" => $order_info->order_no,
                ],
                "theme"             => [
                    "color"             => "#F37254"
                ],
                "order_id"          => $razorpayOrderId,
            ];

            $order_info = Order::find($order_id);
            $order_info->payment_response_id = $razorpayOrderId;
            $order_info->save();

            return $data;
        } catch (Exception $e) {
            return $e;
        }
    }

    public function verifySignature(Request $request)
    {

        $keyId = env('RAZORPAY_KEY');
        $keySecret = env('RAZORPAY_SECRET');

        $customer_id = $request->customer_id;

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

            if ($success) {

                Cart::where('customer_id', $customer_id)->delete();
                /** 
                 *  1. do quantity update in product
                 *  2. update order status and payment response
                 *  3. insert in payment entry 
                 */
                $order_info = Order::where('payment_response_id', $razorpay_order_id)->first();
                if ($order_info) {
                    $order_status    = OrderStatus::where('status', 'published')->where('order', 2)->first();

                    $order_info->status = 'placed';
                    $order_info->order_status_id = $order_status->id;
                    $order_info->save();

                    $order_items = OrderProduct::where('order_id', $order_info->id)->get();

                    if(!is_null($order_info->coupon_code)) {
                        $AppliedCoupon =  Coupons::where('coupon_code', $order_info->coupon_code)->first();
                        $AppliedCoupon->quantity = $AppliedCoupon->quantity - 1;
                        $AppliedCoupon->save();
                    }

                    if (isset($order_items) && !empty($order_items)) {
                        foreach ($order_items as $product) {
                            $product_info = Product::find($product->product_id);
                            $pquantity = $product_info->quantity - $product->quantity;
                            $product_info->quantity = $pquantity;
                            if ($pquantity == 0) {
                                $product_info->stock_status = 'out_of_stock';
                            }
                            $product_info->save();
                        }
                    }

                    $pay_ins['order_id'] = $order_info->id;
                    $pay_ins['payment_no'] = $razor_response['razorpay_payment_id'];
                    $pay_ins['amount'] = $order_info->amount;
                    $pay_ins['paid_amount'] = $order_info->amount;
                    $pay_ins['payment_type'] = 'razorpay';
                    $pay_ins['payment_mode'] = 'online';
                    $pay_ins['response'] = serialize($finalorder);
                    $pay_ins['status'] = $finalorder['status'];

                    Payment::create($pay_ins);

                    /**** order history */
                    $his['order_id'] = $order_info->id;
                    $his['action'] = 'Order Placed';
                    $his['description'] = 'Order has been placed successfully';
                    OrderHistory::create($his);

                    /****
                     * 1.send email for order placed
                     * 2.send sms for notification
                     */
                    #generate invoice
                    $globalInfo = GlobalSettings::first();
                    $pickup_details = [];
                    if (isset($order_info->pickup_store_id) && !empty($order_info->pickup_store_id) && !empty($order_info->pickup_store_details)) {
                        $pickup = unserialize($order_info->pickup_store_details);

                        $pickup_details = $pickup;
                    }
                    $pdf = PDF::loadView('platform.invoice.index', compact('order_info', 'globalInfo', 'pickup_details'));
                    Storage::put('public/invoice_order/' . $order_info->order_no . '.pdf', $pdf->output());
                    #send mail
                    $emailTemplate = EmailTemplate::select('email_templates.*')
                        ->join('sub_categories', 'sub_categories.id', '=', 'email_templates.type_id')
                        ->where('sub_categories.slug', 'new-order')->first();

                    $globalInfo = GlobalSettings::first();

                    $extract = array(
                        'name' => $order_info->billing_name,
                        'regards' => $globalInfo->site_name,
                        'company_website' => '',
                        'company_mobile_no' => $globalInfo->site_mobile_no,
                        'company_address' => $globalInfo->address,
                        'dynamic_content' => '',
                        'order_id' => $order_info->order_no
                    );
                    $templateMessage = $emailTemplate->message;
                    $templateMessage = str_replace("{", "", addslashes($templateMessage));
                    $templateMessage = str_replace("}", "", $templateMessage);
                    extract($extract);
                    eval("\$templateMessage = \"$templateMessage\";");

                    $title = $emailTemplate->title;
                    $title = str_replace("{", "", addslashes($title));
                    $title = str_replace("}", "", $title);
                    eval("\$title = \"$title\";");

                    $filePath = 'storage/invoice_order/' . $order_info->order_no . '.pdf';
                    $send_mail = new OrderMail($templateMessage, $title, $filePath);
                    // return $send_mail->render();
                    try {
                        Mail::to($order_info->billing_email)->bcc('support@gbssystems.com')->send($send_mail);
                    } catch (\Throwable $th) {
                        Log::info($th->getMessage());
                    }

                    #send sms for notification
                    $sms_params = array(
                        'company_name' => env('APP_NAME'),
                        'order_no' => $order_info->order_no,
                        'reference_no' => '',
                        'mobile_no' => [$order_info->billing_mobile_no]
                    );
                    sendGBSSms('confirm_order', $sms_params);
                }
            }
        } else {
            $success = false;
            $error_message = 'Payment Failed';

            if (isset($request->razor_response['error']) && !empty($request->razor_response['error'])) {

                $orderdata = $request->razor_response['error']['metadata'];
                $razorpay_payment_id = $orderdata['payment_id'];
                $razorpay_order_id = $orderdata['order_id'];

                $api = new Api($keyId, $keySecret);

                $finalorder = $api->order->fetch($orderdata['order_id']);

                $order_info = Order::where('payment_response_id', $razorpay_order_id)->first();

                if ($order_info) {

                    $order_status    = OrderStatus::where('status', 'published')->where('order', 3)->first();

                    $order_info->status = 'payment_pending';
                    $order_info->order_status_id = $order_status->id;

                    $order_info->save();

                    $order_items = OrderProduct::where('order_id', $order_info->id)->get();


                    if(!is_null($order_info->coupon_code)) {
                        $AppliedCoupon =  Coupons::where('coupon_code', $order_info->coupon_code)->first();
                        $AppliedCoupon->quantity = $AppliedCoupon->quantity - 1;
                        $AppliedCoupon->save();
                    }

                    if (isset($order_items) && !empty($order_items)) {
                        foreach ($order_items as $product) {
                            $product_info = Product::find($product->id);
                            $pquantity = $product_info->quantity - $product->quantity; // reduce product Qty
                            $product_info->quantity = $pquantity;
                            if ($pquantity == 0) {
                                $product_info->stock_status = 'out_of_stock';
                            }
                            $product_info->save();
                        }
                    }

                    $pay_ins['order_id'] = $order_info->id;
                    $pay_ins['payment_no'] = $razorpay_payment_id;
                    $pay_ins['amount'] = $order_info->amount;
                    $pay_ins['paid_amount'] = $order_info->amount;
                    $pay_ins['payment_type'] = 'razorpay';
                    $pay_ins['payment_mode'] = 'online';
                    $pay_ins['description'] = $request->razor_response['error']['description'];
                    $pay_ins['response'] = serialize($finalorder);
                    $pay_ins['status'] = 'failed';

                    $error_message = $request->razor_response['error']['description'];

                    Payment::create($pay_ins);
                }
            }
        }

        return  array('success' => $success, 'message' => $error_message);
    }
}
