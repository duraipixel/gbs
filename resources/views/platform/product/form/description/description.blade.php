<div class="d-flex flex-column gap-7 gap-lg-10">
    <div class="card card-flush py-4">
        <div class="card-body pt-2">
            <div class="mb-10 fv-row">
                <div>
                    <label class="form-label">Description</label>
                    <div id="kt_ecommerce_add_product_short_description" name="kt_ecommerce_add_product_short_description"
                        class="min-h-200px mb-2">{!! $info->description ?? '' !!}</div>
                    <textarea name="product_description" id="product_description" class="d-none" cols="30" rows="10">{!! $info->description ?? '' !!}</textarea>
                </div>
                <br>

                <div class="col-md-3">
                    <button type="button" id="btnAdd" class="btn btn-sm btn-light-primary mt-9">
                        <!--begin::Svg Icon | path: icons/duotune/arrows/arr087.svg-->
                        <span class="svg-icon svg-icon-2">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                                fill="none">
                                <rect opacity="0.5" x="11" y="18" width="12" height="2"
                                    rx="1" transform="rotate(-90 11 18)" fill="currentColor" />
                                <rect x="6" y="11" width="12" height="2" rx="1"
                                    fill="currentColor" />
                            </svg>
                        </span>

                        <!--end::Svg Icon-->Add Description
                    </button>

                </div>
                <div id="newinput">
                    @isset($info->productDescription)
                        @foreach ($info->productDescription as $item)
                        
                            <div class="card border border-2 p-5" id='new_row_add'>
                                <div class="row">
                                    <div class="col-md-7">
                                        <div class="row">
                                            <div class="col-sm-8 mb-2">
                                                <input type="text" id="title" name="title[]"
                                                    class="form-control form-control-solid mb-3 mb-lg-0 mobile_num"
                                                    placeholder="Title" value="{{ $item->title ?? '' }}" required />
                                                <input type="hidden" name="desc_id[]" value="{{ $item->id }}">
                                            </div>
                                            <div class="col-sm-4 mb-2">
                                                <input type="text" id="sorting_order" name="sorting_order[]"
                                                    class="form-control form-control-solid mb-3 mb-lg-0 mobile_num"
                                                    placeholder="Sorting Order" value="{{ $item->order_by ?? '' }}" />
                                            </div>
                                            <div class="col-sm-12">

                                                <textarea class="form-control form-control-solid mb-3 mb-lg-0" name="desc[]" id="desc">{{ $item->description ?? '' }}</textarea>
                                            </div>
                                        </div>

                                    </div>
                                    <div class="col-md-4 mb-2">
                                        <div>

                                            <input type="file" id="home_image" name="home_image[]"
                                                class="form-control form-control-solid mb-3 mb-lg-0 mobile_num"
                                                placeholder="Sorting Order" />
                                        </div>
                                        <div class="mt-3">
                                            @php
                                                $path = $item->desc_image;
                                                if (isset($path) && !empty($path)) {
                                                    $path = Storage::url($path);
                                                }
                                            @endphp
                                            <img src="{{ asset($path) }}" width="100" alt="">
                                        </div>
                                    </div>

                                    <div class="col-md-1">
                                        <button type="button" id="DeleteRow" data-repeater-delete=""
                                            class="btn btn-sm btn-icon btn-light-danger removeRow mt-10">
                                            <!--begin::Svg Icon | path: icons/duotune/arrows/arr088.svg-->
                                            <span class="svg-icon svg-icon-2">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                                    viewBox="0 0 24 24" fill="none">
                                                    <rect opacity="0.5" x="7.05025" y="15.5356" width="12"
                                                        height="2" rx="1"
                                                        transform="rotate(-45 7.05025 15.5356)" fill="currentColor">
                                                    </rect>
                                                    <rect x="8.46447" y="7.05029" width="12" height="2"
                                                        rx="1" transform="rotate(45 8.46447 7.05029)"
                                                        fill="currentColor"></rect>
                                                </svg>
                                            </span>
                                            <!--end::Svg Icon-->
                                        </button>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    @endisset

                </div>
                <br>
            </div>
        </div>
    </div>
</div>

<script>
    $("#btnAdd").click(function() {

        newRowAdd =
            `<div class="card border border-2 p-5" id='new_row_add'>
                <div class="row" >
                    <div class="col-md-7">
                        <div class="row">
                            <div class="col-sm-8 mb-2">
                                <input type="text" id="title" name="title[]"
                                    class="form-control form-control-solid mb-3 mb-lg-0 mobile_num"
                                    placeholder="Title" required />
                                
                            </div>
                            <div class="col-sm-4 mb-2">
                                <input type="text" id="sorting_order" name="sorting_order[]"
                                    class="form-control form-control-solid mb-3 mb-lg-0 mobile_num"
                                    placeholder="Sorting Order"  />
                            </div>
                            <div class="col-sm-12">
                                <textarea class="form-control form-control-solid mb-3 mb-lg-0" name="desc[]" id="desc"></textarea>
                            </div>
                        </div>

                    </div>
                    <div class="col-md-4 mb-2">
                        <div>
                            <input type="file" id="home_image" name="home_image[]"
                                class="form-control form-control-solid mb-3 mb-lg-0 mobile_num"
                                placeholder="Sorting Order" />
                        </div>
                        
                    </div>
                    
                    <div class="col-md-1">
                        <button type="button" id="DeleteRow" data-repeater-delete=""
                            class="btn btn-sm btn-icon btn-light-danger removeRow mt-10">
                            <!--begin::Svg Icon | path: icons/duotune/arrows/arr088.svg-->
                            <span class="svg-icon svg-icon-2">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                    viewBox="0 0 24 24" fill="none">
                                    <rect opacity="0.5" x="7.05025" y="15.5356" width="12"
                                        height="2" rx="1"
                                        transform="rotate(-45 7.05025 15.5356)" fill="currentColor">
                                    </rect>
                                    <rect x="8.46447" y="7.05029" width="12" height="2"
                                        rx="1" transform="rotate(45 8.46447 7.05029)"
                                        fill="currentColor"></rect>
                                </svg>
                            </span>
                            <!--end::Svg Icon-->
                        </button>
                    </div>
                </div>
            </div>`;

        $('#newinput').append(newRowAdd);
    });
</script>
