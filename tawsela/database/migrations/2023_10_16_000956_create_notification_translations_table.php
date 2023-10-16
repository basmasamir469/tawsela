<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;


class CreateNotificationTranslationsTable extends Migration {

	public function up()
	{
		Schema::create('notification_translations', function(Blueprint $table) {
			$table->increments('id');
			$table->timestamps();
			$table->integer('notification_id')->unsigned();
			$table->string('title');
			$table->text('description')->nullable();
			$table->string('locale')->index();
		});
	}

	public function down()
	{
		Schema::drop('notification_translations');
	}
}