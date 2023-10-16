<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;


class CreateCartTypeTranslationsTable extends Migration {

	public function up()
	{
		Schema::create('cart_type_translations', function(Blueprint $table) {
			$table->increments('id');
			$table->timestamps();
			$table->string('locale')->index();
			$table->integer('cart_type_id')->unsigned();
			$table->string('name');
		});
	}

	public function down()
	{
		Schema::drop('cart_type_translations');
	}
}