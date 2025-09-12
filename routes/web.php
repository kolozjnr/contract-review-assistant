<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ContractReviewController;

// Route::get('/', function () {
//     return view('welcome');
// });

Route::get('/', [ContractReviewController::class, 'index']);


Route::post('/review-contract', [ContractReviewController::class, 'review']);

Route::post('/review-contract-file', [ContractReviewController::class, 'reviewFile']);

Route::get('/ping', [ContractReviewController::class, 'ping']);