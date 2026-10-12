<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    /**
     * ? request_type:
     * ?    CREATE (New weekly shift)
     * ?    UPDATE (Change existing shift hours/duration)
     * ?    DELETE (Remove shift)
     * ?status:
     * ?    PENDING (Waiting for admin action)
     * ?    APPROVED (Admin accepted; backend applies change to DoctorSchedule)
     * ?    REJECTED (Admin declined)
     */
    public function up(): void
    {
        Schema::create('doctor_schedule_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('doctor_id')->constrained()->cascadeOnDelete();
            $table->foreignId('doctor_schedule_id')->nullable()->constrained()->nullOnDelete();
            $table->enum('request_type', ['CREATE', 'UPDATE', 'DELETE']);
            $table->json('data')->nullable(); // ? Data to be applied to doctor_schedules
            $table->string('doctor_note')->nullable();
            $table->enum('status', ['PENDING', 'APPROVED', 'REJECTED']);
            $table->string('admin_note')->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('doctor_schedule_requests');
    }
};
