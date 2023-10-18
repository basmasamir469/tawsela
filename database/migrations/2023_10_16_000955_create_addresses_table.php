<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;


class CreateAddressesTable extends Migration {

	public function up()
	{
		Schema::create('addresses', function(Blueprint $table) {
			$table->increments('id');
			$table->timestamps();
			$table->integer('type');
			$table->text('longitude');
			$table->text('latitude');
			$table->BigInteger('user_id')->unsigned();
			$table->string('name');
		});
	}

	public function down()
	{
		Schema::drop('addresses');
	}
}