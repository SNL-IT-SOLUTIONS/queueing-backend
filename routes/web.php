<?php

use Illuminate\Support\Facades\Route;
use Ramsey\Collection\Queue;
use App\Models\ServiceCounter;
use App\Events\QueueUpdated;
/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});



Route::get('/test', function () {
    $counter = ServiceCounter::first();

    event(new QueueUpdated($counter));

    return "Event Fired!";
});
