<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DataImportController;
use App\Http\Controllers\Api\ProjectController;
use Illuminate\Support\Facades\Route;

// Public routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', [AuthController::class, 'user']);
    Route::post('/logout', [AuthController::class, 'logout']);

    // Dataset routes
    Route::post('/datasets/upload', [DataImportController::class, 'upload']);
    Route::get('/datasets', [DataImportController::class, 'list']);

    // Project routes
    Route::post('/projects', [ProjectController::class, 'store']);
    Route::get('/projects', [ProjectController::class, 'index']);
    Route::put(   '/projects/{id}',   [ProjectController::class, 'update']); 
    Route::delete('/projects/{id}', [ProjectController::class, 'destroy']); 
});
