<?php

use App\Http\Controllers\FileController;
use App\Http\Controllers\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
    
    Route::get('/products', [FileController::class, 'products']);
    
    Route::get('/status/{upload_id}', [FileController::class, 'status'])->where('upload_id', '[0-9]+');

    Route::post('/upload', [FileController::class, 'upload']);

    Route::post('/logout', [UserController::class, 'logout']);
});

Route::post('/register', [UserController::class, 'register']);

Route::post('/login', [UserController::class, 'login']);
