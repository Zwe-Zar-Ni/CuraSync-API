<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\V1\AuthController;
use App\Http\Controllers\V1\ProfileController;
use App\Http\Controllers\V1\AttachmentController;

use App\Http\Controllers\V1\Admin\DoctorController as AdminDoctorController;
use App\Http\Controllers\V1\Admin\ScheduleController as AdminScheduleController;
use App\Http\Controllers\V1\Admin\ScheduleRequestController as AdminScheduleRequestController;
use App\Http\Controllers\V1\Admin\AppointmentController as AdminAppointmentController;

use App\Http\Controllers\V1\Doctor\DoctorSpecialtyController;
use App\Http\Controllers\V1\Doctor\QualificationController;
use App\Http\Controllers\V1\Doctor\ScheduleController as DoctorScheduleController;
use App\Http\Controllers\V1\Doctor\ScheduleRequestController as DoctorScheduleRequestController;
use App\Http\Controllers\V1\Doctor\AppointmentController as DoctorAppointmentController;
// use App\Http\Controllers\V1\Doctor\ScheduleOverrideController;

use App\Http\Controllers\V1\Patient\AllergyController;
use App\Http\Controllers\V1\Patient\ConditionController;
use App\Http\Controllers\V1\Patient\ContactController;
use App\Http\Controllers\V1\Patient\AppointmentController as PatientAppointmentController;

use App\Http\Controllers\V1\Public\DoctorController as PublicDoctorController;

Route::prefix('v1')->group(function () {
    Route::prefix('auth')->group(function () {
        Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:6,1');
        Route::post('/register/patient', [AuthController::class, 'registerPatient']);
        Route::post('/register/doctor', [AuthController::class, 'registerDoctor']);
        Route::post('/refresh', [AuthController::class, 'refresh'])->middleware('throttle:10,1');
        Route::post('/logout', [AuthController::class, 'logout'])->middleware(['auth:api', 'throttle:10,1']);
    });

    Route::middleware('auth:api')->group(function () {
        Route::get('/me', [ProfileController::class, 'show']);
        Route::post('/attachments', [AttachmentController::class, 'store']);

        Route::prefix('admin')->middleware(['role.jwt:admin'])->group(function () {
            Route::get('/doctors', [AdminDoctorController::class, 'index']);
            Route::get('/doctors/{id}', [AdminDoctorController::class, 'show']);
            Route::put('/doctors/{id}', [AdminDoctorController::class, 'update']);

            Route::get('/schedule-requests', [AdminScheduleRequestController::class, 'index']);
            Route::put('/schedule-requests/{id}', [AdminScheduleRequestController::class, 'update']);

            Route::get('/doctor-schedules', [AdminScheduleController::class, 'index']);

            Route::apiResource('appointments', AdminAppointmentController::class)->except(['destroy', 'store'])->names('admin.appointments');
        });

        Route::prefix('patients')->middleware(['role.jwt:patient'])->group(function () {
            Route::patch('/profile', [ProfileController::class, 'updatePatientProfile']);

            Route::apiResource('allergies', AllergyController::class);
            Route::apiResource('conditions', ConditionController::class);
            Route::apiResource('contacts', ContactController::class);

            Route::get('/appointments/upcoming', [PatientAppointmentController::class, 'upcoming'])->name('patient.appointments.upcoming');
            Route::get('/appointments/history/{doctor_id}', [PatientAppointmentController::class, 'appointmentsByDoctor'])->name('patient.appointments.history');
            Route::apiResource('appointments', PatientAppointmentController::class)->except(['destroy'])->names('patient.appointments');
        });

        Route::prefix('doctors')->middleware(['role.jwt:doctor'])->group(function () {
            Route::patch('/profile', [ProfileController::class, 'updateDoctorProfile']);

            Route::apiResource('specialties', DoctorSpecialtyController::class)->except(['show', 'update']);
            Route::apiResource('qualifications', QualificationController::class);
            Route::apiResource('schedules', DoctorScheduleController::class)->only(['index', 'show']);
            Route::apiResource('schedule-requests', DoctorScheduleRequestController::class);
            // Route::apiResource('schedule-overrides', ScheduleOverrideController::class);

            Route::get('/appointments/upcoming', [DoctorAppointmentController::class, 'upcoming'])->name('doctor.appointments.upcoming');
            Route::get('/appointments/history/{patient_id}', [DoctorAppointmentController::class, 'appointmentsByPatient'])->name('doctor.appointments.history');
            Route::apiResource('appointments', DoctorAppointmentController::class)->except(['destroy', 'store'])->names('doctor.appointments');
        });
    });

    Route::prefix('public')->group(function () {
        Route::get('/specializations', [PublicDoctorController::class, 'getSpecializations']);
        Route::get('/specializations/{id}', [PublicDoctorController::class, 'getSpecializationDetails']);
        Route::get('/doctors/{id}', [PublicDoctorController::class, 'getDoctorDetails']);
        Route::get('/doctors/{id}/schedules', [PublicDoctorController::class, 'doctorSchedules']);
        Route::post('/doctors/{id}/schedules/slots', [PublicDoctorController::class, 'generateSlots']);
        Route::get('/search', [PublicDoctorController::class, 'search']);
    });
});
