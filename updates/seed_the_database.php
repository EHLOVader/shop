<?php namespace Bedard\Shop\Updates;

use Illuminate\Database\Eloquent\Model;
use October\Rain\Database\Updates\Seeder;
use DB;

use Bedard\Shop\Models\Category;

class DatabaseSeeder extends Seeder {

	public function run()
	{
        //disable foreign key check for this connection before running seeders
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

		// Category Seeds
		Category::truncate();
		Category::create([
			'position'			=> 1,
			'name'				=> 'All',
			'slug'				=> 'all',
			'description'		=> 'Everything in the shop',
			'pseudo'			=> 'all',
			'is_visible'		=> 1,
			'is_active'			=> 1
		]);
		Category::create([
			'position'			=> 2,
			'name'				=> 'Sale',
			'slug'				=> 'sale',
			'description'		=> 'Products on sale.',
			'pseudo'			=> 'sale',
			'is_visible'		=> 1,
			'is_active'			=> 1
		]);

		// Enable foreign keys
		DB::statement('SET FOREIGN_KEY_CHECKS=1;');
	}

}