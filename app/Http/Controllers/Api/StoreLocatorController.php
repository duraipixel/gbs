<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Master\Brands;
use App\Models\StoreLocator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class StoreLocatorController extends Controller
{

    public function getStoreLocator()
    {

        $brand = Brands::where('status','published')->get();
        $data = StoreLocator::where('status','published')->get();
        $params = [];
        if( isset( $brand ) && !empty( $brand ) ) {
            foreach($brand as $key=>$val)
            {
                $temp = [];
                $temp['id']                 = $val->id;
                $temp['brand_name']         = $val->brand_name;
                $temp['slug']               = $val->slug;
                $temp['short_description']  = $val->short_description;
                $temp['status']             = $val->status;

                if($val->brand_logo )
                {
                    $url = Storage::url($val->brand_logo );
                    $val->brand_logo = asset($url);
                }
                else{
                    $val->brand_logo = asset('userImage/no_Image.jpg');
                }
                $temp['brand_logo']         = $val->brand_logo;
                if(isset($val->storeLocator) && !empty($val->storeLocator) && count($val->storeLocator) > 0)
                {
                    foreach($val->storeLocator as $store)
                    {
                        $temp1['id']            = $store->id;
                        $temp1['parent_id']     = $store->parent_id;
                        $temp1['brand_id']      = $store->brand_id;
                        $temp1['title']         = $store->title;
                        $temp1['slug']          = $store->slug;
                        $temp1['description']   = $store->description;
                        $temp1['address']       = $store->address;
                        $temp1['latitude']      = $store->latitude;
                        $temp1['longitude']     = $store->longitude;
                        
                        if(isset($store->email) && !empty($store->email))
                        {
                            $arrEmail = json_decode( $store->email );
                            $store->email = implode(',',$arrEmail);
                            $temp1['email']         = $store->email;
                        }
                        else{
                            $temp1['email']         = '' ;
                        }
                            
                        if(isset($store->contact_no) && !empty($store->contact_no))
                        {
                            $arrContact             = json_decode( $store->contact_no );
                            $store->contact_no      = implode(',',$arrContact);
                            $temp1['contact_no']    = $store->contact_no;
                        }
                        else{
                            $temp1['contact_no']         = '' ;
                        }

                        if($store->banner )
                        {
                            $url = Storage::url($store->banner );
                            $store->banner = asset($url);
                        }
                        else{
                            $store->banner = asset('userImage/no_Image.jpg');
                        }
                        $temp1['banner']         = $store->banner;

                        if($store->banner_mb )
                        {
                            $url = Storage::url($store->banner_mb );
                            $store->banner_mb = asset($url);
                        }
                        else{
                            $store->banner_mb = asset('userImage/no_Image.jpg');
                        }
                        $temp1['banner_mb']         = $store->banner_mb;

                        if($store->store_image )
                        {
                            $url = Storage::url($store->store_image );
                            $store->store_image = asset($url);
                        }
                        else{
                            $store->store_image = asset('userImage/no_Image.jpg');
                        }
                        $temp1['store_image']         = $store->store_image;

                        if($store->store_image_mb )
                        {
                            $url = Storage::url($store->store_image_mb );
                            $store->store_image_mb = asset($url);
                        }
                        else{
                            $store->store_image_mb = asset('userImage/no_Image.jpg');
                        }
                        $temp1['store_image_mb']            = $store->store_image_mb;
                        $temp1['meta_title']                = $store->meta->meta_title ?? '';
                        $temp1['meta_keyword']              = $store->meta->meta_keyword ?? '';
                        $temp1['meta_description']          = $store->meta->meta_description ?? '';

                        $temp1['status']    = $store->status;
                        $temp['child'][]    = $temp1;
                    }
                }
                $params[] = $temp;
            }
        }
        return response()->json(['data'=>$params]);
    }

    public function getStoreLocatorDetail(Request $request)
    {
        $slug = $request->slug;
        $data = StoreLocator::where('status','published')
                ->where('slug',$slug)
                ->select('id','brand_id','parent_id','title','slug','banner','banner_mb','store_image','store_image_mb','description','address','latitude','longitude','email','contact_no','status','order_by')
                ->first();

        if(isset($data->banner) && !empty($data->banner))
        {
            $url = Storage::url($data->banner );
            $data->banner = asset($url);
        }
        else{
            $data->banner = asset('userImage/no_Image.jpg');
        }
        if(isset($data->banner_mb) && !empty($data->banner_mb))
        {
            $url = Storage::url($data->banner_mb );
            $data->banner_mb = asset($url);
        }
        else{
            $data->banner_mb = asset('userImage/no_Image.jpg');
        }

        if(isset($data->store_image) && !empty($data->store_image))
        {
            $url = Storage::url($data->store_image );
            $data->store_image = asset($url);
        }
        else{
            $data->store_image = asset('userImage/no_Image.jpg');
        }
        if(isset($data->store_image_mb) && !empty($data->store_image_mb))
        {
            $url = Storage::url($data->store_image_mb );
            $data->store_image_mb = asset($url);
        }
        else{
            $data->store_image_mb = asset('userImage/no_Image.jpg');
        }


        if(isset($data->email) && !empty($data->email))
        {
            $arrEmail = json_decode( $data->email );
            $data->email = implode(',',$arrEmail);
        }
        else{
            $data->email        = '' ;
        }
         
        if(isset($data->contact_no) && !empty($data->contact_no))
        {
            $arrContact             = json_decode( $data->contact_no );
            $data->contact_no      = implode(',',$arrContact);
        }
        else{
            $data->contact_no       = '' ;
        }
        $data->meta                = $data->meta;

        return response()->json(['data'=>$data]);

    }
}
