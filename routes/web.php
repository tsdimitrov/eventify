<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\TaskController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/dashboard')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('events', [EventController::class, 'index'])->name('events.index');
    Route::get('events/create', [EventController::class, 'create'])->name('events.create');
    Route::post('events', [EventController::class, 'store'])->name('events.store');
    Route::get('events/{event}', [EventController::class, 'show'])->name('events.show');

    Route::get('tasks', [TaskController::class, 'index'])->name('tasks.index');
    Route::patch('tasks/{task}', [TaskController::class, 'update'])->name('tasks.update');
});

require __DIR__.'/settings.php';
