<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;


class CreateConversationsTable extends Migration {

	public function up()
	{
		Schema::create('conversations', function(Blueprint $table) {
			$table->increments('id');
			$table->timestamps();
			$table->integer('user_id')->unsigned();
			$table->integer('driver_id')->unsigned();
		});
	}

	public function down()
	{
		Schema::drop('conversations');
	}
}