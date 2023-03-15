<div class="row mb-7">
    <div class="col-md-8">
        <div class="fv-row mb-7">
            <div class="row">
                <div class="col-md-6">
                    <label class="required fw-bold fs-6 mb-2">Brand</label>
                    <select name="brand_id" id="brand_id" aria-label="Select a Brand" data-control="select2" data-placeholder="Select a Brand..." class="form-select mb-2">
                        <option value="">Select a Brand</option>
                        @isset($brand)
                            @foreach ($brand as $item)
                                <option value="{{ $item->id }}" @if( isset( $info->brand_id ) && $info->brand_id == $item->id ) selected @endif>{{ $item->brand_name }}</option>
                            @endforeach
                        @endisset
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="required fw-bold fs-6 mb-2">Title</label>
                    <input type="text" name="title" class="form-control form-control-solid mb-3 mb-lg-0" placeholder="Title" value="{{ $info->title ?? '' }}" />
                </div>
            </div>
            
          
        </div>
        <div class="fv-row mb-7">
            <label class="required fw-bold fs-6 mb-2">Description</label>
            <textarea class="form-control form-control-solid mb-3 mb-lg-0" placeholder="Description" name="description" id="description" cols="30" rows="5">{{ $info->description ?? '' }}</textarea>
        </div>
       
        <div class="fv-row mb-7">
            @if(isset( $info->email ) && !empty ( $info->email ))
                <?php
                $arrEmail = json_decode( $info->email );
                $info->email = implode(',',$arrEmail);
                ?>
            @endif
            <label class="fw-bold fs-6 mb-2">Email</label>
            <input type="text" name="email" class="form-control form-control-solid mb-3 mb-lg-0" placeholder="Email" value="{{ $info->email ?? '' }}" />
        </div>
        <div class="fv-row mb-7">
            <label class="fw-bold fs-6 mb-2">Contact Number</label>
            @if(isset( $info->contact_no ) && !empty ( $info->contact_no ))
              <?php
                $arrMobile = json_decode( $info->contact_no );
                $info->contact_no = implode(',',$arrMobile);
              ?>
            @endif
            <input type="text" name="contact_no" class="form-control form-control-solid mb-3 mb-lg-0" placeholder="Contact Number" value="{{ $info->contact_no ?? '' }}" />
        </div>
     
       
        <div class="fv-row mb-7">
            <label class="fw-bold fs-6 mb-2">Address</label>
            <textarea class="form-control form-control-solid mb-3 mb-lg-0" placeholder="Address" name="address" id="address" cols="30" rows="5">{{ $info->address ?? '' }}</textarea>
        </div>
      
        <div class="fv-row mb-7">

            <div class="row">
                <div class="col-md-6">
                    <label class="fw-bold fs-6 mb-2">Latitude</label>
                    <input type="text" name="latitude" class="form-control numberonly form-control-solid mb-3 mb-lg-0"
                    placeholder="Latitude" value="{{ $info->latitude ?? '' }}" />
                </div>
                <div class="col-md-6">
                    <label class="fw-bold fs-6 mb-2">Longitude</label>
                    <input type="text" name="longitude" class="form-control numberonly form-control-solid mb-3 mb-lg-0"
                        placeholder="Longitude" value="{{ $info->longitude ?? '' }}" />
                </div>
            </div>
        </div>
        <div class="fv-row mb-7">
            <div class="row">
                <div class="col-md-6">
                    <label class="fw-bold fs-6 mb-2">Sorting Order</label>
                    <input type="text" name="order_by" class="form-control numberonly form-control-solid mb-3 mb-lg-0"
                    placeholder="Sorting Order" value="{{ $info->order_by ?? '' }}" min="1" />
                </div>
                <div class="col-md-6">
                    <label class="fw-bold fs-6 mb-2"> Published </label>
                    <div class="form-check form-switch form-check-custom form-check-solid fw-bold fs-6 mb-2">
                        <input class="form-check-input" type="checkbox"  name="status" value="1"  @if( (isset( $info->status) && $info->status == 'published') || (!isset($info->status)))  checked @endif />
                    </div>
                </div>
            </div>
        </div>
       
        
    </div>
    <div class="col-md-4">
        <div class=" mb-7">
            <div class="fv-row">
                <label class="d-block fw-bold fs-6 mb-5">Banner Image</label>
                <div class="form-text">
                    Allowed file types: png, jpg,
                    jpeg.
                </div>
            </div>
            <input id="banner_remove_image" type="hidden" name="banner_remove_image" value="no">
            <div class=" image-input image-input-outline banner-image" data-kt-image-input="true"
                style="background-image: url({{ asset('userImage/no_Image.jpg') }})">
                @if ($info->banner ?? '')
                @php
                    $url = Storage::url($info->banner);
                @endphp
                    <div class="image-input-wrapper w-125px h-125px banner-image"
                        id="banner-image"
                        style="background-image: url({{ asset($url) }});">
                    </div>
                @else
                    <div class="image-input-wrapper w-125px h-125px banner-image"
                        id="banner-image"
                        style="background-image: url({{ asset('userImage/no_Image.jpg') }});">
                    </div>
                @endif
                <label
                    class="btn btn-icon btn-circle btn-active-color-primary w-25px h-25px bg-body shadow"
                    data-kt-image-input-action="change" data-bs-toggle="tooltip"
                    title="Change Icon">
                    <i class="bi bi-pencil-fill fs-7"></i>
                    <input type="file" name="banner" id="bannerUrl"
                        accept=".png, .jpg, .jpeg" />
                </label>

                <span
                    class="btn btn-icon btn-circle btn-active-color-primary w-25px h-25px bg-body shadow"
                    data-kt-image-input-action="cancel" data-bs-toggle="tooltip"
                    title="Cancel avatar">
                    <i class="bi bi-x fs-2"></i>
                </span>
                <span
                    class="btn btn-icon btn-circle btn-active-color-primary w-25px h-25px bg-body shadow"
                    data-kt-image-input-action="remove" data-bs-toggle="tooltip"
                    title="Remove avatar1">
                    <i class="bi bi-x fs-2" id="banner_remove_logo"></i>
                </span>
            </div>
        </div>
        <div class=" mb-7">
            <div class="fv-row">
                <label class="d-block fw-bold fs-6 mb-5">Banner Image Mobile</label>
                <div class="form-text">
                    Allowed file types: png, jpg,
                    jpeg.
                </div>
            </div>
            <input id="banner_mobile_remove_image" type="hidden" name="banner_mobile_remove_image" value="no">
            <div class="image-input image-input-outline banner-mobile-image" data-kt-image-input="true"
                style="background-image: url({{ asset('userImage/no_Image.jpg') }})">
                @if ($info->banner_mb ?? '')
                @php
                    $url = Storage::url($info->banner_mb);
                    // print_r( $url );
                @endphp
                    <div class="image-input-wrapper w-125px h-125px banner-mobile-image"
                        id="banner-mobile-image"
                        style="background-image: url({{ asset($url) }});">
                    </div>
                @else
                    <div class="image-input-wrapper w-125px h-125px banner-mobile-image"
                        id="banner-mobile-image"
                        style="background-image: url({{ asset('userImage/no_Image.jpg') }});">
                    </div>
                @endif
                <label
                    class="btn btn-icon btn-circle btn-active-color-primary w-25px h-25px bg-body shadow"
                    data-kt-image-input-action="change" data-bs-toggle="tooltip"
                    title="Change Icon">
                    <i class="bi bi-pencil-fill fs-7"></i>
                    <input type="file" name="banner_mb" id="bannerMobileUrl"
                        accept=".png, .jpg, .jpeg" />
                </label>

                <span
                    class="btn btn-icon btn-circle btn-active-color-primary w-25px h-25px bg-body shadow"
                    data-kt-image-input-action="cancel" data-bs-toggle="tooltip"
                    title="Cancel avatar">
                    <i class="bi bi-x fs-2"></i>
                </span>
                <span
                    class="btn btn-icon btn-circle btn-active-color-primary w-25px h-25px bg-body shadow"
                    data-kt-image-input-action="remove" data-bs-toggle="tooltip"
                    title="Remove avatar1">
                    <i class="bi bi-x fs-2" id="banner_mobile_remove_logo"></i>
                </span>
            </div>
        </div>

        <div class=" mb-7">
            <div class="fv-row">
                <label class="d-block fw-bold fs-6 mb-5">Store Image</label>
                <div class="form-text">
                    Allowed file types: png, jpg,
                    jpeg.
                </div>
            </div>
            <input id="store_remove_image" type="hidden" name="store_remove_image" value="no">
            <div class=" image-input image-input-outline store-image" data-kt-image-input="true"
                style="background-image: url({{ asset('userImage/no_Image.jpg') }})">
                @if ($info->store_image ?? '')
                @php
                    $url = Storage::url($info->store_image);
                @endphp
                    <div class="image-input-wrapper w-125px h-125px store-image"
                        id="store-image"
                        style="background-image: url({{ asset($url) }});">
                    </div>
                @else
                    <div class="image-input-wrapper w-125px h-125px store-image"
                        id="store-image"
                        style="background-image: url({{ asset('userImage/no_Image.jpg') }});">
                    </div>
                @endif
                <label
                    class="btn btn-icon btn-circle btn-active-color-primary w-25px h-25px bg-body shadow"
                    data-kt-image-input-action="change" data-bs-toggle="tooltip"
                    title="Change Icon">
                    <i class="bi bi-pencil-fill fs-7"></i>
                    <input type="file" name="store_image" id="storeUrl"
                        accept=".png, .jpg, .jpeg" />
                </label>

                <span
                    class="btn btn-icon btn-circle btn-active-color-primary w-25px h-25px bg-body shadow"
                    data-kt-image-input-action="cancel" data-bs-toggle="tooltip"
                    title="Cancel avatar">
                    <i class="bi bi-x fs-2"></i>
                </span>
                <span
                    class="btn btn-icon btn-circle btn-active-color-primary w-25px h-25px bg-body shadow"
                    data-kt-image-input-action="remove" data-bs-toggle="tooltip"
                    title="Remove avatar1">
                    <i class="bi bi-x fs-2" id="store_remove_logo"></i>
                </span>
            </div>
        </div>

        <div class=" mb-7">
            <div class="fv-row">
                <label class="d-block fw-bold fs-6 mb-5">Store Image Mobile</label>
                <div class="form-text">
                    Allowed file types: png, jpg,
                    jpeg.
                </div>
            </div>
            <input id="store_mobile_remove_image" type="hidden" name="store_mobile_remove_image" value="no">
            <div class="image-input image-input-outline store-mobile-image" data-kt-image-input="true"
                style="background-image: url({{ asset('userImage/no_Image.jpg') }})">
                @if ($info->store_image_mb ?? '')
                @php
                    $url = Storage::url($info->store_image_mb);
                @endphp
                    <div class="image-input-wrapper w-125px h-125px store-mobile-image"
                        id="store-mobile-image"
                        style="background-image: url({{ asset($url) }});">
                    </div>
                @else
                    <div class="image-input-wrapper w-125px h-125px banner-mobile-image"
                        id="store-mobile-image"
                        style="background-image: url({{ asset('userImage/no_Image.jpg') }});">
                    </div>
                @endif
                <label
                    class="btn btn-icon btn-circle btn-active-color-primary w-25px h-25px bg-body shadow"
                    data-kt-image-input-action="change" data-bs-toggle="tooltip"
                    title="Change Icon">
                    <i class="bi bi-pencil-fill fs-7"></i>
                    <input type="file" name="store_image_mb" id="storeMobileUrl"
                        accept=".png, .jpg, .jpeg" />
                </label>

                <span
                    class="btn btn-icon btn-circle btn-active-color-primary w-25px h-25px bg-body shadow"
                    data-kt-image-input-action="cancel" data-bs-toggle="tooltip"
                    title="Cancel avatar">
                    <i class="bi bi-x fs-2"></i>
                </span>
                <span
                    class="btn btn-icon btn-circle btn-active-color-primary w-25px h-25px bg-body shadow"
                    data-kt-image-input-action="remove" data-bs-toggle="tooltip"
                    title="Remove avatar1">
                    <i class="bi bi-x fs-2" id="store_mobile_remove_logo"></i>
                </span>
            </div>
        </div>
       
       
        
    </div>
</div>