<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\Product\Product;
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
        $product_id = $request->id;
        $quantity = $request->quantity ?? 1;
        $type       = $request->type;
        
        /**
         *1.check customer id and product exist if not insert
         *2. if exist update quantiy 
         * 
         */
        
        $product_info = Product::find($product_id);
        $checkCart = Cart::where('customer_id', $customer_id)->where('product_id', $product_id)->first();
       
        $salePrices = $request->sale_prices;        
        
        if (isset($checkCart) && !empty($checkCart)) {
            if ($type == 'delete') {
                $checkCart->delete();
            } else {
                 
                $product_quantity = $checkCart->quantity + $quantity;
                if( $product_info->quantity <= $product_quantity ) {
                    $product_quantity = $product_info->quantity;
                }
                
                $checkCart->quantity    = $product_quantity;
                $checkCart->sub_total = $product_quantity * $checkCart->price;
                $checkCart->update();

            }

        } else {

            if( $product_info->quantity <= $quantity ) {
                $quantity = $product_info->quantity;
            }
            $ins['customer_id']     = $request->customer_id;
            $ins['product_id']      = $product_id;
            $ins['quantity']        = $quantity ?? 1;
            $ins['price']           = $salePrices['price_original'];
            $ins['sub_total']       = $salePrices['price_original'] * $quantity ?? 1;

            $cart_id = Cart::create($ins)->id;
            $ins['message']         = 'added';

        }
        return $this->getCartListAll($customer_id);
    }

    public function updateCart(Request $request)
    {

        $cart_id        = $request->cart_id;        
        $quantity       = $request->quantity ?? 1;        
        $checkCart      = Cart::where('id', $cart_id)->first();

        $checkCart->quantity = $quantity;
        $checkCart->sub_total = $checkCart->price * $quantity;
        $checkCart->update();
        return $this->getCartListAll($checkCart->customer_id);

    }

    public function deleteCart(Request $request)
    {

        $cart_id        = $request->cart_id;    

        $checkCart      = Cart::find($cart_id);
        $customer_id    = $checkCart->customer_id;
        $checkCart->delete();
        return $this->getCartListAll($customer_id);

    }

    public function clearCart(Request $request)
    {

        $customer_id        = $request->customer_id;

        Cart::where('customer_id', $customer_id)->delete();
        return $this->getCartListAll($customer_id);

    }

    public function getCarts(Request $request)
    {

        $customer_id    = $request->customer_id;
        return $this->getCartListAll($customer_id);

    }

    function getCartListAll($customer_id, $shipping_info = null ) {
        
        $checkCart          = Cart::where('customer_id', $customer_id)->get();
        $tmp                = ['carts'];
        $grand_total        = 0;
        $tax_total          = 0;
        $product_tax_exclusive_total = 0;
        $tax_percentage = 0;

        if (isset($checkCart ) && !empty($checkCart )) {
            foreach ($checkCart as $citems ) {
                foreach ($citems->products as $items ) {
                    $tax = [];
                    $tax_percentage = 0;
                    $category               = $items->productCategory;
                    $salePrices             = getProductPrice($items);
                    
                    if( isset( $category->parent->tax_id ) && !empty( $category->parent->tax_id ) ) {
                        $tax_info = Tax::find( $category->parent->tax_id );
                        
                    } else if( isset( $category->tax_id ) && !empty( $category->tax_id ) ) {
                        $tax_info = Tax::find( $category->tax_id );
                        
                    } 
                    if( isset( $tax_info ) && !empty( $tax_info ) ) {
                        $tax = getAmountExclusiveTax( $salePrices['price_original'], $tax_info->pecentage );
                        $tax_total =  $tax_total + $tax['gstAmount'] ?? 0;
                        $product_tax_exclusive_total = $product_tax_exclusive_total + ($tax['basePrice'] ?? 0 * $citems->quantity );
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
                    $pro['is_new']          = $items->is_new;
                    $pro['sale_prices']     = $salePrices;
                    $pro['mrp_price']       = $items->price;
                    $pro['image']           = $items->base_image;
                    $pro['max_quantity']    = $items->quantity;
                    $imagePath              = $items->base_image;
    
                    if (!Storage::exists($imagePath)) {
                        $path               = asset('assets/logo/product-noimg.jpg');
                    } else {
                        $url                = Storage::url($imagePath);
                        $path               = asset($url);
                    }
    
                    $pro['image']           = $path;
                    $pro['customer_id']     = $customer_id;
                    $pro['cart_id']         = $citems->id;
                    $pro['price']           = $citems->price;
                    $pro['quantity']        = $citems->quantity;
                    $pro['sub_total']       = $citems->sub_total;
                    $grand_total            += $citems->sub_total;
                    $tmp['carts'][] = $pro;
                }
            }

            if( isset($shipping_info ) && !empty( $shipping_info ) ) {
                $tmp['shipping_id']         = $shipping_info->id;
                $grand_total                = $grand_total + $shipping_info->charges ?? 0;
            }

            $amount         = filter_var($grand_total, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
            $charges        = ShippingCharge::where('status', 'published')->where('minimum_order_amount', '<', $amount )->get();
        
            $tmp['shipping_charges']    = $charges;
            $tmp['cart_total']          = array(
                                                'total' => number_format( round($grand_total),2), 
                                                'product_tax_exclusive_total' => number_format(round($product_tax_exclusive_total),2),
                                                'product_tax_exclusive_total_without_format' => round($product_tax_exclusive_total),
                                                'tax_total' => number_format(round($tax_total), 2),
                                                'tax_percentage' => number_format(round($tax_percentage),2)
                                            );
            
        }
        return $tmp;
    }

    public function getShippingCharges(Request $request)
    {
        $customer_id    = $request->customer_id;
        
        $amount         = filter_var($request->amount, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
        $charges        = ShippingCharge::where('status', 'published')->where('minimum_order_amount', '<', $amount )->get();
        return $charges;
    }

    public function updateCartAmount(Request $request)
    {

        $customer_id    = $request->customer_id;
        $shipping_id    = $request->shipping_id;

        $shipping_info  = ShippingCharge::find($shipping_id);

        return $this->getCartListAll($customer_id, $shipping_info );

    }
   
}