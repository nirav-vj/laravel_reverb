<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ChatController;
use Illuminate\Support\Facades\Route;

// Authentication Routes
Route::middleware('guest')->group(function () {
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register']);
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
});

Route::post('/logout', [AuthController::class, 'logout'])->name('logout')->middleware('auth');

// Chat Dashboard & APIs (Protected)
Route::middleware('auth')->group(function () {
    Route::get('/', [ChatController::class, 'index'])->name('chat.index');
    
    // Polling Fallback
    Route::get('/chat/updates', [ChatController::class, 'pollUpdates'])->name('chat.updates');
    Route::post('/chat/typing', [ChatController::class, 'notifyTyping'])->name('chat.typing');
    
    // Conversation APIs
    Route::get('/messages/{user}', [ChatController::class, 'getMessages'])->name('chat.messages');
    Route::post('/messages/{user}', [ChatController::class, 'sendMessage'])->name('chat.send');
    Route::post('/messages/{user}/seen', [ChatController::class, 'markAsSeen'])->name('chat.seen');
    
    // Message Actions
    Route::post('/messages/{message}/pin', [ChatController::class, 'pinMessage'])->name('chat.pin');
    Route::delete('/messages/{message}', [ChatController::class, 'deleteMessage'])->name('chat.delete');
    Route::post('/messages/{message}/react', [ChatController::class, 'toggleReaction'])->name('chat.react');

    // Profile Settings
    Route::post('/profile/update', [AuthController::class, 'updateProfile'])->name('profile.update');
});

