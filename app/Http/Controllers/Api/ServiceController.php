<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ServiceCenter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ServiceController extends Controller
{
    
    public function getServiceCenter()
    {
        $data = ServiceCenter::where('status','published')->where('parent_id',0)->select('id','parent_id','title','slug','banner','banner_mb','description','pincode','address','latitude','longitude','email','contact_no','status','order_by')->get();
        
        $params = [];
        if( isset( $data ) && !empty( $data ) ) {

        foreach($data as $key=>$val){
            $temp = [];
            $temp['id']             = $val->id ;
            $temp['title']          = $val->title ;
            $temp['slug']           = $val->slug ;
            $temp['parent_id']      = $val->parent_id ;
            $temp['description']    = $val->description;
            $temp['pincode']        = $val->pincode;
            $temp['address']        = $val->address;
            $temp['latitude']       = $val->latitude;
            $temp['langitude']      = $val->langitude;

            if(isset($val->email) && !empty($val->email))
            {
                $arrEmail = json_decode( $val->email );
                $val->email = implode(',',$arrEmail);
                $temp['email']          = $val->email;
            }
            else{
                $temp['email']       = '' ;
            }
             
            if(isset($val->contact_no) && !empty($val->contact_no))
            {
                $arrContact             = json_decode( $val->contact_no );
                $val->contact_no      = implode(',',$arrContact);
                $temp['contact_no']     = $val->contact_no;
            }
            else{
                $temp['contact_no']       = '' ;
            }

            if($val->banner )
            {
                $url = Storage::url($val->banner );
                $val->banner = asset($url);
            }
            else{
                $val->banner = asset('userImage/no_Image.jpg');
            }
            $temp['banner'] = $val->banner ;

            if($val->banner_mb )
            {
                $url = Storage::url($val->banner_mb );
                $val->banner_mb = asset($url);
            }
            else{
                $val->banner_mb = asset('userImage/no_Image.jpg');
            }
            $temp['banner_mb']          = $val->banner_mb ;
            $temp['status']             = $val->status;
            $temp['meta_title']         = $val->meta->meta_title ?? "";
            $temp['meta_keyword']       = $val->meta->meta_keyword ?? "";
            $temp['meta_description']   = $val->meta->meta_description ?? "";

            if(isset($val->child) && !empty($val->child) && count($val->child) > 0)
            {
                foreach($val->child as $childData){
                    $temp1['id']            = $childData->id ;
                    $temp1['title']         = $childData->title ;
                    $temp1['slug']          =    $childData->slug ;
                    $temp1['parent_id']     = $val->parent_id ;
                    $temp1['description']   = $val->description;
                    $temp1['pincode']       = $val->pincode;
                    $temp1['address']       = $val->address;
                    $temp1['latitude']      = $val->latitude;
                    $temp1['langitude']     = $val->langitude;
                    $temp1['email']         = $val->email;
                    $temp1['contact_no']    = $val->contact_no;
                    
                    if($childData->banner )
                    {
                        $url = Storage::url($childData->banner );
                        $childData->banner = asset($url);
                    }
                    else{
                        $childData->banner = asset('userImage/no_Image.jpg');
                    }
                    $temp1['banner'] = $childData->banner ;

                    if($childData->banner_mb )
                    {
                        $url = Storage::url($childData->banner_mb );
                        $childData->banner_mb = asset($url);
                    }
                    else{
                        $childData->banner_mb = asset('userImage/no_Image.jpg');
                    }
                    $temp1['banner_mb'] = $childData->banner_mb ;
                    $temp1['status']    = $childData->status;
                    $temp1['meta_title']         = $childData->meta->meta_title ?? "";
                    $temp1['meta_keyword']       = $childData->meta->meta_keyword ?? "";
                    $temp1['meta_description']   = $childData->meta->meta_description ?? "";


                    $temp['child'][] = $temp1;
                }

            }
            $params[] = $temp;
            // $count= count($params);

        }

    }
    return response()->json(['data'=>$params]);
    }
    public function getServiceCenterDetail(Request $request)
    {
        $slug = $request->slug;
        $data = ServiceCenter::where('status','published')
        ->where('slug',$slug)
        ->select('id','parent_id','title','slug','banner','banner_mb','description','pincode','address','latitude','longitude','email','contact_no','status','order_by')->first();
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
