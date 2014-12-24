<?php namespace Bedard\Shop\Updates;

use Illuminate\Database\Eloquent\Model;
use October\Rain\Database\Updates\Seeder;
use DB;

use Bedard\Shop\Models\Category;
use Bedard\Shop\Models\Product;

class TempSeeder extends Seeder {

	public function run()
	{
        //disable foreign key check for this connection before running seeders
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

		/**
		 * CATEGORY SEEDS
		 */
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

		/**
		 * PRODUCT SEEDS
		 */
		$colors = ['red', 'blue', 'green', 'black', 'orange', 'yellow', 'purple', 'white'];
		$products = ['shirt', 'hat', 'board', 'sticker'];

		$seeds = [];
		foreach ($products as $product) foreach ($colors as $color) $seeds[] = $color.' '.$product;
		shuffle($seeds);

		Product::truncate();
		foreach ($seeds as $seed) {
			$product = Product::create([
				'name' => $seed,
				'slug' => str_replace(' ', '-', $seed),
				'full_price' => rand(10, 20),
				'ounces' => rand(0, 10),
				'description' => "Some awesome $seed... You should totaly buy it."
			]);
			if (strpos($seed, 'board') !== FALSE) $product->categories()->attach(3);
			elseif (strpos($seed, 'hat') !== FALSE) $product->categories()->attach(10);
			elseif (strpos($seed, 'shirt') !== FALSE) $product->categories()->attach(4);
			elseif (strpos($seed, 'sticker') !== FALSE) $product->categories()->attach(6);
		}
		
		// Enable foreign keys
		DB::statement('SET FOREIGN_KEY_CHECKS=1;');
	}

}