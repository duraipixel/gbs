<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddIsOverviewInProductWithAttributeSetsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('product_with_attribute_sets', function (Blueprint $table) {
            $table->enum('is_overview', ['yes', 'no'])->after('attribute_values')->default('no');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('product_with_attribute_sets', function (Blueprint $table) {
            $table->dropColumn('is_overview'); 
        });
    }
}
