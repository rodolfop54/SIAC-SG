<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AppointmentController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BotController;
use App\Http\Controllers\PatientEmailAccessController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RegistrationController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', fn () => Inertia::render('Home'))->name('home');
Route::get('/bot', [BotController::class, 'index'])->name('bot');
Route::post('/bot', [BotController::class, 'send'])->middleware('throttle:60,1,public-bot')->block(10, 10);
Route::post('/bot/cambiar-identidad', [BotController::class, 'reset'])->middleware('throttle:20,1,bot-reset')->block(10, 10);
Route::post('/bot/abrir-web', [BotController::class, 'openWeb'])->middleware('throttle:20,1,bot-web')->block(10, 10);
Route::get('/acceso/codigo', [PatientEmailAccessController::class, 'show']);
Route::post('/acceso/codigo', [PatientEmailAccessController::class, 'requestCode'])->middleware('throttle:10,1,email-request')->block(10, 10);
Route::post('/acceso/codigo/verificar', [PatientEmailAccessController::class, 'verify'])->middleware('throttle:20,1,email-verify')->block(10, 10);
Route::post('/acceso/codigo/reiniciar', [PatientEmailAccessController::class, 'restart'])->block(10, 10);
Route::get('/recuperar', [PatientEmailAccessController::class, 'recovery']);
Route::post('/recuperar', [PatientEmailAccessController::class, 'sendReset'])->middleware('throttle:10,1,reset-request')->block(10, 10);
Route::get('/recuperar/{id}/{token}', [PatientEmailAccessController::class, 'resetForm'])->whereUuid('id');
Route::post('/recuperar/confirmar', [PatientEmailAccessController::class, 'resetPassword'])->middleware('throttle:20,1,reset-verify')->block(10, 10);
Route::middleware('guest')->group(function () {
    Route::get('/acceso', [AuthController::class, 'show'])->name('login');
    Route::post('/acceso', [AuthController::class, 'login']);
    Route::get('/registro', [RegistrationController::class, 'show'])->name('register');
    Route::post('/registro', [RegistrationController::class, 'store'])->middleware('throttle:10,1,registration')->block(10, 10);
});
Route::middleware('auth')->group(function () {
    Route::post('/salir', [AuthController::class, 'logout']);
    Route::middleware('role:user')->group(function () {
        Route::get('/perfil', [ProfileController::class, 'show']);
        Route::patch('/perfil', [ProfileController::class, 'updateName'])->middleware('throttle:20,1,profile-name')->block(10, 10);
        Route::post('/perfil/correo', [ProfileController::class, 'requestEmail'])->middleware('throttle:10,1,profile-email')->block(10, 10);
        Route::post('/perfil/correo/verificar', [ProfileController::class, 'confirmEmail'])->middleware('throttle:20,1,profile-verify')->block(10, 10);
        Route::post('/perfil/correo/cancelar', [ProfileController::class, 'cancelEmail'])->block(10, 10);
        Route::post('/perfil/confirmar-acceso', [ProfileController::class, 'requestAccess'])->middleware('throttle:10,1,profile-access')->block(10, 10);
        Route::post('/perfil/confirmar-acceso/verificar', [ProfileController::class, 'confirmAccess'])->middleware('throttle:20,1,profile-access-verify')->block(10, 10);
        Route::get('/disponibilidad', [AppointmentController::class, 'availability']);
        Route::get('/mis-citas', [AppointmentController::class, 'index']);
        Route::get('/citas/{appointment}', [AppointmentController::class, 'show']);
        Route::post('/citas', [AppointmentController::class, 'store'])->middleware('throttle:30,1');
        Route::patch('/citas/{appointment}', [AppointmentController::class, 'update']);
        Route::post('/citas/{appointment}/cancelar', [AppointmentController::class, 'cancel']);
    });
    Route::prefix('admin')->middleware('role:admin')->group(function () {
        Route::get('/agenda', [AdminController::class, 'agenda']);
        Route::post('/franjas', [AdminController::class, 'store']);
        Route::patch('/franjas/{slot}', [AdminController::class, 'toggle']);
        Route::get('/auditoria', [AdminController::class, 'audit']);
        Route::get('/reportes', [AdminController::class, 'reports']);
    });
});
