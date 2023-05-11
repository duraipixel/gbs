<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category\SubCategory;
use App\Models\HomePageSetting\HomepageSetting;
use App\Models\HomePageSetting\HomepageSettingItems;
use App\Models\Product\Product;
use App\Models\Product\ProductAttributeSet;
use App\Models\Product\ProductCategory;
use App\Models\Product\ProductCollection;
use App\Models\Product\ProductWithAttributeSet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class FilterController extends Controller
{
    public function getFilterStaticSideMenu(Request $request)
    {
        $category_slug = $request->category_slug ?? '';

        $categories = Product::select('product_categories.id', 'product_categories.name', 'product_categories.slug')
                            ->join('product_categories', 'product_categories.id', '=', 'products.category_id')
                            ->where('product_categories.parent_id', 0)
                            ->groupBy('products.category_id')
                            ->get()->toArray();
        
        $get_max_discounts = Product::selectRaw('max(abs(gbs_products.discount_percentage)) as discount')
                            ->where('status','published')->where('stock_status', 'in_stock')->first();
        $discounts = [];
        if( $get_max_discounts->discount ){

            $discount_range = $get_max_discounts->discount / 3;
            $second_range = ( round($discount_range) +  round($discount_range));
            $discounts = array( 
                                array( 'name' => 'below '.round($discount_range).'%', 'slug' => '0-'.round($discount_range)),
                                array( 'name' => round($discount_range).'% To '.$second_range.'%', 'slug' =>  round($discount_range).'-'.$second_range),
                                array( 'name' => $second_range.'% To '.round($get_max_discounts->discount).'%', 'slug' => $second_range.'-'.round($get_max_discounts->discount)),
                            );
        }

        /** 
         * size filter actions
         */
        $size_data = ProductWithAttributeSet::select('attribute_values')->where('title', 'Size')->groupByRaw("SUBSTRING_INDEX(gbs_product_with_attribute_sets.attribute_values,' ', 1)")->get();
        $sizes = [];
        if( isset( $size_data ) && !empty( $size_data ) ){
            foreach ($size_data as $size_item ) {
                $int_var = explode(' ', $size_item->attribute_values);
                $tmp = [];
                $tmp = array( 'name' => $size_item->attribute_values, 'slug' => current($int_var) );
                $sizes[] = $tmp;
            }
        }

        $sort_by                = array(
            // array('id' => null, 'name' => 'Featured', 'slug' => 'is-featured'),
            array('id' => null, 'name' => 'Price: High to Low', 'slug' => 'price-high-to-low'),
            array('id' => null, 'name' => 'Price: Low to High', 'slug' => 'price-low-to-high'),
        );       

        $collection             = ProductCollection::select('product_collections.id', 'product_collections.collection_name as name', 'product_collections.slug')
            ->join('product_collections_products', 'product_collections_products.product_collection_id', '=', 'product_collections.id')
            ->join('products', 'products.id', '=', 'product_collections_products.product_id')
            ->where('products.stock_status', 'in_stock')
            ->where('products.status', 'published')
            ->where('product_collections.can_map_discount', 'no')
            ->where('product_collections.show_home_page', 'yes')
            ->where('product_collections.is_handpicked_collection', 'no')
            ->where('product_collections.status', 'published')
            ->orderBy('product_collections.order_by', 'asc')
            ->groupBy('product_collections.id')
            ->get()->toArray();

        $handpicked = ProductCollection::select('product_collections.id', 'product_collections.collection_name as name', 'product_collections.slug')
            ->join('product_collections_products', 'product_collections_products.product_collection_id', '=', 'product_collections.id')
            ->join('products', 'products.id', '=', 'product_collections_products.product_id')
            ->where('products.stock_status', 'in_stock')
            ->where('products.status', 'published')
            ->where('product_collections.is_handpicked_collection', 'yes')
            ->where('product_collections.show_home_page', 'yes')
            ->where('product_collections.status', 'published')
            ->orderBy('product_collections.order_by', 'asc')
            ->groupBy('product_collections.id')
            ->get()->toArray();

        $browse                     = [];
        $browse_filed_data          = HomepageSetting::where('title', 'Browse By Price')->where('status', 'published')->orderBy('order_by', 'asc')->first();
        
        if( $browse_filed_data ) {

            $parent = [];
           
            $items_field = HomepageSettingItems::where('homepage_settings_id', $browse_filed_data->id)->get();
            
            foreach ($items_field as $key => $data_field) {
                $tmp = [];
                $tmp['name'] = ($data_field->start_size == 0 ? 'Below ' : $data_field->start_size ).'-'.($data_field->end_size == 0 ? 'Above' : $data_field->end_size);
                $tmp['slug'] = $data_field->start_size.'-'.$data_field->end_size;
                $parent[] = $tmp;
            }

        }

        $browse = $parent;

        $response = $this->getAttributeFilter($category_slug);
        
        $response['categories'] =  $categories;          
        $response['discounts'] = $discounts;          
        $response['sizes'] = $sizes;
        $response['prices'] = $browse;
        $response['collection'] = $collection;          
        $response['handpicked'] = $handpicked;
        $response['sort_by'] =  $sort_by;          

        return $response;
    }

    public function getProducts(Request $request)
    {
        $page                   = $request->page ?? 0;
        $take                   = $request->take ?? 12;
        $filter_category        = $request->category;
        $filter_sub_category    = $request->scategory;
        $filter_availability    = $request->availability;
        $filter_brand           = $request->brands;
        $filter_discount        = $request->discounts;
        $filter_attribute       = $request->attribute_category ?? '';
        $sort                   = $request->sort_by;
        $price                  = $request->prices;
        $size                   = $request->sizes;
        
        $filter_availability_array = [];
        $filter_attribute_array = [];
        $filter_brand_array = [];
        $filter_discount_array = [];
        $filter_price_array = [];
        $filter_size_array = [];
        $tmp_price = [];

        $price_start = 0;
        $price_end = 0;

        if (isset($price) && !empty($price)) {            
            $filter_price_array = explode("_", $price);        
           
        }
        
        if (isset($filter_attribute) && !empty($filter_attribute)) {            
            $filter_attribute_array = explode("-", $filter_attribute);
        }
        if (isset($filter_availability) && !empty($filter_availability)) {
            $filter_availability_array = explode("-", $filter_availability);
        }
        if (isset($filter_brand) && !empty($filter_brand)) {
            $filter_brand_array     = explode("_", $filter_brand);
        }
        if (isset($size) && !empty($size)) {
            $filter_size_array     = explode("_", $size);
        }
        
        $discount_start_value = '';
        $discount_end_value = '';

        if (isset($filter_discount) && !empty($filter_discount)) {
            $filter_discount_array     = explode("_", $filter_discount);
            if( !empty($filter_discount_array ) ) {
                $dis_array = [];
                foreach ($filter_discount_array as $dis_arr) {
                    $dis_array = array_merge( explode('-', $dis_arr), $dis_array) ;
                }
            }
            if( !empty( $dis_array)) {
                $dis_array = array_unique($dis_array);
                sort($dis_array);

                $discount_start_value = current($dis_array);
                $discount_end_value = end($dis_array);

            }
          
        }

        $productAttrNames = [];
        if (isset($filter_attribute_array) && !empty($filter_attribute_array)) {
            $productWithData = ProductWithAttributeSet::whereIn('id', $filter_attribute_array)->get();
            if (isset($productWithData) && !empty($productWithData)) {
                foreach ($productWithData as $attr) {
                    $productAttrNames[] = $attr->title;
                }
            }
        }

        $take_limit = $take ?? 12;
        $total = Product::select('products.*')->where('products.status', 'published')
            ->join('product_categories', 'product_categories.id', '=', 'products.category_id')
            ->leftJoin('product_categories as parent', 'parent.id', '=', 'product_categories.parent_id')
            ->join('brands', 'brands.id', '=', 'products.brand_id')
            ->when($filter_category != '', function ($q) use ($filter_category) {
                $q->where(function ($query) use ($filter_category) {
                    return $query->where('product_categories.slug', $filter_category)->orWhere('parent.slug', $filter_category);
                });
            })
            ->when($filter_sub_category != '', function ($q) use ($filter_sub_category) {
                return $q->where('product_categories.slug', $filter_sub_category);
            })
            ->when($filter_availability != '', function ($q) use ($filter_availability_array) {
                return $q->whereIn('products.stock_status', $filter_availability_array);
            })
            ->when($filter_brand != '', function ($q) use ($filter_brand_array) {
                return $q->whereIn('brands.slug', $filter_brand_array);
            })
                     
            ->when( $filter_attribute != '' || $filter_size_array != '', function($q){
                $q->join('product_with_attribute_sets', 'product_with_attribute_sets.product_id', '=', 'products.id');
            } )
            ->when( $discount_start_value != '' && $discount_end_value != '', function($q) use($discount_start_value, $discount_end_value) {
                
                $q->where(function ($query) use ($discount_start_value, $discount_end_value) {
                    return $query->whereRaw('ABS(gbs_products.discount_percentage) >= '. $discount_start_value )
                                ->whereRaw('ABS(gbs_products.discount_percentage) <= '. $discount_end_value);
                });

            })
            ->when($filter_attribute != '', function ($q) use ($productAttrNames) {
                return $q->whereIn('product_with_attribute_sets.title', $productAttrNames);
            })
            ->when($filter_size_array != '', function($q) use($filter_size_array) {
                $q->where('product_with_attribute_sets.title', 'size');
                $q->where(function($query) use($filter_size_array){
                    if( count($filter_size_array) > 1) {
                        $i = 1;
                        foreach ($filter_size_array as $size_arr) {
                            if( $i == 1){

                                $query->where('product_with_attribute_sets.attribute_values', $size_arr );
                            } else {

                                $query->orWhere('product_with_attribute_sets.attribute_values', $size_arr );
                            }
                            $i++;
                        }

    
                    } 
                });                
                
            })
            ->when($filter_price_array != '', function ($q) use ($filter_price_array) {
                // dd( $filter_price_array );
                if(count($filter_price_array) > 0 ){
                    $j = 1;
                    foreach ($filter_price_array as $price_var) {
                        $test_price = explode('-', $price_var);
                        if($j == 1){

                            $q->where(function ($query) use ($test_price) {
                                return $query->where('products.mrp', '>=', current($test_price))
                                            ->where('products.mrp','<=', end($test_price));
                            });

                        } else {
                            $q->orWhere(function ($query) use ($test_price) {
                                return $query->where('products.mrp', '>=', current($test_price))
                                            ->where('products.mrp','<=', end($test_price));
                            });
                        }
                        $j++;
                    }
                }
                
            })
            ->when($sort == 'price-high-to-low', function ($q) {
                $q->orderBy('products.mrp', 'desc');
            })
            ->when($sort == 'price-low-to-high', function ($q) {
                $q->orderBy('products.mrp', 'asc');
            })
            ->when($sort == 'is_featured', function ($q) {
                $q->orderBy('products.is_featured', 'desc');
            })
            ->where('products.stock_status', 'in_stock')
            ->count();

        $details = Product::select('products.*')->where('products.status', 'published')
            ->join('product_categories', 'product_categories.id', '=', 'products.category_id')
            ->leftJoin('product_categories as parent', 'parent.id', '=', 'product_categories.parent_id')
            ->join('brands', 'brands.id', '=', 'products.brand_id')
            ->when($filter_category != '', function ($q) use ($filter_category) {
                $q->where(function ($query) use ($filter_category) {
                    return $query->where('product_categories.slug', $filter_category)->orWhere('parent.slug', $filter_category);
                });
            })
            ->when($filter_sub_category != '', function ($q) use ($filter_sub_category) {
                return $q->where('product_categories.slug', $filter_sub_category);
            })
            ->when($filter_availability != '', function ($q) use ($filter_availability_array) {
                return $q->whereIn('products.stock_status', $filter_availability_array);
            })
            ->when($filter_brand != '', function ($q) use ($filter_brand_array) {
                return $q->whereIn('brands.slug', $filter_brand_array);
            })
           
            ->when( $filter_attribute != '' || $filter_size_array != '', function($q){
                $q->join('product_with_attribute_sets', 'product_with_attribute_sets.product_id', '=', 'products.id');
            })
            ->when( $discount_start_value != '' && $discount_end_value != '', function($q) use($discount_start_value, $discount_end_value) {
                
                $q->where(function ($query) use ($discount_start_value, $discount_end_value) {
                    return $query->whereRaw('ABS(gbs_products.discount_percentage) >= '. $discount_start_value )
                                ->whereRaw('ABS(gbs_products.discount_percentage) <= '. $discount_end_value);
                });

            })
            ->when($filter_size_array != '', function($q) use($filter_size_array) {
                $q->where('product_with_attribute_sets.title', 'size');
                $q->where(function($query) use($filter_size_array){
                    if( count($filter_size_array) > 0) {
                        $i = 1;
                        foreach ($filter_size_array as $size_arr) {
                            if( $i == 1){

                                $query->where('product_with_attribute_sets.attribute_values', $size_arr );
                            } else {

                                $query->orWhere('product_with_attribute_sets.attribute_values', $size_arr );
                            }
                            $i++;
                        }
    
                    } 
                });                
                
            })
            ->when($filter_attribute != '', function ($q) use ($productAttrNames) {
                
                return $q->whereIn('product_with_attribute_sets.title', $productAttrNames);
            })
            ->when($filter_price_array != '', function ($q) use ($filter_price_array) {
                // dd( $filter_price_array );
                if(count($filter_price_array) > 0 ){
                    $j = 1;
                    foreach ($filter_price_array as $price_var) {
                        $test_price = explode('-', $price_var);
                        if($j == 1){

                            $q->where(function ($query) use ($test_price) {
                                return $query->where('products.mrp', '>=', current($test_price))
                                            ->where('products.mrp','<=', end($test_price));
                            });

                        } else {
                            $q->orWhere(function ($query) use ($test_price) {
                                return $query->where('products.mrp', '>=', current($test_price))
                                            ->where('products.mrp','<=', end($test_price));
                            });
                        }
                        $j++;
                    }
                }
                
            })
            ->when($sort == 'price-high-to-low', function ($q) {
                $q->orderBy('products.mrp', 'desc');
            })
            ->when($sort == 'price-low-to-high', function ($q) {
                $q->orderBy('products.mrp', 'asc');
            })
            ->when($sort == 'is_featured', function ($q) {
                $q->orderBy('products.is_featured', 'desc');
            })
            ->where('products.stock_status', 'in_stock')
            ->groupBy('products.id')
            ->skip(0)->take($take_limit)
            ->get();

        $tmp = [];
        if (isset($details) && !empty($details)) {
            foreach ($details as $items) {
                $tmp[] = getProductApiData($items);
            }
        }

        // if ($total < $limit) {
        //     $to = $total;
        // }
        $to = count($details);

        return array('products' => $tmp, 'total_count' => $total, 'from' => ($total == 0 ? '0' : '1'), 'to' => $to);
    }

    public function getProductBySlug(Request $request)
    {
        $product_url = $request->product_url;
        $customer_id = $request->customer_id;
        $items = Product::where('product_url', $product_url)->first();
        if ($items) {
            $return = getProductApiData($items, $customer_id);
        }
        return $return ?? [];
    }

    public function globalSearch(Request $request)
    {
        $search_type = $request->search_type;
        $query = $request->search_field;
        $take = $request->take ?? 10;

        $searchData = [];
        $error = 0;
        if (!empty($query)) {

            $productInfo = Product::where(function ($qr) use ($query) {
                $qr->where('product_name', 'like', "%{$query}%")
                    ->orWhere('hsn_code', 'like', "%{$query}%")
                    ->orWhere('sku', 'like', "%{$query}%");
            })->where('status', 'published')->where('products.stock_status', 'in_stock')
                ->skip(0)->take($take)->get();

            if (count($productInfo) == 0) {
                $productInfo = Product::where(function ($qr) use ($query) {
                    $qr->whereRaw("MATCH (gbs_products.product_name) AGAINST ('" . $query . "' IN BOOLEAN MODE)")
                        ->orWhere('sku', 'like', "%{$query}%");
                })->where('status', 'published')
                ->where('products.stock_status', 'in_stock')->skip(0)->take($take)->get();
            }

            if (isset($productInfo) && !empty($productInfo) && count($productInfo) > 0) {
                $error = 1;
                foreach ($productInfo as $items) {
                    $searchData[] = getProductApiData($items);
                }
            } else {
                $pro = [];
                $pro['has_data']        = 'no';
                $pro['message']         = 'No record found';

                $searchData[] = $pro;
            }
        }

        return array('products' => $searchData, 'status' => $error);
    }

    public function getOtherCategories(Request $request)
    {

        $category       = $request->category;

        $otherCategory   = ProductCategory::select('id', 'name', 'slug')
            ->when($category != '', function ($q) use ($category) {
                $q->where('slug', '!=', $category);
            })
            ->where(['status' => 'published', 'parent_id' => 0])
            ->orderBy('order_by', 'asc')
            ->get();
        $data = [];
        if (isset($otherCategory) && !empty($otherCategory)) {
            foreach ($otherCategory as $item) {

                $tmp = [];
                $tmp['id'] = $item->id;
                $tmp['name'] = $item->name;
                $tmp['slug'] = $item->slug;
                $tmp['description'] = $item->description;

                $imagePath              = $item->image;

                if (!Storage::exists($imagePath)) {
                    $path               = asset('assets/logo/no_Image.jpg');
                } else {
                    $url                = Storage::url($imagePath);
                    $path               = asset($url);
                }

                $tmp['image'] = $path;

                $data[] = $tmp;
            }
        }
        return $data;
    }

    public function getAttributeFilter($category_slug = '')
    {
        
        if( $category_slug ) {
            $productCategory = ProductCategory::where('slug', $category_slug)->first();
        }
        
        $cat_id = $productCategory->id ?? '' ;
        $brands = Product::select('brands.id', 'brands.brand_name as name', 'brands.slug')
            ->join('brands', 'brands.id', '=', 'products.brand_id')
            ->join('product_categories', function ($join) {
                $join->on('product_categories.id', '=', 'products.category_id');
                $join->orOn('product_categories.parent_id', '=', 'products.category_id');
            })
            ->when($cat_id != '', function($query) use($cat_id) {
                $query->where(function ($query) use ($cat_id) {
                    return $query->where('product_categories.id', $cat_id)->orWhere('product_categories.parent_id', $cat_id);
                });
            })
            ->where('products.stock_status', 'in_stock')
            ->where('products.status', 'published')->groupBy('products.brand_id')
            ->get()->toArray();

        $whereIn = [];
        if( isset( $productCategory ) && !empty( $productCategory ) ) {
            $whereIn[] = $productCategory->id;
            if (isset($productCategory->childCategory) && !empty($productCategory->childCategory)) {
                foreach ($productCategory->childCategory  as $items) {
                    $whereIn[] = $items->id;
                }
            }
        } else {
            $productCategory = ProductCategory::where('status', 'published')->get();
            if( isset( $productCategory ) && !empty($productCategory) ) {
                foreach ($productCategory as $catItem) {
                    $whereIn[] = $catItem->id;
                    if (isset($catItem->childCategory) && !empty($catItem->childCategory)) {
                        foreach ($catItem->childCategory  as $itemsOne) {
                            $whereIn[] = $itemsOne->id;
                        }
                    }
                }
            }
        }

        $whereIn = array_unique($whereIn);
        $data = [];
        $attributes = [];

        $topLevelData = ProductAttributeSet::select('product_attribute_sets.*')->join('product_with_attribute_sets', 'product_with_attribute_sets.product_attribute_set_id', '=', 'product_attribute_sets.id')
                                            ->join('products', 'products.id', '=', 'product_with_attribute_sets.product_id')
                                            ->whereIn('product_attribute_sets.product_category_id', $whereIn)
                                            ->where(['products.status' => 'published', 'products.stock_status' => 'in_stock'])
                                            ->groupBy('product_attribute_sets.id')
                                            ->orderBy('product_attribute_sets.id', 'asc')->get();

        if (isset($topLevelData) && !empty($topLevelData)) {
            foreach ($topLevelData as $vals) {
                $tmp = [];
                $tmp['id'] = $vals->id;
                $tmp['title'] = $vals->title;
                $tmp['slug'] = $vals->slug;
                
                $secondLevelData = ProductAttributeSet::select('product_with_attribute_sets.id', 'product_with_attribute_sets.title', 'product_with_attribute_sets.attribute_values', 'product_with_attribute_sets.is_overview', 'product_with_attribute_sets.order_by' )
                                        ->join('product_with_attribute_sets', 'product_with_attribute_sets.product_attribute_set_id', '=', 'product_attribute_sets.id')
                                        ->join('products', 'products.id', '=', 'product_with_attribute_sets.product_id')
                                        ->whereIn('product_attribute_sets.product_category_id', $whereIn)
                                        ->where(['products.status' => 'published', 'products.stock_status' => 'in_stock'])
                                        ->where('product_attribute_sets.id', $vals->id)
                                        ->where('product_with_attribute_sets.status', 'published')
                                        ->groupBy('product_with_attribute_sets.title')
                                        ->orderBy('product_with_attribute_sets.title', 'asc')->get();
                
                if( isset( $secondLevelData ) && !empty( $secondLevelData ) ) {
                    foreach ($secondLevelData as $secondValue ) {
                        $sec = [];
                        $sec['id'] = $secondValue->id;
                        $sec['title'] = $secondValue->title;

                        $childItems = ProductAttributeSet::select('product_with_attribute_sets.title', 'product_with_attribute_sets.attribute_values', 'product_with_attribute_sets.is_overview', 'product_with_attribute_sets.order_by' )
                                        ->join('product_with_attribute_sets', 'product_with_attribute_sets.product_attribute_set_id', '=', 'product_attribute_sets.id')
                                        ->join('products', 'products.id', '=', 'product_with_attribute_sets.product_id')
                                        ->whereIn('product_attribute_sets.product_category_id', $whereIn)
                                        ->where(['products.status' => 'published', 'products.stock_status' => 'in_stock'])
                                        ->where('product_attribute_sets.id', $vals->id)
                                        ->where('product_with_attribute_sets.status', 'published')
                                        ->where('product_with_attribute_sets.title', $secondValue->title )
                                        ->orderBy('product_with_attribute_sets.title', 'asc')->get()->toArray();
                        $sec['items'] = $childItems;

                        $tmp['items'] = $sec;
                    }
                }
                // $tmp['items'] 
                

                $attributes[] = $tmp;
            }
        }
        return array( 'brands' => $brands ?? []);
    }

    public function exclusiveProduct()
    {
        $product_data = Product::join('sub_categories', 'sub_categories.id', '=', 'products.label_id')
                            ->join('main_categories', 'main_categories.id', '=', 'sub_categories.parent_id')
                            ->where('main_categories.slug', 'product-labels')
                            ->where('products.stock_status', 'in_stock')
                            ->where('products.status', 'published')->get();
        $data = [];
        if( isset( $product_data ) && !empty($product_data)){
            foreach ($product_data as $item ) {
                $data[] = getProductApiData($item);
            }
        }
        return array('products' => $data, 'error' => 1);
    }
}
