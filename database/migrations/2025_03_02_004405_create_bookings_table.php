<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trip_id')->constrained()->onDelete('cascade');
            $table->string('type'); // flight, hotel, car
            $table->string('status')->default('pending'); // pending, confirmed, cancelled
            $table->boolean('is_policy_compliant')->default(true);
            $table->text('compliance_notes')->nullable();
            $table->decimal('cost', 10, 2);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('bookings');
    }
};