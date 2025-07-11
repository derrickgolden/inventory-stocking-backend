<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CounterController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware(['permission:create-counter', 'fileUploader:1'])->post("/", [CounterController::class, 'createSingleCounter']);

Route::middleware('permission:readAll-counter')->get("/", [CounterController::class, 'getAllCounter']);

Route::middleware('permission:readSingle-counter')->get("/{id}", [CounterController::class, 'getSingleCounter']);

Route::middleware(['permission:update-counter', 'fileUploader:1'])->put("/{id}", [CounterController::class, 'updateSingleCounter']);

Route::middleware('permission:delete-counter')->patch("/{id}", [CounterController::class, 'deleteSingleCounter']);
