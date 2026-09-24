<?php

use App\Http\Controllers\V1\AuthController;
use App\Http\Controllers\V1\Patient\AllergyController;
use App\Http\Controllers\V1\Patient\ConditionController;
use App\Http\Controllers\V1\Patient\ContactController;
use App\Http\Controllers\V1\ProfileController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::prefix('auth')->group(function () {
        Route::post('/login', [AuthController::class, 'login']);
        Route::post('/register/patient', [AuthController::class, 'registerPatient']);
        Route::post('/register/doctor', [AuthController::class, 'registerDoctor']);
    });

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/me', [ProfileController::class, 'show']);

        Route::prefix('patients')->middleware(['role:patient'])->group(function () {
            Route::patch('/profile', [ProfileController::class, 'updatePatientProfile']);
            Route::apiResource('allergies', AllergyController::class);
            Route::apiResource('conditions', ConditionController::class);
            Route::apiResource('contacts', ContactController::class);
        });

        Route::prefix('doctors')->middleware(['role:doctor'])->group(function () {
            Route::patch('/profile', [ProfileController::class, 'updateDoctorProfile']);
        });
    });
});

// * Test token for Doctor : 5|lj9WFVPjiEhsKeogtbaRvfwoKbPkxw5XNQtAZN1Zf79a14bb
// * Test token for patient : 7|7S1TlypSdb2m5KaLzuduI0CdI9Z3vybrlJ6mSST59fe87fee
