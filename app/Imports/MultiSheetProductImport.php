<?php

namespace App\Imports;

use App\Models\Master\Brands;
use App\Models\Product\Product;
use App\Models\Product\ProductCategory;
use App\Models\Product\ProductLink;
use App\Models\Settings\Tax;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Support\Str;

class MultiSheetProductImport implements ToModel, WithHeadingRow
{
   
    public function model(array $row)
    {
        /***
         * 1.check tax exist
         * 2.check category exist
         * 3.check subcategory exist
         * 4.check brand exist         
         */
        
        $status = 'published'; 
        
        $ins = $cat_ins = $tax_ins = $subcat_ins = $brand_ins = $link_ins = [];
        $category           = $row['category'] ?? null;
        $sub_category       = $row['sub_category'] ?? null;
        $tax                = 18;
        if( isset( $category ) && !empty( $category ) ) {
            #check taxt exits if not create 
            $taxPercentage  = $tax;
            $checkTax       = Tax::where('pecentage', $taxPercentage)->first();
            if( isset($checkTax) && !empty( $checkTax ) ) {
                $tax_id     = $checkTax->id;
            } else {
                $tax_ins['title'] = 'Tax '.intval($taxPercentage);
                $tax_ins['pecentage'] = $taxPercentage ?? 0;
                $tax_ins['order_by'] = 0;

                $tax_id = Tax::create($tax_ins)->id;
            }

            #do insert or update if data exist or not
            $checkCategory = ProductCategory::where('name', trim($category) )->first();
            
            if( isset( $checkCategory ) && !empty( $checkCategory ) ) {
                $category_id                = $checkCategory->id;
            } else {
                #insert new category                
                $cat_ins['name']            = $category;
                $cat_ins['parent_id']       = 0;
                $cat_ins['description']     = $row['category_description'] ?? null;
                $cat_ins['status']          = 'published';
                $cat_ins['is_featured']     = '0';
                $cat_ins['added_by']        = Auth::id();                
                $cat_ins['tag_line']        = $row['category_tagline'] ?? null;                
                $cat_ins['tax_id']          = $tax_id;
                $cat_ins['is_home_menu']    = 'yes'; 
                $cat_ins['slug']            = Str::slug($category);
                
                $category_id                = ProductCategory::create($cat_ins)->id;

            }
            if( !empty($category_id)) {

                #check subcategory exist or create new one
                $checkSubCategory = ProductCategory::where(['name' => trim($sub_category), 'parent_id' => $category_id] )->first();
                if( isset( $checkSubCategory ) && !empty( $checkSubCategory ) ) {
                    $sub_category_id                = $checkSubCategory->id;
                } else if( $sub_category ) {
                    #insert new sub category
                    $subcat_ins['tax_id']           = $tax_id;
                    $subcat_ins['is_home_menu']     = 'no';
                    $subcat_ins['added_by']         = Auth::id();
                    $subcat_ins['name']             = trim($sub_category);
                    $subcat_ins['description']      = $row['subcategory_description'] ?? null;
                    $subcat_ins['order_by']         = 0;
                    $subcat_ins['tag_line']         = $row['subcategory_tagline'] ?? null;
                    $subcat_ins['status']           = 'published';
                    $subcat_ins['parent_id']        = $category_id;
                    $subcat_ins['is_featured']      = '0';
    
                    $parent_name = '';
                    if( isset( $category_id ) && !empty( $category_id ) ) {
                        $parentInfo                 = ProductCategory::find($category_id);
                        $parent_name                = $parentInfo->name ?? '';
                    }
        
                    $subcat_ins['slug']             = Str::slug($sub_category.' '.$parent_name);
                    $sub_category_id                = ProductCategory::create($subcat_ins);
    
                }

            }
            #check brand exist or create new one
            $checkBrand                         = Brands::where('brand_name', trim($row['brand']))->first();
            if( isset( $checkBrand ) && !empty( $checkBrand ) ) {
                $brand_id                       = $checkBrand->id;
            } else {
                #insert new brand
                $brand_ins['brand_name']    = trim($row['brand']);
                $brand_ins['slug']          = Str::slug($row['brand']);
                $brand_ins['order_by']      = 0;
                $brand_ins['status']        = 'published';

                $brand_id                   = Brands::create($brand_ins)->id;
            }

            #check product exist or create new one
            $sku            = Str::replace('.','-',$row['sku']);
            
            $amount         = $row['mrp'] ?? $row['tax_inclexcl'] ?? 100;
            // $productPriceDetails = getAmountExclusiveTax((float)$amount, $taxPercentage ?? 0 );
			
            $productInfo = Product::where('sku', $sku)->first();

            $ins['product_name'] = trim($row['product_name']);
            $ins['hsn_code'] = $row['hsn'] ?? null;
            $ins['product_url'] = Str::slug(Str::replace('.', '-', $row['sku']).'-'.trim($row['brand']));
            $ins['sku'] = $sku;
            $ins['strike_price'] = round($row['mrp']);
            $ins['price'] = round($row['base_price']);
            $ins['mrp'] = round($row['price_with_tax'] ?? 0);
            $ins['discount_percentage'] = getDiscountPercentage(round($row['price_with_tax'] ?? 0), round($row['mrp']));
            $ins['status'] = $status;
            $ins['quantity'] = 1;
            $ins['stock_status'] = 'in_stock';
            $ins['brand_id'] = $brand_id;
            $ins['category_id'] = $sub_category_id ?? $category_id;
            $ins['is_featured'] = ( isset($row['featured']) && !empty( $row['featured']) ) ? 1 : 0;
            $ins['tax_id'] = $tax_id;
            $ins['description'] = $row['short_description'];
           
            $ins['added_by'] = Auth::id();
            
			if( isset( $productInfo ) && !empty( $productInfo ) ) {
                
            	DB::table('products')->where('id', $productInfo->id)->update($ins);
            	$product_id = $productInfo->id;
            
            } else {
            	$product_id     = Product::create($ins)->id;
            }
            
            if( isset( $row['video_link']) && !empty( $row['video_link'])) {
                $link_ins['product_id'] = $product_id;
                $link_ins['url'] = $row['video_link'];
                $link_ins['url_type'] = 'video_link';
                ProductLink::create($link_ins);
            }
            
        }
        
       
    }
}
