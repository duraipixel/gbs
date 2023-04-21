<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class CustomerAddressesResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        $response['customer_address_id'] = $request->id;
        $response['address_type_id'] = $request->address_type_id;
        $response['name'] = $request->name;
        $response['email'] = $request->email;
        $response['mobile_no'] = $request->mobile_no;
        $response['address_line1'] = $request->address_line1;
        $response['address_line2'] = $request->address_line2;
        $response['landmark'] = $request->landmark;
        $response['countryid'] = $request->countryid;
        $response['country'] = $request->country;
        $response['post_code'] = $request->post_code;
        $response['stateid'] = $request->stateid;
        $response['state'] = $request->state;
        $response['cityid'] = $request->cityid;
        $response['city'] = $request->city;
        $response['is_default'] = $request->is_default;

        return $response;
    }
}
