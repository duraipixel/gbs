<?php

namespace App\Http\Controllers\HomePageSetting;

use App\Exports\HomepageSettingExport;
use App\Http\Controllers\Controller;
use App\Models\HomePageSetting\HomepageSetting;
use App\Models\HomePageSetting\HomepageSettingField;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use DataTables;
use Illuminate\Support\Facades\File;
use Carbon\Carbon;
use Illuminate\Support\Facades\Validator;
use Excel;

class HomepageSettingController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $data       = HomepageSetting::select('homepage_settings.*','homepage_setting_fields.title as field_name')
            ->leftJoin('homepage_setting_fields','homepage_setting_fields.id','=','homepage_settings.homepage_setting_field_id');
            $status     = $request->get('status');
            $keywords   = $request->get('search')['value'];

            $datatables =  DataTables::of($data)
                ->filter(function ($query) use ($keywords,$status) {
                    if ($status) {
                        return $query->where('homepage_settings.status', '=', "$status");
                    }
                    if ($keywords) {
                        $date = date('Y-m-d', strtotime($keywords));
                        return $query->where('homepage_settings.title', 'like', "%{$keywords}%")
                        ->orWhere('homepage_setting_fields.title', 'like', "%{$keywords}%")
                        ->orWhereDate("homepage_settings.created_at", $date);
                    }
                })
                ->addIndexColumn()
                ->editColumn('status', function ($row) {
                    $status = '<a href="javascript:void(0);" class="badge badge-light-'.(($row->status == 'published') ? 'success': 'danger').'" tooltip="Click to '.(($row->status == 'published') ? 'Unpublish' : 'Publish').'" onclick="return commonChangeStatus(' . $row->id . ',\''.(($row->status == 'published') ? 'unpublished': 'published').'\', \'homepage-setting\')">'.ucfirst($row->status).'</a>';
                    return $status;
                })
                ->editColumn('created_at', function ($row) {
                    $created_at = Carbon::createFromFormat('Y-m-d H:i:s', $row['created_at'])->format('d-m-Y');
                    return $created_at;
                })
    
                ->addColumn('action', function ($row) {
                    $edit_btn = '<a href="javascript:void(0);" onclick="return  openForm(\'homepage-setting\',' . $row->id . ')" class="btn btn-icon btn-active-primary btn-light-primary mx-1 w-30px h-30px" > 
                    <i class="fa fa-edit"></i>
                </a>';
                    $del_btn = '<a href="javascript:void(0);" onclick="return commonDelete(' . $row->id . ', \'homepage-setting\')" class="btn btn-icon btn-active-danger btn-light-danger mx-1 w-30px h-30px" > 
                <i class="fa fa-trash"></i></a>';
              
                    return $edit_btn.$del_btn;
                })
                ->rawColumns(['action', 'status', 'created_at']);
            return $datatables->make(true);
        }
        $title                  = "Homepage Setting";
        $breadCrum              = array('Homepage Setting');
        return view('platform.homepage_setting.homepage_setting.index', compact('title', 'breadCrum'));
    }
    public function modalAddEdit(Request $request)
    {
        $id                 = $request->id;
        $from               = $request->from;
        $info               = '';
        $modal_title        = 'Add Homepage Setting';
        $field = HomepageSettingField::where('status','published')->get();
        if (isset($id) && !empty($id)) {
            $info           = HomepageSetting::find($id);
           
            $modal_title    = 'Update Homepage Setting';
        }

        return view('platform.homepage_setting.homepage_setting.add_edit_modal', compact('info', 'modal_title', 'from','field'));
    }
    public function saveForm(Request $request,$id = null)
    {
        $id             = $request->id;
        $validator      = Validator::make($request->all(), [
                                'title' => 'required|string|unique:homepage_settings,title,' . $id . ',id,deleted_at,NULL',
                                'homepage_setting_field_id' => 'required',
                            ]);
        if ($validator->passes()) {
        
 
            $ins['title']                               = $request->title;
            $ins['homepage_setting_field_id']           = $request->homepage_setting_field_id;
            $ins['description']                         = $request->description;
            $ins['order_by']                            = $request->order_by ?? 0;
            $ins['added_by']                            = auth()->user()->id;

            if($request->status == "1")
            {
                $ins['status']          = 'published';
            } else {
                $ins['status']          = 'unpublished';
            }
            
            $error                      = 0;
            $info                       = HomepageSetting::updateOrCreate(['id' => $id], $ins);
        
            $message                    = (isset($id) && !empty($id)) ? 'Updated Successfully' : 'Added successfully';
        } else {
            $error                      = 1;
            $message                    = $validator->errors()->all();
        }
        return response()->json(['error' => $error, 'message' => $message]);
    }
    public function changeStatus(Request $request)
    {
        $id             = $request->id;
        $status         = $request->status;
        $info           = HomepageSetting::find($id);
        $info->status   = $status;
        $info->update();
        return response()->json(['message'=>"You changed the Homepage Setting!",'status'=>1]);

    }

    public function delete(Request $request)
    {
        $id         = $request->id;
        $info       = HomepageSetting::find($id);
        $info->delete();

        return response()->json(['message'=>"Successfully deleted Homepage Setting!",'status'=>1]);
    }

    public function export()
    {

        return Excel::download(new HomepageSettingExport, 'HomepageSetting.xlsx');
    }
}
