<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;


class CreateVehicleDocsTable extends Migration {

	public function up()
	{
		Schema::create('vehicle_docs', function(Blueprint $table) {
			$table->increments('id');
			$table->timestamps();
			$table->integer('car_type_id')->unsigned();
			$table->integer('car_brand_id')->unsigned();
			$table->integer('car_color')->unsigned();
			$table->string('metal_plate_numbers');
			$table->string('model_year');
			$table->integer('driver_id')->unsigned();
		});
	}

	public function down()
	{
		Schema::drop('vehicle_docs');
	}
}