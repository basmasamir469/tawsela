<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;


class CreateReviewsTable extends Migration {

	public function up()
	{
		Schema::create('reviews', function(Blueprint $table) {
			$table->increments('id');
			$table->timestamps();
			$table->BigInteger('user_id')->unsigned();
			$table->decimal('rate');
			$table->text('comment');
			$table->BigInteger('driver_id')->unsigned();
		});
	}

	public function down()
	{
		Schema::drop('reviews');
	}
}