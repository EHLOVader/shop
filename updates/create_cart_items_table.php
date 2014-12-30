<?php namespace Bedard\Shop\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class CreateCartItemsTable extends Migration
{

    public function up()
    {
        Schema::create('bedard_shop_cart_items', function($table)
        {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->integer('cart_id')->nullable()->unsigned();
            $table->foreign('cart_id')->references('id')->on('bedard_shop_carts')->onDelete('cascade');
            $table->integer('inventory_id')->nullable()->unsigned();
            $table->integer('quantity')->unsigned();
            $table->decimal('backup_price', 10, 2)->unsigned();
            $table->string('backup_inventory')->nullable();
            $table->string('backup_product')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('bedard_shop_cart_items');
    }

}
