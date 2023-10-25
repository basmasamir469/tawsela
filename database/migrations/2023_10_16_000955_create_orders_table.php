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
			$table->BigInteger('user_id')->unsigned();
			$table->string('promo_code')->nullable();
			$table->decimal('total_cost')->nullable();
			$table->decimal('price')->nullable();
			$table->BigInteger('driver_id')->unsigned()->nullable();
			$table->text('notes')->nullable();
			$table->text('cancel_reason')->nullable();
			$table->integer('order_status');
			$table->decimal('discount')->nullable();
			$table->string('payment_way');
		});
	}

	public function down()
	{
		Schema::drop('orders');
	}
}