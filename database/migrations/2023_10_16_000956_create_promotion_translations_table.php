<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;


class CreatePromotionTranslationsTable extends Migration {

	public function up()
	{
		Schema::create('promotion_translations', function(Blueprint $table) {
			$table->increments('id');
			$table->timestamps();
			$table->string('title')->nullable();
			$table->integer('promotion_id')->unsigned();
			$table->string('locale')->index();
		});
	}

	public function down()
	{
		Schema::drop('promotion_translations');
	}
}