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
            $table->integer('order_id')->nullable()->unsigned();
            $table->decimal('backup_total', 10, 2)->unsigned()->nullable();
            $table->decimal('backup_totalBeforeCoupon', 10, 2)->unsigned()->nullable();
            $table->decimal('backup_fullTotal', 10, 2)->unsigned()->nullable();
            $table->string('backup_couponName')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('bedard_shop_carts');
    }

}
