<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Eloquent\Model;

class CreateForeignKeys extends Migration {

	public function up()
	{
		Schema::table('vehicle_docs', function(Blueprint $table) {
			$table->foreign('driver_id')->references('id')->on('users')
						->onDelete('cascade')
						->onUpdate('cascade');
		});
		Schema::table('orders', function(Blueprint $table) {
			$table->foreign('user_id')->references('id')->on('users')
						->onDelete('cascade')
						->onUpdate('cascade');
		});
		Schema::table('orders', function(Blueprint $table) {
			$table->foreign('driver_id')->references('id')->on('users')
						->onDelete('cascade')
						->onUpdate('cascade');
		});
		Schema::table('drive_invoices', function(Blueprint $table) {
			$table->foreign('driver_id')->references('id')->on('users')
						->onDelete('cascade')
						->onUpdate('cascade');
		});
		Schema::table('drive_invoices', function(Blueprint $table) {
			$table->foreign('order_id')->references('id')->on('orders')
						->onDelete('cascade')
						->onUpdate('cascade');
		});
		Schema::table('drive_invoices', function(Blueprint $table) {
			$table->foreign('user_id')->references('id')->on('users')
						->onDelete('cascade')
						->onUpdate('cascade');
		});
		Schema::table('notifications', function(Blueprint $table) {
			$table->foreign('user_id')->references('id')->on('users')
						->onDelete('restrict')
						->onUpdate('restrict');
		});
		Schema::table('notifications', function(Blueprint $table) {
			$table->foreign('driver_id')->references('id')->on('users')
						->onDelete('cascade')
						->onUpdate('cascade');
		});
		Schema::table('addresses', function(Blueprint $table) {
			$table->foreign('user_id')->references('id')->on('users')
						->onDelete('cascade')
						->onUpdate('cascade');
		});
		Schema::table('reviews', function(Blueprint $table) {
			$table->foreign('user_id')->references('id')->on('reviews')
						->onDelete('cascade')
						->onUpdate('cascade');
		});
		Schema::table('reviews', function(Blueprint $table) {
			$table->foreign('driver_id')->references('id')->on('users')
						->onDelete('cascade')
						->onUpdate('cascade');
		});
		Schema::table('picker', function(Blueprint $table) {
			$table->foreign('user_id')->references('id')->on('users')
						->onDelete('cascade')
						->onUpdate('cascade');
		});
		Schema::table('order_details', function(Blueprint $table) {
			$table->foreign('order_id')->references('id')->on('orders')
						->onDelete('cascade')
						->onUpdate('cascade');
		});
		Schema::table('conversations', function(Blueprint $table) {
			$table->foreign('user_id')->references('id')->on('users')
						->onDelete('cascade')
						->onUpdate('cascade');
		});
		Schema::table('conversations', function(Blueprint $table) {
			$table->foreign('driver_id')->references('id')->on('users')
						->onDelete('cascade')
						->onUpdate('cascade');
		});
		Schema::table('promotion_user', function(Blueprint $table) {
			$table->foreign('promotion_id')->references('id')->on('promotions')
						->onDelete('cascade')
						->onUpdate('cascade');
		});
		Schema::table('promotion_user', function(Blueprint $table) {
			$table->foreign('user_id')->references('id')->on('users')
						->onDelete('cascade')
						->onUpdate('cascade');
		});
		Schema::table('promotion_user', function(Blueprint $table) {
			$table->foreign('order_id')->references('id')->on('orders')
						->onDelete('cascade')
						->onUpdate('cascade');
		});
		Schema::table('cart_type_translations', function(Blueprint $table) {
			$table->foreign('cart_type_id')->references('id')->on('car_types')
						->onDelete('cascade')
						->onUpdate('cascade');
		});
		Schema::table('car_brand_translations', function(Blueprint $table) {
			$table->foreign('car_brand_id')->references('id')->on('car_brands')
						->onDelete('cascade')
						->onUpdate('cascade');
		});
		Schema::table('car_color_translations', function(Blueprint $table) {
			$table->foreign('car_color_id')->references('id')->on('car_colors')
						->onDelete('cascade')
						->onUpdate('cascade');
		});
		Schema::table('promotion_translations', function(Blueprint $table) {
			$table->foreign('promotion_id')->references('id')->on('promotions')
						->onDelete('cascade')
						->onUpdate('cascade');
		});
		Schema::table('notification_translations', function(Blueprint $table) {
			$table->foreign('notification_id')->references('id')->on('notifications')
						->onDelete('cascade')
						->onUpdate('cascade');
		});
	}

	public function down()
	{
		Schema::table('vehicle_docs', function(Blueprint $table) {
			$table->dropForeign('vehicle_docs_driver_id_foreign');
		});
		Schema::table('orders', function(Blueprint $table) {
			$table->dropForeign('orders_user_id_foreign');
		});
		Schema::table('orders', function(Blueprint $table) {
			$table->dropForeign('orders_driver_id_foreign');
		});
		Schema::table('drive_invoices', function(Blueprint $table) {
			$table->dropForeign('drive_invoices_driver_id_foreign');
		});
		Schema::table('drive_invoices', function(Blueprint $table) {
			$table->dropForeign('drive_invoices_order_id_foreign');
		});
		Schema::table('drive_invoices', function(Blueprint $table) {
			$table->dropForeign('drive_invoices_user_id_foreign');
		});
		Schema::table('notifications', function(Blueprint $table) {
			$table->dropForeign('notifications_user_id_foreign');
		});
		Schema::table('notifications', function(Blueprint $table) {
			$table->dropForeign('notifications_driver_id_foreign');
		});
		Schema::table('addresses', function(Blueprint $table) {
			$table->dropForeign('addresses_user_id_foreign');
		});
		Schema::table('reviews', function(Blueprint $table) {
			$table->dropForeign('reviews_user_id_foreign');
		});
		Schema::table('reviews', function(Blueprint $table) {
			$table->dropForeign('reviews_driver_id_foreign');
		});
		Schema::table('picker', function(Blueprint $table) {
			$table->dropForeign('picker_user_id_foreign');
		});
		Schema::table('order_details', function(Blueprint $table) {
			$table->dropForeign('order_details_order_id_foreign');
		});
		Schema::table('conversations', function(Blueprint $table) {
			$table->dropForeign('conversations_user_id_foreign');
		});
		Schema::table('conversations', function(Blueprint $table) {
			$table->dropForeign('conversations_driver_id_foreign');
		});
		Schema::table('promotion_user', function(Blueprint $table) {
			$table->dropForeign('promotion_user_promotion_id_foreign');
		});
		Schema::table('promotion_user', function(Blueprint $table) {
			$table->dropForeign('promotion_user_user_id_foreign');
		});
		Schema::table('promotion_user', function(Blueprint $table) {
			$table->dropForeign('promotion_user_order_id_foreign');
		});
		Schema::table('cart_type_translations', function(Blueprint $table) {
			$table->dropForeign('cart_type_translations_cart_type_id_foreign');
		});
		Schema::table('car_brand_translations', function(Blueprint $table) {
			$table->dropForeign('car_brand_translations_car_brand_id_foreign');
		});
		Schema::table('car_color_translations', function(Blueprint $table) {
			$table->dropForeign('car_color_translations_car_color_id_foreign');
		});
		Schema::table('promotion_translations', function(Blueprint $table) {
			$table->dropForeign('promotion_translations_promotion_id_foreign');
		});
		Schema::table('notification_translations', function(Blueprint $table) {
			$table->dropForeign('notification_translations_notification_id_foreign');
		});
	}
}