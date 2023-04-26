<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Master\Brands;
use App\Models\StoreLocator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class StoreLocatorController extends Controller
{

    public function getStoreLocator(Request $request)
    {

        $brand_id = $request->brand_id ?? '';
        $post_code = $request->post_code ?? '';
        $center_id = $request->center_id ?? '';

        $data = StoreLocator::select('store_locators.*')
            ->join('store_locator_brands', 'store_locator_brands.store_locator_id', '=', 'store_locators.id')
            ->join('store_locator_pincodes', 'store_locator_pincodes.store_locator_id', '=', 'store_locators.id')
            ->join('brands', 'brands.id', '=', 'store_locator_brands.brand_id')
            ->where('store_locators.status', 'published')
            // ->where('store_locators.parent_id', 0)
            ->when($brand_id != '', function ($query) use ($brand_id) {
                $query->where('brands.id', $brand_id);
            })
            ->when($post_code != '', function ($query) use ($post_code) {
                $query->where('store_locator_pincodes.pincode', $post_code);
                $query->orWhere('store_locators.pincode', $post_code);
            })
            ->when($center_id != '', function ($query) use ($center_id) {
                $query->where('store_locators.id', $center_id);
            })
            ->groupBy('store_locators.id')
            ->get();

        $params = [];

        if (isset($data) && !empty($data)) {
            foreach ($data as $item) {
                $params[] = $this->getList($item);
            }
        }

        return response()->json(['data' => $params]);
    }

    public function getStoreLocatorDetail(Request $request)
    {

        $slug = $request->slug;
        $data = StoreLocator::select('store_locators.*')
            ->join('store_locator_brands', 'store_locator_brands.store_locator_id', '=', 'store_locators.id')
            ->join('store_locator_pincodes', 'store_locator_pincodes.store_locator_id', '=', 'store_locators.id')
            ->join('brands', 'brands.id', '=', 'store_locator_brands.brand_id')
            ->where('store_locators.status', 'published')
            // ->where('store_locators.parent_id', 0)
            ->when($slug != '', function ($query) use ($slug) {
                $query->where('store_locators.slug', $slug);
            })
            ->groupBy('store_locators.id')
            ->first();

        $response = [];
        if (isset($data) && !empty($data)) {
            $response = $this->getList($data);
        }
        return $response;
    }

    public function getList($data)
    {

        $temp['id']             = $data->id;
        $temp['title']          = $data->title;
        $temp['slug']           = $data->slug;
        $temp['parent_id']      = $data->parent_id;
        $temp['description']    = $data->description;
        $temp['pincode']        = $data->pincode;
        $temp['address']        = $data->address;
        $temp['map_link']       = $data->map_link;
        $temp['image_360_link'] = $data->image_360_link;
        $offers = [];
        if (isset($data->offers) && !empty($data->offers)) {
            foreach ($data->offers as $item) {
                $path = '';
                $tmp = [];
                $tmp['id'] = $item->id;
                $tmp['title'] = $item->title;
                $offerImagePath = $item->image;
                if (!Storage::exists($offerImagePath) || $offerImagePath === null) {
                    $path               = asset('assets/logo/no_Image.jpg');
                } else {
                    $url                    = Storage::url($offerImagePath);
                    $path                   = asset($url);
                }

                $tmp['image'] = $path;
                $offers[] = $tmp;
            }
        }
        $temp['offers'] = $offers;
        $usedBrands = [];
        $brandarr = [];
        $near_pincodes = [];
        if (isset($data->nearPincodes) && !empty($data->nearPincodes)) {
            foreach ($data->nearPincodes as $items) {
                $near_pincodes[] = $items->pincode;
            }
        }

        $temp['near_pincodes'] = $near_pincodes;
        if (isset($data->brands) && !empty($data->brands)) {

            $usedBrands = array_column($data->brands->toArray(), 'brand_id');
            $brandall = Brands::whereIn('id', $usedBrands)->get();

            if (isset($brandall) && !empty($brandall)) {
                foreach ($brandall as $item) {
                    $parent = [];
                    $parent['id'] = $item->id;
                    $parent['name'] = $item->brand_name;
                    $parent['slug'] = $item->slug;

                    $brandLogoPath          = 'public/brands/' . $item->id . '/default/' . $item->brand_logo;

                    if (!Storage::exists($brandLogoPath) || $item->brand_logo === null) {
                        $path               = asset('assets/logo/no_Image.jpg');
                    } else {
                        $url                    = Storage::url($brandLogoPath);
                        $path                   = asset($url);
                    }

                    $parent['image'] = $path;
                    $brandarr[] = $parent;
                }
            }
        }

        $temp['brands'] = $brandarr;

        if (isset($data->email) && !empty($data->email)) {
            $arrEmail = json_decode($data->email);
            $data->email = implode(',', $arrEmail);
            $temp['email']          = $data->email;
        } else {
            $temp['email']       = '';
        }

        if (isset($data->contact_no) && !empty($data->contact_no)) {
            $arrContact             = json_decode($data->contact_no);
            $data->contact_no      = implode(',', $arrContact);
            $temp['contact_no']     = $data->contact_no;
        } else {
            $temp['contact_no']       = '';
        }

        if ($data->banner) {
            $url = Storage::url($data->banner);
            $data->banner = asset($url);
        } else {
            $data->banner = asset('userImage/no_Image.jpg');
        }
        $temp['banner'] = $data->banner;

        if ($data->banner_mb) {
            $url = Storage::url($data->banner_mb);
            $data->banner_mb = asset($url);
        } else {
            $data->banner_mb = asset('userImage/no_Image.jpg');
        }
        $temp['store_image']        = $data->banner_mb;
        $temp['status']             = $data->status;
        $temp['meta_title']         = $data->meta->meta_title ?? "";
        $temp['meta_keyword']       = $data->meta->meta_keyword ?? "";
        $temp['meta_description']   = $data->meta->meta_description ?? "";
        return $temp;
    }
}
