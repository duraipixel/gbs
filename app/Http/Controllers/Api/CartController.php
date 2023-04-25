<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\CartAddress;
use App\Models\CartProductAddon;
use App\Models\Master\Customer;
use App\Models\Product\Product;
use App\Models\ProductAddonItem;
use App\Models\Settings\Tax;
use App\Models\ShippingCharge;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class CartController extends Controller
{
    public function addToCart(Request $request)
    {
        
        $customer_id = $request->customer_id;
        $guest_token = $request->guest_token;
        $addon_id = $request->addon_id;
        $product_id = $request->product_id;
        $quantity = $request->quantity ?? 1;
        $type = $request->type;
        
        /**
         * 1. check customer id and product exist if not insert
         * 2. if exist update quantiy
         */
        

        $product_info = Product::find($product_id);
        $checkCart = Cart::when( $customer_id != '', function($q) use($customer_id) {
                            $q->where('customer_id', $customer_id);
                        })->
                        when( $customer_id == '' && $guest_token != '', function($q) use($guest_token) {
                            $q->where('guest_token', $guest_token);
                        })->where('product_id', $product_id)->first();

        $getCartToken = Cart::when( $customer_id != '', function($q) use($customer_id) {
                            $q->where('customer_id', $customer_id);
                        })->
                        when( $customer_id == '' && $guest_token != '', function($q) use($guest_token) {
                            $q->where('guest_token', $guest_token);
                        })->first();

       
            if (isset($checkCart) && !empty($checkCart)) {
                if ($type == 'delete') {
                    $checkCart->delete();
                } else {
                    $error = 0;
                    $message = 'Cart added successfull';
                    $product_quantity = $checkCart->quantity + $quantity;
                    if ($product_info->quantity <= $product_quantity) {
                        $product_quantity = $product_info->quantity;
                    }
                    
                    $checkCart->quantity  = $product_quantity;
                    $checkCart->sub_total = $product_quantity * $checkCart->price;
                    $checkCart->update();
    
                    $data = $this->getCartListAll($customer_id, $guest_token);
                }
            } else {
                $customer_info = Customer::find($request->customer_id);
                
                if( isset( $customer_info ) && !empty( $customer_info) || !empty($request->guest_token) ) {
    
                    if ($product_info->quantity <= $quantity) {
                        $quantity = $product_info->quantity;
                    }
                    $ins['customer_id']     = $request->customer_id;
                    $ins['product_id']      = $product_id;
                    $ins['guest_token']     = $request->guest_token ?? null;
                    $ins['quantity']        = $quantity ?? 1;
                    $ins['price']           = (float)$product_info->mrp;
                    $ins['sub_total']       = $product_info->mrp * $quantity ?? 1;
                    $ins['cart_order_no']   = 'ORD' . date('ymdhis');
                    
                    $cart_id = Cart::create($ins)->id;
                    $error = 0;
                    $message = 'Cart added successfull';
                    $data = $this->getCartListAll($customer_id, $guest_token);
                } else {
                    $error = 1;
                    $message = 'Customer Data not available';
                    $data = [];
                }
            }
        
        
        
        
        return array( 'error' => $error, 'message' => $message, 'data' => $data);
    }

    public function updateCart(Request $request)
    {

        $cart_id        = $request->cart_id;
        $guest_token    = $request->guest_token;
        $customer_id    = $request->customer_id;
        $quantity       = $request->quantity ?? 1;
        $addon_id   = $request->addon_id;

        $addon_items_info = ProductAddonItem::find($addon_id);
       
            
        $checkCart      = Cart::where('id', $cart_id)->first();
        if( $checkCart ) {

            if( isset( $addon_items_info ) && !empty( $addon_items_info ) ) {

                $addon = [];
                $addon['cart_id'] = $cart_id;
                $addon['product_id'] = $checkCart->product_id;
                $addon['addon_item_id'] = $addon_id;
                $addon['title'] = $addon_items_info->label;
                $addon['amount'] = $addon_items_info->amount;
                
                $addonCartItems = CartProductAddon::updateOrCreate(['cart_id' => $cart_id, 'product_id' => $checkCart->product_id, 'addon_item_id' => $addon_id], $addon);
                
            } else {

                $checkCart->quantity = $quantity;
                $checkCart->sub_total = $checkCart->price * $quantity;
                $checkCart->update();
            }
          
            $error = 0;
            $message = 'Cart updated successfull';
            $data = $this->getCartListAll($checkCart->customer_id, $checkCart->guest_token);
        } else {

            $error = 1;
            $message = 'Cart Data not available';
            $data = [];
        }

        return array( 'error' => $error, 'message' => $message, 'data' => $data );
    }

    public function deleteCart(Request $request)
    {

        $cart_id        = $request->cart_id;  
        $customer_id = $request->customer_id;
        $guest_token = $request->guest_token;
        $product_id = $request->product_id;
        
        $checkCart = Cart::when( $customer_id != '', function($q) use($customer_id) {
                            $q->where('customer_id', $customer_id);
                        })->
                        when( $customer_id == '' && $guest_token != '', function($q) use($guest_token) {
                            $q->where('guest_token', $guest_token);
                        })->where('product_id', $product_id)->first();


        // $checkCart      = Cart::find($cart_id);
        if( $checkCart ) {

            $customer_id    = $checkCart->customer_id;
            $guest_token    = $checkCart->guest_token;
            $checkCart->delete();

            $error = 0;
            $message = 'Cart Item deleted successfull';

            $data = $this->getCartListAll($customer_id, $guest_token);
        } else {
            $error = 1;
            $message = 'Cart Data not available';
            $data = [];
        }
        return array( 'error' => $error, 'message' => $message, 'data' => $data );

    }

    public function clearCart(Request $request)
    {

        $customer_id        = $request->customer_id;
        $guest_token        = $request->guest_token;

        if( $customer_id || $guest_token ) {

            Cart::when( $customer_id != '', function($q) use($customer_id) {
                    $q->where('customer_id', $customer_id);
                })->
                when( $customer_id == '' && $guest_token != '', function($q) use($guest_token) {
                    $q->where('guest_token', $guest_token);
                })->delete();
            
            $data = $this->getCartListAll($customer_id, $guest_token);
            $error = 0;
            $message = 'Cart Cleared successfull';

        } else {

            $error = 1;
            $message = 'Customer Data not available';
            $data = [];

        }

        return array( 'error' => $error, 'message' => $message, 'data' => $data );

    }

    public function getCarts(Request $request)
    {
        $guest_token = $request->guest_token;
        $customer_id    = $request->customer_id;
        $selected_shipping = $request->selected_shipping ?? '';
        return $this->getCartListAll($customer_id, $guest_token, null, null, $selected_shipping); 
    }

    function getCartListAll($customer_id = null, $guest_token = null,  $shipping_info = null, $shipping_type = null, $selected_shipping = null, $coupon_data = null)
    {
        // dd( $coupon_data );
        $checkCart          = Cart::with(['products', 'products.productCategory'])->when( $customer_id != '', function($q) use($customer_id) {
                                        $q->where('customer_id', $customer_id);
                                    })->
                                    when( $customer_id == '' && $guest_token != '', function($q) use($guest_token) {
                                        $q->where('guest_token', $guest_token);
                                    })->get();

        $tmp                = [];
        $grand_total        = 0;
        $tax_total          = 0;
        $product_tax_exclusive_total = 0;
        $tax_percentage = 0;
        $cartTemp = [];
        if (isset($checkCart) && !empty($checkCart)) {
            foreach ($checkCart as $citems) {
                
                $items = $citems->products;
                $tax = [];
                $tax_percentage = 0;

                $category               = $items->productCategory;
                $price_with_tax         = $items->mrp;
                if (isset($category->parent->tax_id) && !empty($category->parent->tax_id)) {
                    $tax_info = Tax::find($category->parent->tax_id);
                } else if (isset($category->tax_id) && !empty($category->tax_id)) {
                    $tax_info = Tax::find($category->tax_id);
                }
                // dump( $citems );
                if (isset($tax_info) && !empty($tax_info)) {
                    $tax = getAmountExclusiveTax($price_with_tax, $tax_info->pecentage);
                    $tax_total =  $tax_total + ($tax['gstAmount'] * $citems->quantity) ?? 0;
                    $product_tax_exclusive_total = $product_tax_exclusive_total + ($tax['basePrice'] * $citems->quantity);
                    // print_r( $product_tax_exclusive_total );
                    $tax_percentage         = $tax['tax_percentage'] ?? 0;
                } else {
                    $product_tax_exclusive_total = $product_tax_exclusive_total + $citems->sub_total;
                }

                $pro                    = [];
                $pro['id']              = $items->id;
                $pro['tax']             = $tax;
                $pro['tax_percentage']  = $tax_percentage;
                $pro['hsn_no']          = $items->hsn_code ?? null;
                $pro['product_name']    = $items->product_name;
                $pro['category_name']   = $category->name ?? '';
                $pro['brand_name']      = $items->productBrand->brand_name ?? '';
                $pro['hsn_code']        = $items->hsn_code;
                $pro['product_url']     = $items->product_url;
                $pro['sku']             = $items->sku;
                $pro['has_video_shopping'] = $items->has_video_shopping;
                $pro['stock_status']    = $items->stock_status;
                $pro['is_featured']     = $items->is_featured;
                $pro['is_best_selling'] = $items->is_best_selling;
                $pro['price']           = $items->mrp;
                $pro['strike_price']    = $items->strike_price;
                $pro['save_price']      = $items->strike_price - $items->mrp;
                $pro['discount_percentage'] = abs($items->discount_percentage);
                $pro['image']           = $items->base_image;
                $pro['max_quantity']    = $items->quantity;
                $imagePath              = $items->base_image;

                if (!Storage::exists($imagePath)) {
                    $path               = asset('assets/logo/no_Image.jpg');
                } else {
                    $url                = Storage::url($imagePath);
                    $path               = asset($url);
                }

                $pro['image']           = $path;
                $pro['customer_id']     = $customer_id;
                $pro['guest_token']     = $citems->guest_token;
                $pro['cart_id']         = $citems->id;
                $pro['price']           = $citems->price;
                $pro['quantity']        = $citems->quantity;
                $pro['sub_total']       = $citems->sub_total;
                
                $grand_total            += $citems->sub_total;
                $cartTemp[] = $pro;
                
            }

            $tmp['carts'] = $cartTemp;
            $tmp['cart_count'] = count($cartTemp);
            if (isset($shipping_info) && !empty($shipping_info) || (isset( $selected_shipping ) && !empty( $selected_shipping )) ) {
                $tmp['selected_shipping_fees'] = array(
                                                'shipping_id' => $shipping_info->id ?? $selected_shipping['shipping_id'],
                                                'shipping_charge_order' => $shipping_info->charges ?? $selected_shipping['shipping_charge_order'],
                                                'shipping_type' => $shipping_type ?? $selected_shipping['shipping_type'] ?? 'fees'
                                                );
                
                $grand_total                = $grand_total + ($shipping_info->charges ?? $selected_shipping['shipping_charge_order'] ?? 0);
            }
            if( isset( $coupon_data ) && !empty( $coupon_data ) ) {
                $grand_total = $grand_total - $coupon_data['discount_amount'] ?? 0;
            }

            $amount         = filter_var($grand_total, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
            $charges        = ShippingCharge::select('id', 'shipping_title', 'minimum_order_amount', 'charges', 'is_free')->where('status', 'published')->where('minimum_order_amount', '<', $amount)->get();

            $tmp['shipping_charges']    = $charges;
            $tmp['cart_total']          = array(
                'total' => number_format(round($grand_total), 2),
                'product_tax_exclusive_total' => number_format(round($product_tax_exclusive_total), 2),
                'product_tax_exclusive_total_without_format' => round($product_tax_exclusive_total),
                'tax_total' => number_format(round($tax_total), 2),
                'tax_percentage' => number_format(round($tax_percentage), 2),
                'shipping_charge' => $shipping_info->charges ?? 0
            );
        }
        
        return $tmp;
    }

    public function getShippingCharges(Request $request)
    {
        
        $amount         = filter_var($request->amount, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
        $charges        = ShippingCharge::select('id', 'shipping_title', 'minimum_order_amount', 'charges', 'is_free')->where('status', 'published')->where('minimum_order_amount', '<', $amount )->get();
        return $charges;
    }
   
}
