<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;


class CreateOrderDetailsTable extends Migration {

	public function up()
	{
		Schema::create('order_details', function(Blueprint $table) {
			$table->increments('id');
			$table->timestamps();
			$table->string('start_address');
			$table->string('end_address');
			$table->text('start_latitude');
			$table->text('end_latitude');
			$table->text('start_longitude');
			$table->text('end_longitude');
			$table->integer('order_id')->unsigned();
		});
	}

	public function down()
	{
		Schema::drop('order_details');
	}
}