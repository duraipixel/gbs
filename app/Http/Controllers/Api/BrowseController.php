<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\HomePageSetting\HomepageSetting; 
use App\Models\HomePageSetting\HomepageSettingItems; 
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class BrowseController extends Controller
{
    public function index()
    {
        /*$browse_filed = HomepageSetting::select('id','title')->where('status','published')->orderBy('order_by','asc')->get();
        foreach ($browse_filed as $key=>$data) {
            $browse_filed[$key]['children'] = HomepageSettingItems::where('homepage_settings_id',$data->id)->get();
         }
          return response()->json(['data'=>$browse_filed]); */
         $browse                   = [];
         $browse_filed_data = HomepageSetting::select('id','title')->where('status','published')->orderBy('order_by','asc')->get();
          foreach ($browse_filed_data as $key=>$data) {
         
         $browse['id']             = $data->id;
         $browse['title']          = $data->title;
         $items_field= HomepageSettingItems::where('homepage_settings_id',$data->id)->get();
         $items = [];
            foreach($items_field as $key=>$data_field)
            {
                $tmp = [];
                $tmp['start_size'] = $data_field->start_size;
                $tmp['end_size'] = $data_field->end_size;
                $image           = 'public/home_settings/'.$data_field->setting_image_name;
                $mobUrl                 = Storage::url($image);
                $pathbrowse             = asset($mobUrl);
                $tmp['path'] = $pathbrowse;

                $items[] = $tmp;
              
            }
            $browse['children'] = $items;
        }
        return response()->json(['data'=>$browse]); 
    }
}
