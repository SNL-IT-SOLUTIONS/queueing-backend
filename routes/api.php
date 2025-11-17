<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\BaseController;
use App\Http\Controllers\ServiceCounterController;
use App\Http\Controllers\ServiceQueueController;


/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/


Route::controller(ServiceCounterController::class)->group(function () {
    Route::get('counters', 'listCounters');
    Route::get('counters/{id}', 'getCounter');
    Route::post('create/counters', 'createCounter');
    Route::post('update/counters/{id}', 'updateCounter');
    Route::post('archive/counters/{id}', 'archiveCounter');
});


Route::controller(ServiceQueueController::class)->group(function () {
    Route::get('queue/{counterId?}', 'listQueue');
    Route::post('queue/add-person', 'addPerson');
    Route::post('queue/serve-person/{id}', 'servePerson');
});
