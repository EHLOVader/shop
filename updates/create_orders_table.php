<?php namespace Bedard\Shop\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class CreateOrdersTable extends Migration
{

    public function up()
    {
        Schema::create('bedard_shop_orders', function($table)
        {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->integer('cart_id')->unsigned()->default(0);
            $table->integer('customer_id')->nullable()->unsigned();
            $table->string('service')->nullable();
            $table->string('payment_id')->nullable()->nullable();
            $table->string('payment_code')->nullable()->nullable();
            $table->string('hash')->nullable();
            $table->decimal('amount', 10, 2)->unsigned()->default(0);
            $table->text('shipping_address')->nullable();
            $table->string('shipping_courier')->nullable();
            $table->string('shipping_method')->nullable();
            $table->decimal('shipping_cost')->unsigned()->default(0);
            $table->timestamp('shipped')->nullable();
            $table->boolean('is_complete')->unsigned()->default(0);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('bedard_shop_orders');
    }

}
