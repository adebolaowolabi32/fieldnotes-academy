<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ImportController;
use App\Http\Controllers\LearningController;
use App\Http\Controllers\StudioController;
use Illuminate\Support\Facades\Route;

Route::get('/', [LearningController::class, 'index'])->name('catalog');
Route::get('/courses/{course}', [LearningController::class, 'course'])->name('course');
Route::middleware('guest')->group(function () {
    Route::view('/login', 'auth', ['register' => false])->name('login');
    Route::view('/register', 'auth', ['register' => true])->name('register');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:5,1');
    Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:5,1');
});
Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/learning', [LearningController::class, 'dashboard'])->name('learning');
    Route::post('/courses/{course}/enroll', [LearningController::class, 'enroll'])->name('enroll');
    Route::get('/lessons/{lesson}', [LearningController::class, 'lesson'])->name('lesson');
    Route::post('/lessons/{lesson}/answer', [LearningController::class, 'answer'])->middleware('throttle:30,1')->name('answer');
    Route::get('/certificates/{enrollment}', [LearningController::class, 'certificate'])->name('certificate');
    Route::get('/studio', [StudioController::class, 'index'])->name('studio');
    Route::post('/studio/courses', [StudioController::class, 'store'])->name('studio.store');
    Route::get('/studio/courses/{course}', [StudioController::class, 'edit'])->name('studio.edit');
    Route::post('/studio/courses/{course}/lessons', [StudioController::class, 'lesson'])->name('studio.lesson');
    Route::post('/studio/courses/{course}/publish', [StudioController::class, 'publish'])->name('studio.publish');
    Route::get('/studio/courses/{course}/imports', [ImportController::class, 'index'])->name('imports');
    Route::post('/studio/courses/{course}/imports', [ImportController::class, 'preview'])->name('imports.preview');
    Route::post('/studio/imports/{batch}/start', [ImportController::class, 'start'])->name('imports.start');
    Route::post('/studio/imports/{batch}/undo', [ImportController::class, 'undo'])->name('imports.undo');
});
