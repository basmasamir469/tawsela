<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;


class CreateActivationProcessesTable extends Migration {

	public function up()
	{
		Schema::create('activation_processes', function(Blueprint $table) {
			$table->increments('id');
			$table->timestamps();
			$table->integer('user_id')->unsigned();
			$table->string('code');
			$table->integer('status');
			$table->integer('type');
		});
	}

	public function down()
	{
		Schema::drop('activation_processes');
	}
}