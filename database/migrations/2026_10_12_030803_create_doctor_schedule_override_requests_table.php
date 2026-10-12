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
        Schema::create('doctor_schedule_override_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('doctor_id')->constrained()->cascadeOnDelete();
            $table->enum('request_type', ['CREATE', 'UPDATE', 'DELETE']);
            $table->json('data')->nullable(); // ? Data to be applied to doctor_schedule_overrides
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
        Schema::dropIfExists('doctor_schedule_override_requests');
    }
};
