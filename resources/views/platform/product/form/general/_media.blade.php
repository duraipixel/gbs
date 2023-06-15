<div class="card-header">
    <div class="card-title">
        <h2>Media</h2>
    </div>
</div>

<div class="card-body pt-0">
    <div class="fv-row mb-2">
        <div class="dropzone" id="kt_ecommerce_add_product_media">
            <div class="dz-message needsclick">
                <i class="bi bi-file-earmark-arrow-up text-primary fs-3x"></i>
                <div class="ms-4">
                    <h3 class="fs-5 fw-bolder text-gray-900 mb-1">Drop files here or click to upload.</h3>
                    <span class="fs-7 fw-bold text-gray-400">Upload up to 10 files</span>
                  
                </div>
             
            </div>
            <div id="botofform"></div>
        </div>
      
    </div>
    <div class="text-muted fs-7">Set the product media gallery.</div>
</div>
<script src="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js"></script>

<script src="https://cdn.jsdelivr.net/npm/jquery-sortablejs@latest/jquery-sortable.js"></script>

<script>
   /*  $(function() {
    $(".dropzone").sortable({
    items:'.dz-preview',
    cursor: 'grab',
    opacity: 0.5,
    containment: '.dropzone',
    distance: 20,
    tolerance: 'pointer',
    stop: function () {
      var queue = myDropzone.getAcceptedFiles();
      newQueue = [];
      $('#kt_ecommerce_add_product_media .dz-preview .dz-filename [data-dz-name]').each(function (count, el) { 
        //console.log(count);          
            var name = el.innerHTML;
            queue.forEach(function(file) {
                if (file.name === name) {
                    newQueue.push(file);
                }
            });
      });
      myDropzone.files = newQueue;
    }
});
});
$(function() {
  $(".dropzone").sortable({
    items: '.dz-preview',
    cursor: 'move',
    opacity: 0.5,
    containment: '.dropzone',
    distance: 20,
    tolerance: 'pointer',
    stop: function(event, ui) {
      //cloned div
      var cloned = $('div#botofform').clone()
      $('#botofform').html("") //empty it
      //loop through dropzone..
      $('.dropzone .dz-complete').each(function() {
        var data_id = $(this).data('id') //get data-id
        $(cloned).find("input[data-id=" + data_id + "]").clone().appendTo($('#botofform')) //find input which has that data-id and append same to bottmform
      });


    }

  });
});*/
</script>