<?php namespace Bedard\Shop\Updates;

use Illuminate\Database\Eloquent\Model;
use October\Rain\Database\Updates\Seeder;
use DB;

use Bedard\Shop\Models\Category;

class TempSeeder extends Seeder {

	public function run()
	{
        //disable foreign key check for this connection before running seeders
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

		// Category Seeds
		$i = 3;
		foreach (['Boards', 'Shirts', 'Hoodies', 'Stickers', 'Jackets'] as $category) {
			Category::create([
				'position'			=> $i,
				'name'				=> $category,
				'slug'				=> strtolower($category),
				'is_visible'		=> 1,
				'is_active'			=> 1
			]);
			$i++;
		}
		foreach (['Winter', 'DVDs'] as $category) {
			Category::create([
				'position'			=> $i,
				'name'				=> $category,
				'slug'				=> strtolower($category),
				'is_visible'		=> 0,
				'is_active'			=> 1
			]);
			$i++;
		}
		Category::create([
			'position'			=> $i,
			'name'				=> 'Hats',
			'slug'				=> 'hats',
			'is_visible'		=> 1,
			'is_active'			=> 0
		]);
		
		// Enable foreign keys
		DB::statement('SET FOREIGN_KEY_CHECKS=1;');
	}

}