<?php namespace Bedard\Shop\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class CreateCouponsTable extends Migration
{

    public function up()
    {
        Schema::create('bedard_shop_coupons', function($table)
        {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->string('name')->nullable()->unique();            
            $table->string('message')->nullable();
            $table->timestamp('start_date')->nullable();
            $table->timestamp('end_date')->nullable();
            $table->decimal('cart_value', 10, 2)->unsigned()->default(0);
            $table->decimal('amount', 10, 2)->unsigned()->default(0);
            $table->integer('limit')->unsigned();
            $table->boolean('is_percentage')->unsigned()->default(0);
            $table->boolean('is_freeshipping')->unsigned()->default(0);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('bedard_shop_coupons');
    }

}
