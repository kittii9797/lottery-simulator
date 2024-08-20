<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\LottoController;

Route::get('/draw', [LottoController::class, 'drawNumbers']);
Route::get('/draws', [LottoController::class, 'getAllDraws']);
Route::post('/tickets', [LottoController::class, 'storeTicket']);
Route::get('/statistics', [LottoController::class, 'getStatistics']);
Route::post('/results', [LottoController::class, 'generateResults']);
Route::post('/reset', [LottoController::class, 'resetGame']);
Route::get('/last-result', [LottoController::class, 'getLastResult']);
