<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\BaseController;
use App\Http\Controllers\ServiceCounterController;
use App\Http\Controllers\ServiceQueueController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Events\ServiceQueueUpdated;
use App\Events\QueueUpdated;
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

//

//AuthController
Route::controller(AuthController::class)->group(function () {
    Route::post('login', 'login');
    Route::post('logout', 'logout')->middleware('auth:sanctum');
});


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
    Route::post('queue/call-next/{counterId}', 'callNext');
    Route::post('queue/recall/{queueId}', 'recall');
    Route::post('queue/serve-person/{queueId}', 'servePerson');
    Route::post('queue/complete/{queueId}', 'completeQueue');
    Route::post('queues/{queueId}/move', 'moveQueue');
});
