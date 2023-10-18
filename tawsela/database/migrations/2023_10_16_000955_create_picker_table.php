<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;


class CreatePickerTable extends Migration {

	public function up()
	{
		Schema::create('picker', function(Blueprint $table) {
			$table->increments('id');
			$table->timestamps();
			$table->text('latitude');
			$table->text('longitude');
			$table->BigInteger('user_id')->unsigned();
		});
	}

	public function down()
	{
		Schema::drop('picker');
	}
}