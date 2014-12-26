<?php namespace Bedard\Shop\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class CreateProductsTable extends Migration
{

    public function up()
    {
        Schema::create('bedard_shop_products', function($table)
        {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->string('name')->nullable();
            $table->string('slug')->unique()->nullable();
            $table->text('description')->nullable();
            $table->decimal('full_price', 10, 2)->unsigned();
            $table->integer('ounces')->unsigned();
            $table->boolean('is_active')->unsigned();
            $table->boolean('is_visible')->unsigned();
            $table->timestamps();
        });

        Schema::create('bedard_shop_products_categories', function($table)
        {
            $table->engine = 'InnoDB';
            $table->integer('product_id')->unsigned();
            $table->integer('category_id')->unsigned();
            $table->primary(['product_id', 'category_id']);
            $table->foreign('product_id')->references('id')->on('bedard_shop_products')->onDelete('cascade');
            $table->foreign('category_id')->references('id')->on('bedard_shop_categories')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('bedard_shop_products_categories');
        Schema::dropIfExists('bedard_shop_products');
    }

}
