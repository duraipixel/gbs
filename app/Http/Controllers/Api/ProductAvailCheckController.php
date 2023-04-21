<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Master\Pincode;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProductAvailCheckController extends Controller
{
    public function index(Request $request)
    {
         $pincode                   = [];
         $pincode_check = Pincode::select('id','shipping_information')->where('status','1')->where('pincode',$request->pincode)->first();
          if($pincode_check)
          {
            $pincode['Shipping_Information']=$pincode_check->shipping_information;
            return response()->json(['data'=>$pincode]); 
          }
          else
          {
            $pincode['Shipping_Information']='Product not avilable in this Pincode';
            return response()->json(['data'=>$pincode]); 
          }
       
    }
}
