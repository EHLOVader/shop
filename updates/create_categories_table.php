<?php namespace Bedard\Shop\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class CreateCategoriesTable extends Migration
{

    public function up()
    {
        Schema::create('bedard_shop_categories', function($table)
        {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->string('name', 64)->nullable();
            $table->string('slug', 64)->unique()->nullable();
            $table->string('description')->nullable();
            $table->integer('position')->unsigned();
            $table->string('arrangement_method', 10)->default('newest')->nullable();
            $table->text('arrangement_order')->nullable();
            $table->integer('arrangement_rows')->default(2)->unsigned();
            $table->integer('arrangement_columns')->default(6)->unsigned();
            $table->string('pseudo', 4)->nullable();
            $table->boolean('is_visible')->unsigned();
            $table->boolean('is_active')->unsigned();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('bedard_shop_categories');
    }

}
