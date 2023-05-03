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

        $product_availability = array(
            'in_stock' => 'In Stock',
            'coming_soon' => 'Upcoming',
        );

        $sort_by                = array(
            // array('id' => null, 'name' => 'Featured', 'slug' => 'is-featured'),
            array('id' => null, 'name' => 'Price: High to Low', 'slug' => 'price-high-to-low'),
            array('id' => null, 'name' => 'Price: Low to High', 'slug' => 'price-low-to-high'),
        );

        $discounts              = ProductCollection::select('product_collections.id', 'product_collections.collection_name as name', 'product_collections.slug')
            ->join('product_collections_products', 'product_collections_products.product_collection_id', '=', 'product_collections.id')
            ->join('products', 'products.id', '=', 'product_collections_products.product_id')
            ->where('products.stock_status', 'in_stock')
            ->where('products.status', 'published')
            ->where('product_collections.is_handpicked_collection', 'no')
            ->where('product_collections.can_map_discount', 'yes')
            ->where('product_collections.status', 'published')
            ->orderBy('product_collections.order_by', 'asc')
            ->groupBy('product_collections.id')
            ->get()->toArray();

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
        $browse_filed_data          = HomepageSetting::where('status', 'published')->orderBy('order_by', 'asc')->get();

        foreach ($browse_filed_data as $key => $data) {
            // dd( $data );
            $parent = [];
            $parent['id']             = $data->id;
            $parent['title']          = $data->title;
            $parent['color']          = $data->color;
            $parent['type']           = $data->fields->slug;
            $items_field = HomepageSettingItems::where('homepage_settings_id', $data->id)->get();
            $items = [];
            foreach ($items_field as $key => $data_field) {
                $tmp = [];
                $tmp['start'] = $data_field->start_size;
                $tmp['end'] = $data_field->end_size;
                $image           = $data_field->setting_image_name;
                $mobUrl          = Storage::url($image);
                $pathbrowse      = asset($mobUrl);
                $tmp['path'] = $pathbrowse;

                $items[] = $tmp;
            }
            $parent['children'] = $items;

            $browse[] = $parent;
        }

        $response = $this->getAttributeFilter($category_slug);
        
        // $response['product_availability'] =  $product_availability;          
        $response['sort_by'] =  $sort_by;          
        $response['discounts'] = $discounts;          
        $response['collection'] = $collection;          
        $response['handpicked'] = $handpicked;
        $response['browse_by'] = $browse;

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
        $price                  = $request->price;
        $size                   = $request->size;
        
        $filter_availability_array = [];
        $filter_attribute_array = [];
        $filter_brand_array = [];
        $filter_discount_array = [];
        $filter_price_array = [];
        $filter_size_array = [];

        $price_start = 0;
        $price_end = 0;

        if (isset($price) && !empty($price)) {            
            $filter_price_array = explode("_", $price);
            
            $tmp_price = [];
            if( isset( $filter_price_array ) && !empty( $filter_price_array )) {
                foreach ($filter_price_array as $itemsPrice ) {
                    # code...
                    $tmp_price = array_merge(explode('-', $itemsPrice), $tmp_price);
                }
            }
            if( $tmp_price ) {

                asort($tmp_price);
                $price_start = current($tmp_price);
                $price_end = end($tmp_price);

            }
           
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
        if (isset($filter_discount) && !empty($filter_discount)) {
            $filter_discount_array     = explode("_", $filter_discount);
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
           
            ->when($filter_discount != '', function ($q) use ($filter_discount_array) {
                $q->join('product_collections_products', 'product_collections_products.product_id', '=', 'products.id');
                $q->join('product_collections', 'product_collections.id', '=', 'product_collections_products.product_collection_id');
                return $q->whereIn('product_collections.slug', $filter_discount_array);
            })
            ->when($filter_attribute != '', function ($q) use ($productAttrNames) {
                $q->join('product_with_attribute_sets', 'product_with_attribute_sets.product_id', '=', 'products.id');
                return $q->whereIn('product_with_attribute_sets.title', $productAttrNames);
            })
            ->when($price_start || $price_end, function ($q) use ($price_start, $price_end) {
                $q->where(function ($query) use ($price_start, $price_end) {
                    return $query->where('products.mrp', '>=', $price_start)
                                ->where('products.mrp','<=', $price_end);
                });
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
            ->when($filter_discount != '', function ($q) use ($filter_discount_array) {
                $q->join('product_collections_products', 'product_collections_products.product_id', '=', 'products.id');
                $q->join('product_collections', 'product_collections.id', '=', 'product_collections_products.product_collection_id');
                return $q->whereIn('product_collections.slug', $filter_discount_array);
            })
            ->when($filter_attribute != '', function ($q) use ($productAttrNames) {
                $q->join('product_with_attribute_sets', 'product_with_attribute_sets.product_id', '=', 'products.id');
                return $q->whereIn('product_with_attribute_sets.title', $productAttrNames);
            })
            ->when($price_start || $price_end, function ($q) use ($price_start, $price_end) {
                $q->where(function ($query) use ($price_start, $price_end) {
                    return $query->where('products.mrp', '>=', $price_start)
                                ->where('products.mrp','<=', $price_end);
                });
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
            })->where('status', 'published')
                ->skip(0)->take($take)->get();

            if (count($productInfo) == 0) {
                $productInfo = Product::where(function ($qr) use ($query) {
                    $qr->whereRaw("MATCH (gbs_products.product_name) AGAINST ('" . $query . "' IN BOOLEAN MODE)")
                        ->orWhere('sku', 'like', "%{$query}%");
                })->where('status', 'published')->skip(0)->take($take)->get();
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
        return array('attributes' => $attributes ?? [], 'brands' => $brands ?? []);
    }
}
