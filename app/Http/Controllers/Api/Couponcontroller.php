<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\Offers\CouponCategory;
use App\Models\Offers\Coupons;
use App\Models\Settings\Tax;
use App\Models\ShippingCharge;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class Couponcontroller extends Controller
{
    public function applyCoupon(Request $request)
    {
        $coupon_code = $request->coupon_code;
        $customer_id = $request->customer_id;
        $shipping_fee_id = $request->shipping_fee_id ?? '';
        $carts          = Cart::where('customer_id', $customer_id)->get();
        
        if ($carts) {
            $coupon = Coupons::where('coupon_code', $coupon_code)
                ->where('is_discount_on', 'no')
                ->whereDate('coupons.start_date', '<=', date('Y-m-d'))
                ->whereDate('coupons.end_date', '>=', date('Y-m-d'))
                ->first();
            
            if (isset($coupon) && !empty($coupon)) {
                /**
                 * 1.check quantity is available to use
                 * 2.check coupon can apply for cart products
                 * 3.get percentage or fixed amount
                 * 
                 * coupon type 1- product, 2-customer, 3-category
                 */
                $has_product = 0;
                $product_amount = 0;
                $has_product_error = 0;
                $overall_discount_percentage = 0;
                $couponApplied = [];
                if ($coupon->quantity > $coupon->used_quantity ?? 0) {
                    
                    switch ($coupon->coupon_type) {
                        case '1':
                            # product ...
                            if (isset($coupon->couponProducts) && !empty($coupon->couponProducts)) {
                                $couponApplied['coupon_type'] = array('discount_type' => $coupon->calculate_type, 'discount_value' => $coupon->calculate_value);
                                foreach ($coupon->couponProducts as $items) {
                                    $cartCount = Cart::where('customer_id', $customer_id)->where('product_id', $items->product_id)->first();
                                    if ($cartCount) {
                                        if ($cartCount->sub_total >= $coupon->minimum_order_value) {
                                            /**
                                             * Check percentage or fixed amount
                                             */
                                            switch ($coupon->calculate_type) {

                                                case 'percentage':
                                                    $product_amount += percentageAmountOnly($cartCount->sub_total, $coupon->calculate_value);
                                                    $tmp['discount_amount'] = percentageAmountOnly($cartCount->sub_total, $coupon->calculate_value);
                                                    $tmp['product_id'] = $cartCount->product_id;
                                                    $tmp['coupon_applied_amount'] = $cartCount->sub_total;
                                                    // $tmp['coupon_type'] = array('discount_type' => $coupon->calculate_type, 'discount_value' => $coupon->calculate_value);
                                                    $overall_discount_percentage += $coupon->calculate_value;
                                                    $has_product++;
                                                    $couponApplied[] = $tmp;
                                                    break;
                                                case 'fixed_amount':
                                                    $product_amount += $coupon->calculate_value;
                                                    $tmp['discount_amount'] = $coupon->calculate_value;
                                                    $tmp['product_id'] = $cartCount->product_id;
                                                    $tmp['coupon_applied_amount'] = $cartCount->sub_total;
                                                    // $tmp['coupon_type'] = array('discount_type' => $coupon->calculate_type, 'discount_value' => $coupon->calculate_value);
                                                    $has_product++;
                                                    $couponApplied[] = $tmp;

                                                    break;
                                                default:

                                                    break;
                                            }

                                            $response['coupon_info'] = $couponApplied;
                                            $response['overall_applied_discount'] = $overall_discount_percentage;
                                            $response['coupon_amount'] = $product_amount;
                                            $response['coupon_id'] = $coupon->id;
                                            $response['coupon_code'] = $coupon->coupon_code;
                                            $response['status'] = 'success';
                                            $response['message'] = 'Coupon applied';
                                            $response['cart_info'] = $this->getCartListAll($customer_id, null, null, null, $shipping_fee_id, $response['coupon_amount']);
                                        }
                                    } else {
                                        $has_product_error++;
                                    }
                                }
                                if ($has_product == 0 && $has_product_error > 0) {
                                    $response['status'] = 'error';
                                    $response['message'] = 'Cart order does not meet coupon minimum order amount';
                                }
                            } else {
                                $response['status'] = 'error';
                                $response['message'] = 'Coupon not applicable';
                            }
                            break;

                        case '2':
                            # customer ...
                            break;

                        case '3':
                            # category ...
                            $checkCartData = Cart::selectRaw('mm_carts.*,mm_products.product_name,mm_product_categories.name,mm_coupon_categories.id as catcoupon_id, SUM(mm_carts.sub_total) as category_total')
                                                ->join('products', 'products.id', '=', 'carts.product_id')
                                                ->join('product_categories', 'product_categories.id', '=', 'products.category_id')
                                                ->join('coupon_categories', function ($join) {
                                                    $join->on('coupon_categories.category_id', '=', 'product_categories.id');
                                                    // $join->orOn('coupon_categories.category_id', '=', 'product_categories.parent_id');
                                                })
                                                ->where('coupon_categories.coupon_id', $coupon->id )
                                                ->where('carts.customer_id', $customer_id)
                                                // ->groupBy('carts.product_id')
                                                ->first();

                            if( isset( $checkCartData) && !empty( $checkCartData ) ) {
                                
                                if ($checkCartData->category_total >= $coupon->minimum_order_value) {
                                    /**
                                     * check percentage or fixed amount
                                     */
                                    switch ($coupon->calculate_type) {

                                        case 'percentage':
                                            $product_amount = percentageAmountOnly($checkCartData->category_total, $coupon->calculate_value);
                                            $tmp['discount_amount'] = percentageAmountOnly($checkCartData->category_total, $coupon->calculate_value);
                                            $tmp['coupon_id'] = $coupon->id;
                                            $tmp['coupon_code'] = $coupon->coupon_code;
                                            $tmp['coupon_applied_amount'] = number_format((float)$checkCartData->category_total, 2, '.', '');
                                            $tmp['coupon_type'] = array('discount_type' => $coupon->calculate_type, 'discount_value' => $coupon->calculate_value);
                                            $overall_discount_percentage = $coupon->calculate_value;
                                            $couponApplied = $tmp;
                                            break;
                                        case 'fixed_amount':
                                            $product_amount += $coupon->calculate_value;
                                            $tmp['discount_amount'] = $coupon->calculate_value;
                                            $tmp['coupon_id'] = $coupon->id;
                                            $tmp['coupon_code'] = $coupon->coupon_code;
                                            $tmp['coupon_applied_amount'] = number_format((float)$checkCartData->sub_total, 2, '.', '');
                                            $tmp['coupon_type']         = array('discount_type' => $coupon->calculate_type, 'discount_value' => $coupon->calculate_value);
                                            $has_product++;
                                            $couponApplied[] = $tmp;

                                            break;
                                        default:

                                            break;
                                    }

                                    $response['coupon_info'] = $couponApplied;
                                    $response['overall_applied_discount'] = $overall_discount_percentage;
                                    $response['coupon_amount'] = number_format((float)$product_amount, 2, '.', '');
                                    $response['coupon_id'] = $coupon->id;
                                    $response['coupon_code'] = $coupon->coupon_code;
                                    $response['status'] = 'success';
                                    $response['message'] = 'Coupon applied';
                                    $response['cart_info'] = $this->getCartListAll($customer_id, null, null, null, $shipping_fee_id, $response['coupon_amount']);
                                }
                            } else {
                                $response['status'] = 'error';
                                $response['message'] = 'Cart order does not meet coupon minimum order amount';
                            }
                            break;

                        default:
                            # code...
                            break;
                    }
                } else {
                    $response['status'] = 'error';
                    $response['message'] = 'Coupon Limit reached';
                }
            } else {
                $response['status'] = 'error';
                $response['message'] = 'Coupon code not available';
            }
        } else {
            $response['status'] = 'error';
            $response['message'] = 'There is no products on the cart';
        }
        return $response;
    }

    public function removeCoupon(Request $request)
    {
        $customer_id = $request->customer_id;
        $shipping_fee_id = $request->shipping_fee_id ?? '';
        $carts          = Cart::where('customer_id', $customer_id)->get();
        $response['cart_info'] = $this->getCartListAll($customer_id, null, null, null, $shipping_fee_id);
        $response['status'] = 'success';
        $response['message'] = 'Coupon removed successfully';

        return $response;
    }


    function getCartListAll($customer_id = null, $guest_token = null,  $shipping_info = null, $shipping_type = null, $selected_shipping = null, $coupon_amount = null)
    {
        // dd( $coupon_data );
        if( $selected_shipping ) {
            $shippingfee_info = ShippingCharge::select('id', 'shipping_title', 'minimum_order_amount', 'charges', 'is_free')->find($selected_shipping);
            
        }
        
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
                    $path               = asset('assets/logo/product-noimg.jpg');
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
            if (isset( $shippingfee_info ) && !empty( $shippingfee_info ) ) {
                $tmp['selected_shipping_fees'] = array(
                                                'id' => $shippingfee_info->id,
                                                'charges' => $shippingfee_info->charges,
                                                'shipping_title' => $shippingfee_info->shipping_title
                                                );
                
                $grand_total                = $grand_total + ($shippingfee_info->charges ?? 0);
            }
            if( isset( $coupon_amount ) && !empty( $coupon_amount ) ) {
                $grand_total = $grand_total - $coupon_amount ?? 0;
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
                'shipping_charge' => $shippingfee_info->charges ?? 0,
                'coupon_amount' => $coupon_amount ?? 0
                
            );
        }
        
        return $tmp;
    }

    public function setShippingCharges(Request $request)
    {

        $customer_id = $request->customer_id;
        $shipping_fee_id = $request->shipping_fee_id;
        $coupon_amount = $request->coupon_amount ?? 0;
        
        $fee_info = ShippingCharge::select('id', 'shipping_title', 'minimum_order_amount', 'charges', 'is_free')->find($shipping_fee_id);
        if( $fee_info ) {
            
            $data = $this->getCartListAll($customer_id, null, null, null, $shipping_fee_id, $coupon_amount);
            $response['data'] = $data;
            $response['error'] = '0';
            $response['message'] = 'Shipping Fee Applied';

        } else {

            $response['data'] = [];
            $response['error'] = '1';
            $response['message'] = 'There is no products on the cart';

        }
        
        return $response;

    }
}
