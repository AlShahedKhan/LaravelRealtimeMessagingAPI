<?php

use Illuminate\Http\Request;
use App\Events\TestBroadcastEvent;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\MessageController;

// Registration Route
Route::post('register', [AuthController::class, 'register']);

// Login Route
Route::post('login', [AuthController::class, 'login']);

Route::middleware('auth:api')->group(function () {

    Route::post('logout', [AuthController::class, 'logout']);

    Route::post('/messages', [MessageController::class, 'sendMessage']);

    Route::get('/messages/{receiver_id}', [MessageController::class, 'getMessages']);

    Route::get('/conversations', [MessageController::class, 'getConversations']);

});

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:api');

Route::get('/broadcast-test', function () {
    event(new TestBroadcastEvent('This is a test message!'));
    return response()->json(['status' => 'Event broadcasted']);
});
