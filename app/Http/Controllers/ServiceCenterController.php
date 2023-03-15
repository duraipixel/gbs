<?php

namespace App\Http\Controllers;

use App\Exports\Export;
use App\Models\ServiceCenter;
use App\Models\ServiceCenterMetaTag;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Validator;
use DataTables;
use Auth;
use Excel;
use PDF;
use Image;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;


class ServiceCenterController extends Controller
{
    public function index(Request $request)
    {
        $title = "Service Center";
        $breadCrum = array("Service Center","Service Center");
        if($request->ajax())
        {
           
            $data = ServiceCenter::select('service_centers.*','users.name as user_name',
            DB::raw('IF(mm_service_centers.parent_id = 0,"Parent",mm_parent_category.title) as parent_name'))
            ->join('users','users.id','=','service_centers.added_by')
            ->leftJoin('service_centers as parent_category','parent_category.id','=','service_centers.parent_id');
            $status     = $request->get('status');
            $keywords   = $request->get('search')['value'];
            $datatables         =  Datatables::of($data)
            ->filter(function($query) use ($status,$keywords){
                return $query->when($status !='', function($q) use ($status){
                    $q->where('service_centers.status','=',$status);
                })->when($keywords != '',function($q) use ($keywords){
                    $date = date('Y-m-d', strtotime($keywords));
                    $q->where('service_centers.title','like',"%{$keywords}%")
                        ->orWhere('service_centers.description','like',"%{$keywords}%")
                        ->orWhereDate("service_centers.created_at", $date);
                });
            })
            ->addIndexColumn()
            ->addColumn('status', function($row){
                $status = '<a href="javascript:void(0);" class="badge badge-light-'.(($row->status == 'published') ? 'success': 'danger').'" tooltip="Click to '.(($row->status == 'published') ? 'Unpublish' : 'Publish').'" onclick="return commonChangeStatus(' . $row->id . ', \''.(($row->status == 'published') ? 'unpublished': 'published').'\', \'service-center\')">'.ucfirst($row->status).'</a>';
                return $status;
            })
            ->addColumn('created_at', function($row){
                $created_at = Carbon::createFromFormat('Y-m-d H:i:s', $row['created_at'])->format('d-m-Y');
                return $created_at;
            })
            
            ->addColumn('action', function ($row) {
                $edit_btn = '<a href="javascript:void(0);" onclick="return  openForm(\'service-center\',' . $row->id . ')" class="btn btn-icon btn-active-primary btn-light-primary mx-1 w-30px h-30px" > 
                <i class="fa fa-edit"></i>
            </a>';
                $del_btn = '<a href="javascript:void(0);" onclick="return commonDelete(' . $row->id . ', \'service-center\')" class="btn btn-icon btn-active-danger btn-light-danger mx-1 w-30px h-30px" > 
            <i class="fa fa-trash"></i></a>';
                return $edit_btn . $del_btn;
            })
            ->rawColumns(['action', 'status']);
            return $datatables->make(true);


        }

        return view('platform.service_center.index',compact('title','breadCrum'));

    }
    public function modalAddEdit(Request $request)
    {
        
        $title              = "Add Service Center";
        $breadCrum          = array('Products', 'Add Service Center');

        $id                 = $request->id;
        $info               = '';
        $modal_title        = 'Add Service Center';
        $serviceCenter    = ServiceCenter::where('status', 'published')->where('parent_id', 0)->get();

        if (isset($id) && !empty($id)) {
            $info           = ServiceCenter::find($id);
            $modal_title    = 'Update Service Center';
        }
        return view('platform.service_center.form.add_edit_modal', compact('modal_title', 'breadCrum', 'info', 'serviceCenter'));
    }
    public function saveForm(Request $request)
    {
      
      $id             = $request->id;
      $parent_id      = $request->parent_location;
      $validator      = Validator::make($request->all(), [
                          'title' => ['required','string',
                                              Rule::unique('service_centers')->where(function ($query) use($id, $parent_id) {
                                                  return $query->where('parent_id', $parent_id)->where('deleted_at', NULL)->when($id != '', function($q) use($id){
                                                      return $q->where('id', '!=', $id);
                                                  });
                                              }),
                                              ],

                          'description' => 'required',
                      ]);
        $serviceCenterId         = '';
        if ($validator->passes()) {
            if( !$request->is_parent ) {
                $parent_slug = ServiceCenter::where('id',$parent_id)->select('slug')->first();
                $parent_slug = $parent_slug->slug ?? '';
                $ins['slug'] = $parent_slug .'-'. \Str::slug($request->title);
                $ins['parent_id'] = $request->parent_location;
            } else {

                $ins['slug'] = \Str::slug($request->title);
                $ins['parent_id'] = 0;
            }
            if( !$id ) {
                $ins['added_by'] = Auth::id();
            } else {
                $ins['added_by'] = Auth::id();
            }
            $ins['title'] = $request->title;
            $ins['address'] = $request->address;
            $ins['pincode'] = $request->pincode;
            $ins['latitude'] = $request->latitude;
            $ins['longitude'] = $request->longitude;
            $ins['description'] = $request->description;
            $ins['order_by'] = $request->order_by ?? 0;
            if($request->status)
            {
                $ins['status']          = 'published';
            } else {
                $ins['status']          = 'unpublished';
            }
            if(!empty($request->email))
            {
                $email = explode(',',$request->email);
                $ins['email'] = json_encode($email);
            }
            if(!empty($request->contact_no))
            {
                $contact_no = explode(',',$request->contact_no);
                $ins['contact_no'] = json_encode($contact_no);
            }
           
            $error                      = 0;

            $serviceCenterInfo               = ServiceCenter::updateOrCreate(['id' => $id], $ins);

            $serviceCenterId                 = $serviceCenterInfo->id;

            if ($request->hasFile('banner')) {
               
                $imagName               = time() . '_' . $request->banner->getClientOriginalName();
                $directory              = 'serviceCenter/banner/'.$serviceCenterId;
                $filename               = $directory.'/'.$imagName.'/';
                Storage::deleteDirectory('public/'.$directory);
                Storage::disk('public')->put($filename, File::get($request->banner));
                
                if (!is_dir(storage_path("app/public/serviceCenter/".$serviceCenterId."/thumbnail"))) {
                    mkdir(storage_path("app/public/serviceCenter/".$serviceCenterId."/thumbnail"), 0775, true);
                }
                if (!is_dir(storage_path("app/public/serviceCenter/".$serviceCenterId."/carousel"))) {
                    mkdir(storage_path("app/public/serviceCenter/".$serviceCenterId."/carousel"), 0775, true);
                }

                $thumbnailPath          = 'public/serviceCenter/'.$serviceCenterId.'/thumbnail/' . $imagName;
                Image::make($request->file('banner'))->resize(350,690)->save(storage_path('app/' . $thumbnailPath));

                $carouselPath          = 'public/serviceCenter/'.$serviceCenterId.'/carousel/' . $imagName;
                Image::make($request->file('banner'))->resize(300,220)->save(storage_path('app/' . $carouselPath));

                // $carouselPath          = $directory.'/carousel/'.$imagName;
                // Storage::disk('public')->put( $carouselPath, Image::make($request->file('categoryImage'))->resize(300,220) );

                $serviceCenterInfo->banner    = $filename;
                $serviceCenterInfo->save();
            }
            if ($request->hasFile('banner_mb')) {
              
                $imagName               = time() . '_' . $request->banner_mb->getClientOriginalName();
                $directory              = 'serviceCenter/banner_mb/'.$serviceCenterId;
                $filename               = $directory.'/'.$imagName;
                Storage::deleteDirectory('public/'.$directory);
                Storage::disk('public')->put($filename, File::get($request->banner_mb));
                
                if (!is_dir(storage_path("app/public/serviceCenter/".$serviceCenterId."/thumbnail"))) {
                    mkdir(storage_path("app/public/serviceCenter/".$serviceCenterId."/thumbnail"), 0775, true);
                }
                if (!is_dir(storage_path("app/public/serviceCenter/".$serviceCenterId."/carousel"))) {
                    mkdir(storage_path("app/public/serviceCenter/".$serviceCenterId."/carousel"), 0775, true);
                }

                $thumbnailPath          = 'public/serviceCenter/'.$serviceCenterId.'/thumbnail/' . $imagName;
                Image::make($request->file('banner_mb'))->resize(350,690)->save(storage_path('app/' . $thumbnailPath));

                $carouselPath          = 'public/serviceCenter/'.$serviceCenterId.'/carousel/' . $imagName;
                Image::make($request->file('banner_mb'))->resize(300,220)->save(storage_path('app/' . $carouselPath));

                // $carouselPath          = $directory.'/carousel/'.$imagName;
                // Storage::disk('public')->put( $carouselPath, Image::make($request->file('categoryImage'))->resize(300,220) );

                $serviceCenterInfo->banner_mb    = $filename;
                $serviceCenterInfo->save();
            }
            $meta_title = $request->meta_title;
            $meta_keywords = $request->meta_keywords;
            $meta_description = $request->meta_description;
            if( !empty( $meta_title ) || !empty( $meta_keywords) || !empty( $meta_description ) ) {
                ServiceCenterMetaTag::where('service_center_id',$serviceCenterId)->delete();
                $metaIns['meta_title']          = $meta_title;
                $metaIns['meta_keyword']       = $meta_keywords;
                $metaIns['meta_description']    = $meta_description;
                $metaIns['service_center_id']         = $serviceCenterId;
                ServiceCenterMetaTag::create($metaIns);
            }
            $message                    = (isset($id) && !empty($id)) ? 'Updated Successfully' : 'Added successfully';

        }
        else {
            $error      = 1;
            $message    = $validator->errors()->all();
        }
        return response()->json(['error' => $error, 'message' => $message, 'serviceCenterId' => $serviceCenterId]);

    }
    public function changeStatus(Request $request)
    {
        
        $id             = $request->id;
        $status         = $request->status;
        $info           = ServiceCenter::find($id);
        $info->status   = $status;
        $info->update();
        // echo 1;
        return response()->json(['message'=>"You changed the status!",'status'=>1]);

    }
    public function delete(Request $request)
    {
        $id         = $request->id;
        $info       = ServiceCenter::find($id);
        $info->delete();
        $directory      = 'serviceCenter/banner/'.$id;
        Storage::deleteDirectory($directory);
        $directory      = 'serviceCenter/banner_mb/'.$id;
        Storage::deleteDirectory($directory);
        $directory      = 'serviceCenter/'.$id;
        Storage::deleteDirectory($directory);
        // echo 1;
        return response()->json(['message'=>"Successfully deleted!",'status'=>1]);
    }
    public function export()
    {
        return Excel::download(new ServiceCenterExport, 'service_center.xlsx');
        
    }
    
    public function exportPdf()
    {
        $list       = ServiceCenter::all();
        $pdf        = PDF::loadView('platform.exports.product.product_category_excel', array('list' => $list, 'from' => 'pdf'))->setPaper('a4', 'landscape');;
        return $pdf->download('productCategories.pdf');
    }
    
    
}
