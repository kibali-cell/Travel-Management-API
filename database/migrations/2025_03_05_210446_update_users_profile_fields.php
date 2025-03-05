<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('avatar')->nullable()->after('email_verified_at');
            $table->string('phone')->nullable()->after('avatar');
            $table->string('address')->nullable()->after('phone');
            $table->string('emergency_contact_name')->nullable()->after('address');
            $table->string('emergency_contact_phone')->nullable()->after('emergency_contact_name');
            $table->string('language')->default('en')->after('emergency_contact_phone');
            $table->boolean('email_notifications')->default(true)->after('language');
            $table->boolean('push_notifications')->default(true)->after('email_notifications');
        });
    }

    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'avatar', 'phone', 'address', 'emergency_contact_name',
                'emergency_contact_phone', 'language',
                'email_notifications', 'push_notifications'
            ]);
        });
    }
};
