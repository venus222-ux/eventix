<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\EventController;
use App\Http\Controllers\Admin\VenueController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\Public\EventController as PublicEventController;
use App\Http\Controllers\WebhookController;
use App\Http\Controllers\Admin\OrderController as AdminOrderController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\VenueSectionController;
use App\Http\Controllers\Admin\RefundRequestController;
use App\Http\Controllers\Public\ConfigController;

Route::post('/stripe/webhook', [WebhookController::class, 'handleStripe']);
Route::get('/config', ConfigController::class);

// Public routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('/reset-password', [AuthController::class, 'resetPassword'])
    ->name('password.reset');
Route::post('/refresh', [AuthController::class, 'refresh']);

Route::get('/events', [PublicEventController::class, 'index']);
Route::get('/events/{event:slug}', [PublicEventController::class, 'show']);
Route::get('/events/{event:slug}/seats', [PublicEventController::class, 'seats']);

// Protected routes with auth + throttle
Route::middleware(['jwt.auth'])->group(function () {
    Route::get('/me', [AuthController::class, 'me']);

    Route::get('/dashboard', function () {
        return response()->json(['message' => 'User Dashboard']);
    });

    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/profile', [AuthController::class, 'profile']);
    Route::put('/profile', [AuthController::class, 'updateProfile']);
    Route::delete('/profile', [AuthController::class, 'destroyProfile']);
    Route::put('/profile/billing', [AuthController::class, 'updateBilling']);

    Route::post('/orders', [OrderController::class, 'store'])->middleware('throttle:10,1');
    Route::get('/orders', [OrderController::class, 'index']);
    Route::get('/orders/{order}', [OrderController::class, 'show']);
    Route::post('/orders/{order}/cancel', [OrderController::class, 'cancel']);
    Route::get('/orders/{order}/invoice', [OrderController::class, 'invoice']);
    Route::get('/orders/{order}/tickets', [OrderController::class, 'tickets']);
    Route::post('/orders/{order}/refund', [OrderController::class, 'refund']);
});

Route::prefix('admin')
    ->middleware(['auth.jwt', 'role:admin'])
    ->group(function () {
        Route::get('/dashboard', [AdminController::class, 'dashboard']);
        Route::get('/traffic', [AdminController::class, 'traffic']);   // was PUBLIC before
        Route::get('/users', [AdminController::class, 'users']);
        Route::delete('/users/{id}', [AdminController::class, 'deleteUser']);

        // Day 2
        Route::apiResource('categories', CategoryController::class);
        Route::apiResource('venues', VenueController::class);

        // The event form's per-section price editor calls /api/admin/venues/{venue}/sections
        Route::get('/venues/{venue}/sections', [VenueSectionController::class, 'index']);

        Route::apiResource('events', EventController::class)->withTrashed(['show']);
        Route::patch('events/{event}/restore', [EventController::class, 'restore'])->withTrashed();
        Route::post('events/{event}/banner', [EventController::class, 'uploadBanner']);
        Route::delete('events/{event}/banner', [EventController::class, 'deleteBanner']);

        Route::get('/orders', [AdminOrderController::class, 'index']);
        Route::post('/orders/{order}/refund', [AdminOrderController::class, 'refund']);
        Route::post('/orders/{order}/resend', [AdminOrderController::class, 'resend']);
        Route::get('/reports/sales', [ReportController::class, 'sales']);

        // Refund requests waiting for a human decision
        Route::get('/refund-requests', [RefundRequestController::class, 'index']);
        Route::post('/refund-requests/{refundRequest}/approve', [RefundRequestController::class, 'approve']);
        Route::post('/refund-requests/{refundRequest}/decline', [RefundRequestController::class, 'decline']);
    });