 <!--begin::Card header-->
 <div class="card-header">
    <!--begin::Card title-->
    <div class="card-title">
        <h2>Status</h2>
    </div>
    <!--end::Card title-->
    <!--begin::Card toolbar-->
    <div class="card-toolbar">
        <div class="rounded-circle bg-success w-15px h-15px" id="kt_ecommerce_add_product_status"></div>
    </div>
    <!--begin::Card toolbar-->
</div>
<!--end::Card header-->
<!--begin::Card body-->
<div class="card-body pt-0">
    <!--begin::Select2-->
    <select class="form-select mb-2" data-control="select2" data-hide-search="true" data-placeholder="Select an option" id="kt_ecommerce_add_product_status_select">
        <option></option>
        <option value="published" selected="selected">Published</option>
        <option value="draft">Draft</option>
        <option value="scheduled">Scheduled</option>
        <option value="inactive">Inactive</option>
    </select>
    <div class="text-muted fs-7">Set the product status.</div>
    <div class="d-none mt-10">
        <label for="kt_ecommerce_add_product_status_datepicker" class="form-label">Select publishing date and time</label>
        <input class="form-control" id="kt_ecommerce_add_product_status_datepicker" placeholder="Pick date &amp; time" />
    
    </div>
</div>

<div class="w-100 border-top">
    <div class="row px-10 mt-5 mb-5">
        <div class="col-sm-8">
            <h2>Is Featured? </h2>
        </div>
        <div class="col-sm-4">
            <div class="form-check form-switch form-check-custom form-check-solid fw-bold fs-6 mb-2">
                <input class="form-check-input" type="checkbox"  name="is_featured" value="1"  @if(isset( $info->is_featured) && $info->is_featured == '1') checked @endif />
            </div>
        </div>
    </div>
</div>
