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
        Schema::create('doctors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->enum('status', [
                "ACTIVE",
                "PENDING_VERIFICATION",
                "INACTIVE",
                "SUSPENDED"
            ])->default("PENDING_VERIFICATION");
            $table->string('license_number');
            $table->decimal('standard_consultation_fee', 10, 2);
            $table->string('bio')->nullable();
            $table->unsignedInteger('total_patient_count')->default(0);
            $table->unsignedInteger('rating_count')->default(0);
            $table->decimal('average_rating', 2, 1)->default(0.0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('doctors');
    }
};
