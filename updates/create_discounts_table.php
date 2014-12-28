<?php namespace Bedard\Shop\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class CreateDiscountsTable extends Migration
{

    public function up()
    {
        Schema::create('bedard_shop_discounts', function($table)
        {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->string('name')->nullable();
            $table->timestamp('start_date')->nullable();
            $table->timestamp('end_date')->nullable();
            $table->decimal('amount', 10, 2)->unsigned();
            $table->boolean('is_percentage')->unsigned();
            $table->timestamps();
        });

        Schema::create('bedard_shop_discountables', function($table)
        {
            $table->enging = 'InnoDB';
            $table->integer('discount_id')->unsigned();
            $table->integer('discountable_id')->unsigned();
            $table->string('discountable_type')->nullable();
            $table->primary(['discount_id', 'discountable_id']);
            $table->foreign('discount_id')->references('id')->on('bedard_shop_discounts')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('bedard_shop_discountables');
        Schema::dropIfExists('bedard_shop_discounts');
    }

}
