<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;


class CreateOrdersTable extends Migration {

	public function up()
	{
		Schema::create('orders', function(Blueprint $table) {
			$table->increments('id');
			$table->timestamps();
			$table->integer('user_id')->unsigned();
			$table->integer('promotion_id')->unsigned();
			$table->decimal('total_cost');
			$table->decimal('price');
			$table->integer('driver_id')->unsigned()->nullable();
			$table->text('notes')->nullable();
			$table->text('cancel_reason')->nullable();
			$table->integer('order_status');
			$table->decimal('discount');
			$table->string('payment_way');
		});
	}

	public function down()
	{
		Schema::drop('orders');
	}
}