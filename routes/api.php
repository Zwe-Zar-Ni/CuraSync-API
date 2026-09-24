<?php

use App\Http\Controllers\V1\AuthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::prefix('v1')->group(function () {
    Route::prefix('auth')->group(function () {
        Route::post('/login', [AuthController::class, 'login']);
        Route::post('/register/patient', [AuthController::class, 'registerPatient']);
        Route::post('/register/doctor', [AuthController::class, 'registerDoctor']);
    });
});


// * Test token for Doctor : 5|lj9WFVPjiEhsKeogtbaRvfwoKbPkxw5XNQtAZN1Zf79a14bb
