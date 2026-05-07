<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\OccasionController;
use App\Http\Controllers\AnnouncementController;
use App\Http\Controllers\ReportedAnnouncementController;
use App\Http\Controllers\ReportedOccasionController;
use App\Http\Controllers\AnnouncementMessageController;
use App\Http\Controllers\BereavementMessageController;
use App\Http\Controllers\ReportedMessageController;
use App\Http\Controllers\DailyLightController;
use App\Http\Controllers\JornadaController;
use App\Http\Controllers\JornadaCategoryController;
use App\Http\Controllers\DailyLightCategoryController;
use App\Http\Controllers\SubscriptionController;

Route::get('/', function () {
    return redirect()->route('login');
});

Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [LoginController::class, 'login'])->name('login.post');

Route::middleware('admin.auth')->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

        Route::get('/users', [UserController::class, 'index'])->name('users.index');
        Route::post('/users', [UserController::class, 'store'])->name('users.store');
        Route::get('/users/{uid}', [UserController::class, 'show'])->name('users.show');
        Route::post('/users/{uid}/toggle-active', [UserController::class, 'toggleActive'])->name('users.toggle-active');
        Route::post('/users/{uid}/update-fields', [UserController::class, 'updateFields'])->name('users.update-fields');
        Route::delete('/users/{uid}', [UserController::class, 'destroy'])->name('users.destroy');

        Route::post('/daily-lights/check-date', [DailyLightController::class, 'checkDate'])->name('daily-lights.check-date');
        Route::post('/daily-lights/toggle-notification', [DailyLightController::class, 'toggleDailyNotification'])->name('daily-lights.toggle-notification');
        Route::post('/daily-lights/store-main', [DailyLightController::class, 'storeMain'])->name('daily-lights.store-main');
        Route::post('/daily-lights/{id}/upload-file', [DailyLightController::class, 'uploadFile'])->name('daily-lights.upload-file');
        Route::post('/daily-lights/{id}/store-lang', [DailyLightController::class, 'storeLang'])->name('daily-lights.store-lang');
        Route::post('/daily-lights/{dailyLight}/toggle-featured', [DailyLightController::class, 'toggleFeatured'])->name('daily-lights.toggle-featured');
        Route::get('/comments', [DailyLightController::class, 'commentsIndex'])->name('comments.index');
        Route::get('/comment-moderation', [DailyLightController::class, 'commentModeration'])->name('comment-moderation.index');
        // Route::get('/reported-comments', [DailyLightController::class, 'reportedComments'])->name('reported-comments.index');
        // Route::get('/hidden-comments', [DailyLightController::class, 'hiddenComments'])->name('hidden-comments.index');
        Route::get('/daily-lights/{id}/comments', [DailyLightController::class, 'comments'])->name('daily-lights.comments');
        Route::delete('/daily-lights/{id}/comments/{commentId}', [DailyLightController::class, 'deleteComment'])->name('daily-lights.comments.destroy');
        Route::post('/daily-lights/{id}/comments/{commentId}/clear-report', [DailyLightController::class, 'clearCommentReport'])->name('daily-lights.comments.clear-report');
        Route::post('/daily-lights/{id}/comments/{commentId}/hide', [DailyLightController::class, 'hideComment'])->name('daily-lights.comments.hide');
        Route::post('/daily-lights/{id}/comments/{commentId}/approve', [DailyLightController::class, 'approveComment'])->name('daily-lights.comments.approve');
        Route::post('/daily-lights/{id}/comments/{commentId}/spam', [DailyLightController::class, 'markCommentAsSpam'])->name('daily-lights.comments.spam');
        Route::post('/daily-lights/{id}/comments/{commentId}/unspam', [DailyLightController::class, 'unspamComment'])->name('daily-lights.comments.unspam');
        Route::post('/daily-lights/{id}/comments/{commentId}/approve-prohibited', [DailyLightController::class, 'approveProhibitedWord'])->name('daily-lights.comments.approve-prohibited');
        Route::delete('/daily-lights/{id}/comments/{commentId}/replies/{replyId}', [DailyLightController::class, 'deleteReply'])->name('daily-lights.replies.destroy');
        Route::post('/daily-lights/{id}/comments/{commentId}/replies/{replyId}/approve-prohibited', [DailyLightController::class, 'approveReplyProhibitedWord'])->name('daily-lights.replies.approve-prohibited');
        Route::post('/daily-lights/{id}/comments/{commentId}/replies/{replyId}/clear-report', [DailyLightController::class, 'clearReplyReport'])->name('daily-lights.replies.clear-report');
        Route::post('/daily-lights/{id}/comments/{commentId}/replies/{replyId}/hide', [DailyLightController::class, 'hideReply'])->name('daily-lights.replies.hide');
        Route::post('/daily-lights/{id}/comments/{commentId}/replies/{replyId}/approve', [DailyLightController::class, 'approveReply'])->name('daily-lights.replies.approve');
        Route::post('/users/{userId}/toggle-block', [DailyLightController::class, 'toggleBlockUser'])->name('users.toggle-block');
        Route::resource('daily-lights', DailyLightController::class)->except(['show']);

        Route::post('/jornadas/reorder', [JornadaController::class, 'reorder'])->name('jornadas.reorder');
        Route::post('/jornadas/store-main', [JornadaController::class, 'storeMain'])->name('jornadas.store-main');
        Route::post('/jornadas/{id}/upload-file', [JornadaController::class, 'uploadFile'])->name('jornadas.upload-file');
        Route::post('/jornadas/{id}/store-lang', [JornadaController::class, 'storeLang'])->name('jornadas.store-lang');
        Route::get('/jornadas/{id}/comments', [JornadaController::class, 'jornadaComments'])->name('jornadas.comments');
        Route::delete('/jornadas/{id}/lessons/{lessonId}/comments/{commentId}', [JornadaController::class, 'deleteJornadaComment'])->name('jornadas.comments.destroy');
        Route::post('/jornadas/{id}/lessons/{lessonId}/comments/{commentId}/hide', [JornadaController::class, 'hideJornadaComment'])->name('jornadas.comments.hide');
        Route::post('/jornadas/{id}/lessons/{lessonId}/comments/{commentId}/approve', [JornadaController::class, 'approveJornadaComment'])->name('jornadas.comments.approve');
        Route::resource('jornadas', JornadaController::class)->except(['show']);

        Route::get('/daily-light-categories/{id}/check-usage', [DailyLightCategoryController::class, 'checkUsage'])->name('daily-light-categories.check-usage');
        Route::resource('daily-light-categories', DailyLightCategoryController::class)->except(['show', 'create']);

        Route::get('/subscription-details', [SubscriptionController::class, 'index'])->name('subscription.index');
        Route::post('/subscription-details/save', [SubscriptionController::class, 'save'])->name('subscription.save');

        Route::post('/jornada-categories/reorder', [JornadaCategoryController::class, 'reorder'])->name('jornada-categories.reorder');
        Route::get('/jornada-categories/{id}/check-usage', [JornadaCategoryController::class, 'checkUsage'])->name('jornada-categories.check-usage');
        Route::resource('jornada-categories', JornadaCategoryController::class)->except(['show', 'create']);
});
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

// Cron route - called by external cron service (e.g. cron-job.org) at midnight
Route::get('/cron/auto-publish', function () {
    if (request()->query('token') !== env('CRON_SECRET_TOKEN')) {
        abort(403);
    }
    \Illuminate\Support\Facades\Artisan::call('daily-lights:auto-publish');
    return response()->json(['status' => 'ok', 'output' => \Illuminate\Support\Facades\Artisan::output()]);
});

// Cron route - send daily morning notification at 8 AM
Route::get('/cron/daily-notification', function () {
    if (request()->query('token') !== env('CRON_SECRET_TOKEN')) {
        abort(403);
    }
    return app(\App\Http\Controllers\DailyLightController::class)->cronDailyNotification();
})->name('cron.daily-notification');

// Cron route - send scheduled notifications (category notifications etc.)
Route::get('/cron/send-scheduled-notifications', function () {
    if (request()->query('token') !== env('CRON_SECRET_TOKEN')) {
        abort(403);
    }
    return app(\App\Http\Controllers\DailyLightCategoryController::class)->cronSendScheduledNotifications();
})->name('cron.send-scheduled-notifications');

// Combined cron route - runs ALL jobs in one call (use this in cron-job.org)
Route::get('/cron/run', function () {
    if (request()->query('token') !== env('CRON_SECRET_TOKEN')) {
        abort(403);
    }

    // Close the HTTP connection immediately so nginx does not 504.
    // Processing continues in the PHP-FPM worker after the client is released.
    ignore_user_abort(true);
    set_time_limit(300);

    while (ob_get_level()) {
        ob_end_clean();
    }
    header('Content-Type: application/json');
    header('Connection: close');
    $body = json_encode(['status' => 'ok', 'message' => 'cron triggered, processing in background']);
    header('Content-Length: ' . strlen($body));
    echo $body;
    flush();
    if (function_exists('fastcgi_finish_request')) {
        fastcgi_finish_request();
    }

    // Run after HTTP response has been delivered to the client
    app(\App\Http\Controllers\DailyLightController::class)->cronDailyNotification();
    app(\App\Http\Controllers\DailyLightCategoryController::class)->cronSendScheduledNotifications();
})->name('cron.run');

// One-time migration: copy jornadas_categoty → jornadas_category
// Run once in browser: /migrate/jornada-categories?token=YOUR_CRON_TOKEN
// Delete this route after migration is confirmed.
Route::get('/migrate/jornada-categories', function () {
    if (request()->query('token') !== env('CRON_SECRET_TOKEN')) {
        abort(403);
    }

    $projectId = env('FIREBASE_PROJECT_ID');
    $token     = \App\Services\GoogleAccessTokenService::generate();
    $base      = "https://firestore.googleapis.com/v1/projects/{$projectId}/databases/(default)/documents";

    // 1. Read all docs from the old (typo) collection
    $srcResp = \Illuminate\Support\Facades\Http::withToken($token)
        ->timeout(15)
        ->get("{$base}/jornadas_categoty");

    if (!$srcResp->successful()) {
        return response()->json(['status' => 'error', 'message' => 'Failed to read source collection', 'http' => $srcResp->status()]);
    }

    $docs    = $srcResp->json('documents') ?? [];
    $copied  = [];
    $failed  = [];

    // 2. Copy each doc to jornadas_category with the same document ID
    foreach ($docs as $doc) {
        $docId  = basename($doc['name']);
        $fields = $doc['fields'] ?? [];

        $destResp = \Illuminate\Support\Facades\Http::withToken($token)
            ->timeout(10)
            ->patch("{$base}/jornadas_category/{$docId}", ['fields' => $fields]);

        if ($destResp->successful()) {
            $copied[] = $docId;
        } else {
            $failed[] = ['id' => $docId, 'status' => $destResp->status()];
        }
    }

    return response()->json([
        'status'      => empty($failed) ? 'ok' : 'partial',
        'total'       => count($docs),
        'copied'      => count($copied),
        'copied_ids'  => $copied,
        'failed'      => $failed,
        'next_step'   => 'Verify the jornadas_category collection in Firestore, then delete jornadas_categoty manually.',
    ]);
});

// Cache clear route - use in browser when no SSH access
Route::get('/clear-cache', function () {
    if (request()->query('token') !== env('CRON_SECRET_TOKEN')) {
        abort(403);
    }
    \Illuminate\Support\Facades\Artisan::call('cache:clear');
    \Illuminate\Support\Facades\Artisan::call('config:clear');
    \Illuminate\Support\Facades\Artisan::call('view:clear');
    \Illuminate\Support\Facades\Artisan::call('route:clear');
    return response()->json(['status' => 'ok', 'message' => 'All caches cleared.']);
});


