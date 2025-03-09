<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateApprovalsTableStructure extends Migration
{
    public function up(): void
    {
        Schema::table('approvals', function (Blueprint $table) {
            // Drop foreign keys first
            $table->dropForeign(['booking_id']);
            $table->dropForeign(['approver_id']);

            // Drop the old columns
            $table->dropColumn(['booking_id', 'approver_id', 'status', 'comments']);
        });

        Schema::table('approvals', function (Blueprint $table) {
            // Add new columns for the updated structure
            $table->foreignId('policy_id')->constrained()->onDelete('cascade')->after('id');
            $table->string('restriction')->default('out-of-policy')->after('policy_id'); // 'none', 'out-of-policy', 'all'
            $table->json('approvers')->nullable()->after('restriction'); // JSON array of approvers
        });
    }

    public function down(): void
    {
        // To revert, drop the new columns...
        Schema::table('approvals', function (Blueprint $table) {
            $table->dropColumn(['policy_id', 'restriction', 'approvers']);
        });
        
        // ...and recreate the original columns
        Schema::table('approvals', function (Blueprint $table) {
            $table->foreignId('booking_id')->constrained()->onDelete('cascade');
            $table->foreignId('approver_id')->constrained('users')->onDelete('cascade');
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->text('comments')->nullable();
        });
    }
}
