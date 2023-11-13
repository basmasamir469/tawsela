<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;


class CreateDriveInvoicesTable extends Migration {

	public function up()
	{
		Schema::create('drive_invoices', function(Blueprint $table) {
			$table->increments('id');
			$table->timestamps();
			$table->decimal('price');
			$table->decimal('taxes');
			$table->decimal('total_cost');
			$table->decimal('waiting_time');
			$table->BigInteger('driver_id')->unsigned();
			$table->integer('order_id')->unsigned();
			$table->BigInteger('user_id')->unsigned();
		});
	}

	public function down()
	{
		Schema::drop('drive_invoices');
	}
}