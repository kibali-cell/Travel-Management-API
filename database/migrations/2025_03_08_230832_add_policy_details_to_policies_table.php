<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('policies', function (Blueprint $table) {
            $table->boolean('flight_dynamic_pricing')->default(false);
            $table->decimal('flight_max_amount', 10, 2)->nullable();
            $table->integer('flight_advance_booking_days')->nullable();
            $table->string('economy_class')->default('always');
            $table->string('premium_economy_class')->default('always');
            $table->string('business_class')->default('always');
            $table->string('first_class')->default('always');
            $table->boolean('hotel_dynamic_pricing')->default(false);
            $table->integer('hotel_price_threshold_percent')->nullable();
            $table->decimal('hotel_max_amount', 10, 2)->nullable();
            $table->integer('hotel_advance_booking_days')->nullable();
            $table->integer('hotel_max_star_rating')->nullable();
        });
    }

    public function down()
    {
        Schema::table('policies', function (Blueprint $table) {
            $table->dropColumn([
                'flight_dynamic_pricing',
                'flight_max_amount',
                'flight_advance_booking_days',
                'economy_class',
                'premium_economy_class',
                'business_class',
                'first_class',
                'hotel_dynamic_pricing',
                'hotel_price_threshold_percent',
                'hotel_max_amount',
                'hotel_advance_booking_days',
                'hotel_max_star_rating',
            ]);
        });
    }
};