<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category\SubCategory;
use App\Models\Product\Product;
use App\Models\Product\ProductAttributeSet;
use App\Models\Product\ProductCategory;
use App\Models\Product\ProductCollection;
use App\Models\Product\ProductWithAttributeSet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class FilterController extends Controller
{
    public function getFilterStaticSideMenu()
    {

        $product_availability = array(
            'in_stock' => 'In Stock',
            'coming_soon' => 'Upcoming',
        );

        $sory_by                = array(
            array('id' => null, 'name' => 'Featured', 'slug' => 'is_featured'),
            array('id' => null, 'name' => 'Price: High to Low', 'slug' => 'price_high_to_low'),
            array('id' => null, 'name' => 'Price: Low to High', 'slug' => 'price_low_to_high'),
        );

        $discounts              = ProductCollection::select('id', 'collection_name', 'slug')
            ->where('can_map_discount', 'yes')
            ->where('status', 'published')
            ->orderBy('order_by', 'asc')
            ->get()->toArray();

        $collection             = ProductCollection::select('id', 'collection_name', 'slug')
            ->join('product_collections_products', 'product_collections_products.product_collection_id', '=', 'product_collections.id')
            ->join('products', 'products.id', '=', 'product_collections_products.product_id')
            ->where('products')
            ->where('can_map_discount', 'no')
            ->where('show_home_page', 'yes')
            ->where('status', 'published')
            ->orderBy('order_by', 'asc')
            ->groupBy('product_collections.id')
            ->get()->toArray();

        $response               = array(
            'product_availability' => $product_availability,
            'sory_by' => $sory_by,
            'discounts' => $discounts,
            'collection' => $collection
        );

        return $response;
    }

    public function getProducts(Request $request)
    {

        $page                   = $request->page ?? 0;
        $take                   = $request->take ?? 0;
        $filter_category        = $request->category;
        $filter_sub_category    = $request->scategory;
        $filter_availability    = $request->availability;
        $filter_brand           = $request->brand;
        $filter_discount        = $request->discount;
        $filter_attribute       = $request->attributes_category;
        $sort                   = $request->sort;
        
        $filter_availability_array = [];
        $filter_attribute_array = [];
        $filter_brand_array = [];
        $filter_discount_array = [];
        $filter_booking     = $request->booking;
        if (isset($filter_attribute) && !empty($filter_attribute)) {
            
            $filter_attribute_array = explode("-", $filter_attribute);
        }
        if (isset($filter_availability) && !empty($filter_availability)) {
            $filter_availability_array = explode("-", $filter_availability);
        }
        if (isset($filter_brand) && !empty($filter_brand)) {
            $filter_brand_array     = explode("_", $filter_brand);
        }

        if (isset($filter_discount) && !empty($filter_discount)) {
            $filter_discount_array     = explode("_", $filter_discount);
        }

        $productAttrNames = [];
        if( isset( $filter_attribute_array ) && !empty( $filter_attribute_array ) ) {
            $productWithData = ProductWithAttributeSet::whereIn('id', $filter_attribute_array)->get();
            if( isset( $productWithData ) && !empty( $productWithData ) ) {
                foreach ( $productWithData as $attr ) {
                    $productAttrNames[] = $attr->title;
                }
            }
        }

        $limit = 12;
        $skip = (isset($page) && !empty($page)) ? ($page * $limit) : 0;

        $from   = 1 + ($page * $limit);        

        // $take_limit = $limit + ($page * $limit);
        $take_limit = $take ?? 1;
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
            ->when($filter_booking == 'video_shopping', function ($q) {
                return $q->where('products.has_video_shopping', 'yes');
            })
            ->when($filter_discount != '', function ($q) use ($filter_discount_array) {
                $q->join('product_collections_products', 'product_collections_products.product_id', '=', 'products.id');
                $q->join('product_collections', 'product_collections.id', '=', 'product_collections_products.product_collection_id');
                return $q->whereIn('product_collections.slug', $filter_discount_array);
            })
            ->when($filter_attribute != '', function ($q) use ($productAttrNames) {
                $q->join('product_with_attribute_sets', 'product_with_attribute_sets.product_id', '=', 'products.id');
                return $q->whereIn('product_with_attribute_sets.title', $productAttrNames);
            })
            ->when($sort == 'price_high_to_low', function ($q) {
                $q->orderBy('products.price', 'desc');
            })
            ->when($sort == 'price_low_to_high', function ($q) {
                $q->orderBy('products.price', 'asc');
            })
            ->when($sort == 'is_featured', function ($q) {
                $q->orderBy('products.is_featured', 'desc');
            })
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
            ->when($filter_booking == 'video_shopping', function ($q) {
                return $q->where('products.has_video_shopping', 'yes');
            })
            ->when($filter_discount != '', function ($q) use ($filter_discount_array) {
                $q->join('product_collections_products', 'product_collections_products.product_id', '=', 'products.id');
                $q->join('product_collections', 'product_collections.id', '=', 'product_collections_products.product_collection_id');
                return $q->whereIn('product_collections.slug', $filter_discount_array);
            })
            ->when($filter_attribute != '', function ($q) use ($productAttrNames) {
                $q->join('product_with_attribute_sets', 'product_with_attribute_sets.product_id', '=', 'products.id');
                return $q->whereIn('product_with_attribute_sets.title', $productAttrNames);
            })
            ->when($sort == 'price_high_to_low', function ($q) {
                $q->orderBy('products.mrp', 'desc');
            })
            ->when($sort == 'price_low_to_high', function ($q) {
                $q->orderBy('products.mrp', 'asc');
            })
            ->when($sort == 'is_featured', function ($q) {
                $q->orderBy('products.is_featured', 'desc');
            })
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
        if( $items ) {
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
        $error = 1;
        if (!empty($query)) {

            $productInfo = Product::where(function ($qr) use ($query) {
                $qr->where('product_name', 'like', "%{$query}%")
                    ->orWhere('hsn_code', 'like',"%{$query}%")
                    ->orWhere('sku', 'like', "%{$query}%");
            })->where('status', 'published')
            ->skip(0)->take($take)->get();
            
            if (count($productInfo) == 0) {
                $productInfo = Product::where(function ($qr) use ($query) {
                    $qr->whereRaw("MATCH (gbs_products.product_name) AGAINST ('" . $query . "' IN BOOLEAN MODE)")
                        ->orWhere('sku', 'like', "%{$query}%");
                })->where('status', 'published')->skip(0)->take($take)->get();
            }

            if (isset($productInfo) && !empty($productInfo) && count($productInfo) > 0) {
                $error = 0;
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
        if( isset( $otherCategory ) && !empty( $otherCategory ) ) {
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

    public function getDynamicFilterCategory(Request $request)
    {
        $category_slug = $request->category_slug;
        // $category_slug = 'keyboard-keyboard';

        $productCategory = ProductCategory::where('slug', $category_slug)->first();
        if( isset( $productCategory ) && !empty( $productCategory ) ) {

            $whereIn = [];
            $whereIn[] = $productCategory->id;
            if( isset( $productCategory->childCategory ) && !empty( $productCategory->childCategory ) ) {
                foreach ( $productCategory->childCategory  as $items ) {
                    $whereIn[] = $items->id; 
                }
            }
            
            $data = [];
            // $attributeInfo = ProductAttributeSet::whereIn('product_category_id', $whereIn)->where('is_searchable', '1')->get();

            $filterData = ProductAttributeSet::select('product_attribute_sets.*')
                            ->join('product_categories', 'product_categories.id', '=', 'product_attribute_sets.product_category_id')
                            ->join('products', function($join){
                                $join->on('products.category_id', '=', 'product_categories.id');
                                $join->orOn('products.category_id', '=', 'product_categories.parent_id');
                            })
                            // ->join('product_with_attribute_sets', 'product_attribute_sets.id', '=', 'product_with_attribute_sets.product_attribute_set_id')
                            ->where('product_categories.slug', $category_slug )
                            // ->where('product_with_attribute_sets.status','published')
                            ->groupBy('product_attribute_sets.id')
                            ->get();
            
            if( isset( $filterData ) && !empty( $filterData ) ) {
                foreach ( $filterData as $item ) {
                                       
                    $tmp = [];
                    $tmp['filter_title'] = $item->title;
                    $tmp['filter_slug'] = $item->slug;
                    $tmp['filter_id'] = $item->id;
                    $tmp['child'] = $item->attributesFieldsByTitle ?? [];
                    $data[] = $tmp;
                    //get filter attributes
                }
            }
            return $data;

            
        }
    }
}
