<?php namespace Bedard\Shop\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class CreateInventoriesTable extends Migration
{

    public function up()
    {
        Schema::create('bedard_shop_inventories', function($table)
        {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->integer('product_id')->nullable()->unsigned();
            $table->foreign('product_id')->references('id')->on('bedard_shop_products')->onDelete('cascade');
            $table->string('name')->nullable();
            $table->integer('quantity')->unsigned()->default(0);
            $table->decimal('modifier')->unsigned()->default(0);
            $table->integer('position')->unsigned()->default(0);
            $table->boolean('is_active')->unsigned()->default(0);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('bedard_shop_inventories');
    }

}
