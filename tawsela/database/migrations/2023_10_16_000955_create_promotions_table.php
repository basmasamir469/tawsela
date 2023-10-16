<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;


class CreatePromotionsTable extends Migration {

	public function up()
	{
		Schema::create('promotions', function(Blueprint $table) {
			$table->increments('id');
			$table->timestamps();
			$table->date('expire_date');
			$table->string('code');
			$table->integer('discount');
		});
	}

	public function down()
	{
		Schema::drop('promotions');
	}
}