<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;


class CreateCarColorTranslationsTable extends Migration {

	public function up()
	{
		Schema::create('car_color_translations', function(Blueprint $table) {
			$table->increments('id');
			$table->timestamps();
			$table->string('name');
			$table->integer('car_color_id')->unsigned();
			$table->string('locale')->index();
		});
	}

	public function down()
	{
		Schema::drop('car_color_translations');
	}
}