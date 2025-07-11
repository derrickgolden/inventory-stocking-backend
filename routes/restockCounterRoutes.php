<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\RestockCounterController;

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

Route::middleware('permission:create-stock')->post("/", [RestockCounterController::class, 'createRestockCounter']);

Route::middleware('permission:readAll-stock')->get("/", [RestockCounterController::class, 'getAllRestockCounter']);

Route::middleware('permission:readAll-stock')->get("/{id}", [RestockCounterController::class, 'getSingleRestockCounter']);
