<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateServiceCentersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('service_centers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('parent_id');
            $table->string('title');
            $table->string('banner')->nullable()->comment('website image');
            $table->string('banner_mb')->nullable()->comment('mobile image');
            $table->text('description')->nullable();
            $table->unsignedBigInteger('pincode')->nullable();
            $table->text('address')->nullable();
            $table->decimal('latitude', 10, 10)->nullable();
            $table->decimal('longitude', 11, 10)->nullable();
            $table->json('email')->nullable();
            $table->json('contact_no')->nullable();
            $table->enum('status', ['published', 'unpublished']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('service_centers');
    }
}
