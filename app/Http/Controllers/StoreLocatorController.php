<?php

namespace App\Http\Controllers;

use App\Exports\StoreLocatorExport;
use App\Models\Master\Brands;
use App\Models\StoreLocator;
use App\Models\StoreLocatorContact;
use App\Models\StoreLocatorEmail;
use App\Models\StoreLocatorMetaTag;
use App\Models\StoreLocatorPincode;
use Illuminate\Http\Request;
use Carbon\Carbon;
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
class StoreLocatorController extends Controller
{
    public function index(Request $request)
    {
        $title = "Store Locator";
        $breadCrum = array("Store Locator","Store Locator");
        if($request->ajax())
        {
           
            $data = StoreLocator::select('store_locators.*','brands.brand_name as brand_name','users.name as user_name')
            ->join('users','users.id','=','store_locators.added_by')
            ->leftJoin('brands','brands.id','=','store_locators.brand_id');

            $status     = $request->get('status');
            $keywords   = $request->get('search')['value'];
            $datatables         =  Datatables::of($data)
            ->filter(function($query) use ($status,$keywords){
                return $query->when($status !='', function($q) use ($status){
                    $q->where('store_locators.status','=',$status);
                })->when($keywords != '',function($q) use ($keywords){
                    $date = date('Y-m-d', strtotime($keywords));
                    $q->where('store_locators.title','like',"%{$keywords}%")
                        ->orWhere('brands.brand_name','like', "%{$keywords}%");
                });
            })
            ->addIndexColumn()
            ->addColumn('status', function($row){
                $status = '<a href="javascript:void(0);" class="badge badge-light-'.(($row->status == 'published') ? 'success': 'danger').'" tooltip="Click to '.(($row->status == 'published') ? 'Unpublish' : 'Publish').'" onclick="return commonChangeStatus(' . $row->id . ', \''.(($row->status == 'published') ? 'unpublished': 'published').'\', \'store-locator\')">'.ucfirst($row->status).'</a>';
                return $status;
            })
            ->addColumn('created_at', function($row){
                $created_at = Carbon::createFromFormat('Y-m-d H:i:s', $row['created_at'])->format('d-m-Y');
                return $created_at;
            })
            
            ->addColumn('action', function ($row) {
                $edit_btn = '<a href="javascript:void(0);" onclick="return  openForm(\'store-locator\',' . $row->id . ')" class="btn btn-icon btn-active-primary btn-light-primary mx-1 w-30px h-30px" > 
                <i class="fa fa-edit"></i>
            </a>';
                $del_btn = '<a href="javascript:void(0);" onclick="return commonDelete(' . $row->id . ', \'store-locator\')" class="btn btn-icon btn-active-danger btn-light-danger mx-1 w-30px h-30px" > 
            <i class="fa fa-trash"></i></a>';
                return $edit_btn . $del_btn;
            })
            ->rawColumns(['action', 'status']);
            return $datatables->make(true);
        }
        return view('platform.store_locator.index',compact('title','breadCrum'));

    }

    public function modalAddEdit(Request $request)
    {
        
        $title              = "Add Store Locator";
        $breadCrum          = array('Store Locator', 'Add Store Locator');

        $id                 = $request->id;
        $info               = '';
        $modal_title        = 'Add Store Locator';
        $brand    = Brands::where('status', 'published')->get();

        if (isset($id) && !empty($id)) {
            $info           = StoreLocator::find($id);
            $modal_title    = 'Update Store Locator';
        }
        return view('platform.store_locator.form.add_edit_modal', compact('modal_title', 'breadCrum', 'info', 'brand'));
    }

    public function saveForm(Request $request)
    {
        $id             = $request->id;
        $validator      = Validator::make($request->all(), [
                            'title' => 'required','string',
                            'brand_id' => 'required',
                            'description' => 'required',
                      ]);
        $storeId         = '';
        if ($validator->passes()) {
          
            if( !$id ) {
                $ins['added_by'] = Auth::id();
            } else {
                $ins['added_by'] = Auth::id();
            }
            $brand = Brands::where('status','published')->where('id',$request->brand_id)->select('slug')->first();
            $brand = $brand->slug ?? '';

            $ins['slug'] = $brand.'-'.\Str::slug($request->title);
            $ins['title'] = $request->title;
            $ins['brand_id'] = $request->brand_id;
            $ins['address'] = $request->address;
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
            
            $error                      = 0;
            $storeLocatorInfo               = StoreLocator::updateOrCreate(['id' => $id], $ins);
            $storeLocatorId                 = $storeLocatorInfo->id;

            if(!empty($request->near_pincode) && count($request->near_pincode) > 0)
            {
                StoreLocatorPincode::where('store_locator_id',$storeLocatorId)->delete();
               
                foreach($request->near_pincode as $key=>$val)
                {
                    $ins['store_locator_id']       = $storeLocatorId;
                    $ins['pincode']                 = $val;
                    $ins['status']                  = Auth::id();
                    StoreLocatorPincode::create($ins);
                }
                
            }

            if(!empty($request->contact) && count($request->contact) > 0)
            {

                StoreLocatorContact::where('store_locator_id',$storeLocatorId)->delete();
               
                foreach($request->contact as $key=>$val)
                {
                    $ins['store_locator_id']       = $storeLocatorId;
                    $ins['contact']                 = $val;
                    $ins['status']                  = Auth::id();
                    $data = StoreLocatorContact::create($ins);
                }
                
            }

            if(!empty($request->email) && count($request->email) > 0)
            {

                StoreLocatorEmail::where('store_locator_id',$storeLocatorId)->delete();
               
                foreach($request->email as $key=>$val)
                {
                    $ins['store_locator_id']       = $storeLocatorId;
                    $ins['email']                   = $val;
                    $ins['status']                  = Auth::id();
                    $data = StoreLocatorEmail::create($ins);
                }
                
            }

            if ($request->hasFile('banner')) {
               
                $imagName               = time() . '_' . $request->banner->getClientOriginalName();
                $directory              = 'storeLocator/'.$storeLocatorId.'/banner';
                $filename               = $directory.'/'.$imagName.'/';
                Storage::deleteDirectory('public/'.$directory);
                
                if (!is_dir(storage_path("app/public/storeLocator/".$storeLocatorId."/banner"))) {
                    mkdir(storage_path("app/public/storeLocator/".$storeLocatorId."/banner"), 0775, true);
                }

                $thumbnailPath          = 'public/storeLocator/'.$storeLocatorId.'/banner/' . $imagName;
                Image::make($request->file('banner'))->save(storage_path('app/' . $thumbnailPath));

                $storeLocatorInfo->banner    = $thumbnailPath;
                $storeLocatorInfo->save();

            }
           
            if ($request->hasFile('store_image')) {
               
                $imagName               = time() . '_' . $request->store_image->getClientOriginalName();
                $directory              = 'storeLocator/'.$storeLocatorId.'/store';
                $filename               = $directory.'/'.$imagName.'/';
                Storage::deleteDirectory('public/'.$directory);
                
                if (!is_dir(storage_path("app/public/storeLocator/".$storeLocatorId."/store"))) {
                    mkdir(storage_path("app/public/storeLocator/".$storeLocatorId."/store"), 0775, true);
                }

                $storeImagePath          = 'public/storeLocator/'.$storeLocatorId.'/store/' . $imagName;
                Image::make($request->file('store_image'))->save(storage_path('app/' . $storeImagePath));

                $storeLocatorInfo->store_image    = $storeImagePath;
                $storeLocatorInfo->save();
            }
            
            $meta_title = $request->meta_title;
            $meta_keywords = $request->meta_keywords;
            $meta_description = $request->meta_description;
            if( !empty( $meta_title ) || !empty( $meta_keywords) || !empty( $meta_description ) ) {
                StoreLocatorMetaTag::where('store_locator_id',$storeLocatorId)->delete();
                $metaIns['meta_title']          = $meta_title;
                $metaIns['meta_keyword']       = $meta_keywords;
                $metaIns['meta_description']    = $meta_description;
                $metaIns['store_locator_id']         = $storeLocatorId;
                StoreLocatorMetaTag::create($metaIns);
            }
            $message                    = (isset($id) && !empty($id)) ? 'Updated Successfully' : 'Added successfully';

        }
        else {
            $error      = 1;
            $message    = $validator->errors()->all();
        }
        return response()->json(['error' => $error, 'message' => $message, 'storeLocatorId' => $storeLocatorId]);

    }
    
    public function changeStatus(Request $request)
    {
        
        $id             = $request->id;
        $status         = $request->status;
        $info           = StoreLocator::find($id);
        $info->status   = $status;
        $info->update();
        // echo 1;
        return response()->json(['message'=>"You changed the status!",'status'=>1]);

    }
    public function delete(Request $request)
    {
        $id         = $request->id;
        $info       = StoreLocator::find($id);
        $info->delete();
        $directory      = 'storeLocator/banner/'.$id;
        Storage::deleteDirectory($directory);

        $directory      = 'storeLocator/banner_mb/'.$id;
        Storage::deleteDirectory($directory);

        $directory      = 'storeLocator/store_image/'.$id;
        Storage::deleteDirectory($directory);
        $directory      = 'storeLocator/store_image_mb/'.$id;
        Storage::deleteDirectory($directory);


        $directory      = 'storeLocator/'.$id;
        Storage::deleteDirectory($directory);

        return response()->json(['message'=>"Successfully deleted!",'status'=>1]);
    }
    public function export()
    {
        return Excel::download(new StoreLocatorExport, 'store_locator.xlsx');
        
    }
    
    public function exportPdf()
    {
        $list       = StoreLocator::all();
        $pdf        = PDF::loadView('platform.exports.product.product_category_excel', array('list' => $list, 'from' => 'pdf'))->setPaper('a4', 'landscape');;
        return $pdf->download('productCategories.pdf');
    }
}
