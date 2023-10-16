<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;


class CreateCarBrandTranslationsTable extends Migration {

	public function up()
	{
		Schema::create('car_brand_translations', function(Blueprint $table) {
			$table->increments('id');
			$table->timestamps();
			$table->string('locale')->index();
			$table->string('name');
			$table->integer('car_brand_id')->unsigned();
		});
	}

	public function down()
	{
		Schema::drop('car_brand_translations');
	}
}