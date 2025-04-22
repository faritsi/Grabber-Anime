<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\anime_dataController;

Route::get('/anime/grabber/{mal_id}', [anime_dataController::class, 'grabber']);
Route::get('/anime/grabber', [anime_dataController::class, 'grabberIndex']);
Route::get('/anime/grabber/bulk', [anime_dataController::class, 'bulkGrabber']);


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

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});
// Route::apiResource('/anime', App\Http\Controllers\Api\anime_dataController::class);
