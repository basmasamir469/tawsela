<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique()->nullable();
            $table->string('password')->nullable();
			$table->string('address');
            $table->string('provider_id')->nullable();
            $table->string('provider')->nullable();
			$table->string('phone');
			$table->boolean('notify_status')->nullable()->default(1);
			$table->boolean('voice_alert')->default(1);
			$table->tinyInteger('active_status')->nullable()->default('0');
            $table->tinyInteger('is_active_phone')->default('0');
            $table->tinyInteger('is_active_email')->default('0');
			$table->string('national_number')->nullable();

            $table->rememberToken();
            $table->timestamps();

            // $table->timestamp('email_verified_at')->nullable();
            // $table->text('latitude');
			// $table->text('longitude');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
