<?php namespace Bedard\Shop\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class CreateCartsTable extends Migration
{

    public function up()
    {
        Schema::create('bedard_shop_carts', function($table)
        {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->string('key', 60)->nullable();
            $table->integer('coupon_id')->nullable()->unsigned();
            $table->integer('transaction_id')->nullable()->unsigned();
            $table->string('note')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('bedard_shop_carts');
    }

}
