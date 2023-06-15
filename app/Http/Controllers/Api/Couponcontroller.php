<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\CartProductAddon;
use App\Models\Offers\CouponCategory;
use App\Models\Offers\Coupons;
use App\Models\Settings\Tax;
use App\Models\ShippingCharge;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
                                            /** upddate cart coupon amount */
                                            if( isset($shipping_fee_id) && !empty( $shipping_fee_id ) ) {
                                                $shippingfee_info = ShippingCharge::select('id', 'shipping_title', 'minimum_order_amount', 'charges', 'is_free')->find($shipping_fee_id);
                                                
                                            }
                                            $update_data = [
                                                'coupon_id' => $coupon->id,
                                                'coupon_amount' => $product_amount,
                                                'shipping_fee_id' => $shippingfee_info->id ?? null,
                                                'shipping_fee' => $shippingfee_info->charges ?? null
                                            ];
                                            DB::table('carts')->where('customer_id', $customer_id)->update($update_data);
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

                                    /** upddate cart coupon amount */
                                    if( isset($shipping_fee_id) && !empty( $shipping_fee_id ) ) {
                                        $shippingfee_info = ShippingCharge::select('id', 'shipping_title', 'minimum_order_amount', 'charges', 'is_free')->find($shipping_fee_id);
                                        
                                    }
                                    $update_data = [
                                        'coupon_id' => $coupon->id,
                                        'coupon_amount' => $product_amount,
                                        'shipping_fee_id' => $shippingfee_info->id ?? null,
                                        'shipping_fee' => $shippingfee_info->charges ?? null
                                    ];
                                    DB::table('carts')->where('customer_id', $customer_id)->update($update_data);
                                    
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
        $update_data = [
            'coupon_id' => null,
            'coupon_amount' => null
        ];
        DB::table('carts')->where('customer_id', $customer_id)->update($update_data);
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
        $total_addon_amount = 0;
        $cartTemp = [];
        $brand_array = [];
        $has_pickup_store = true;
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

                /**
                 * addon amount calculated here
                 */
                $addonItems = CartProductAddon::where(['cart_id' => $citems->id, 'product_id' => $items->id ])->get();
                
                $addon_total = 0;
                if( isset( $addonItems ) && !empty( $addonItems ) ) {
                    foreach ($addonItems as $addItems) {
                        
                        $addons = [];
                        $addons['addon_id'] = $addItems->addonItem->addon->id;
                        $addons['title'] = $addItems->addonItem->addon->title;
                        $addons['description'] = $addItems->addonItem->addon->description;

                        if (!Storage::exists( $addItems->addonItem->addon->icon)) {
                            $path               = asset('assets/logo/no_Image.jpg');
                        } else {
                            $url                = Storage::url( $addItems->addonItem->addon->icon);
                            $path               = asset($url);
                        }
                        $addons['addon_item_id'] = $addItems->addonItem->id;
                        $addons['icon'] = $path;
                        $addons['addon_item_label'] = $addItems->addonItem->label;
                        $addons['amount'] = $addItems->addonItem->amount;
                        $addon_total += $addItems->addonItem->amount;
                        $used_addons[] = $addons;

                    }
                }

                $total_addon_amount += $addon_total;

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

                $brand_array[] = $items->brand_id;

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
                $grand_total            += $addon_total;
                $cartTemp[] = $pro;
                
            }

            $tmp['carts'] = $cartTemp;
            $tmp['cart_count'] = count($cartTemp);
            if (isset($shipping_info) && !empty($shipping_info) || (isset( $shippingfee_info ) && !empty( $shippingfee_info )) ) {
                dump( $shippingfee_info );
                dd( $shipping_info );
                $tmp['selected_shipping_fees'] = array(
                                                'shipping_id' => $shipping_info->id ?? $shippingfee_info['shipping_id'],
                                                'shipping_charge_order' => $shipping_info->charges ?? $shippingfee_info['shipping_charge_order'],
                                                'shipping_type' => $shipping_type ?? $shippingfee_info['shipping_type'] ?? 'fees'
                                                );
                
                $grand_total                = $grand_total + ($shipping_info->charges ?? $shippingfee_info['shipping_charge_order'] ?? 0);
            }
            if( isset( $coupon_amount ) && !empty( $coupon_amount ) ) {
                $grand_total = $grand_total - $coupon_amount ?? 0;
            }

            if( count(array_unique($brand_array)) > 1 ) {
                $has_pickup_store = false;
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
                'coupon_amount' => $coupon_amount ?? 0,
                'addon_amount' => $total_addon_amount,
                'has_pickup_store' => $has_pickup_store
                
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
            $update_data = [
                'shipping_fee_id' => $shipping_fee_id,
                'shipping_fee' => $fee_info->charges
            ];
            DB::table('carts')->where('customer_id', $customer_id)->update($update_data);
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
