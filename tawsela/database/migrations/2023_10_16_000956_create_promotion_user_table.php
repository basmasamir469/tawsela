<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;


class CreatePromotionUserTable extends Migration {

	public function up()
	{
		Schema::create('promotion_user', function(Blueprint $table) {
			$table->increments('id');
			$table->timestamps();
			$table->integer('promotion_id')->unsigned();
			$table->BigInteger('user_id')->unsigned();
			$table->integer('order_id')->unsigned();
		});
	}

	public function down()
	{
		Schema::drop('promotion_user');
	}
}