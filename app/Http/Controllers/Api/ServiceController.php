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
            $temp['email']          = $val->email;
            $temp['contact_no']     = $val->contact_no;
            
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
            $temp['banner_mb'] = $val->banner_mb ;
            $temp['status'] = $val->status;
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
                    $temp1['status'] = $val->status;
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
        $data = ServiceCenter::where('status','published')->where('slug',$slug)->select('id','parent_id','title','slug','banner','banner_mb','description','pincode','address','latitude','longitude','email','contact_no','status','order_by')->get();
        return response()->json(['data'=>$data]);

    }
}
