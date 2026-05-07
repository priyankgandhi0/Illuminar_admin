<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\FirestoreService;
use App\Services\FileStorageService;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class DailyLightCategoryController extends Controller
{
    protected $firestore;
    protected $fileStorage;
    protected $notification;

    public function __construct(FirestoreService $firestore, FileStorageService $fileStorage, NotificationService $notification)
    {
        $this->firestore = $firestore;
        $this->fileStorage = $fileStorage;
        $this->notification = $notification;
    }

    private function isInlineSvg(string $value): bool
    {
        return str_starts_with(trim($value), '<');
    }

    private function getIconUrl(string $iconValue): ?string
    {
        if (!$iconValue) return null;

        // Inline SVG code stored directly in DB
        if ($this->isInlineSvg($iconValue)) {
            return 'data:image/svg+xml;base64,' . base64_encode($iconValue);
        }

        // Legacy: R2 file path
        try {
            $ext = strtolower(pathinfo($iconValue, PATHINFO_EXTENSION));
            if ($ext === 'svg') {
                $content = Storage::disk('r2')->get($iconValue);
                return 'data:image/svg+xml;base64,' . base64_encode($content);
            }
            return Storage::disk('r2')->temporaryUrl($iconValue, now()->addMinutes(30));
        } catch (\Exception $e) {
            return null;
        }
    }

    private function getR2Url(string $storageKey): ?string
    {
        try {
            return Storage::disk('r2')->temporaryUrl($storageKey, now()->addMinutes(60));
        } catch (\Exception $e) {
            return null;
        }
    }

    private function getIconSvg(string $iconValue): ?string
    {
        if (!$iconValue) return null;

        // Inline SVG code
        if ($this->isInlineSvg($iconValue)) {
            return $iconValue;
        }

        // Legacy: read from R2
        if (strtolower(pathinfo($iconValue, PATHINFO_EXTENSION)) === 'svg') {
            try { return Storage::disk('r2')->get($iconValue); } catch (\Exception $e) {}
        }

        return null;
    }

    public function index()
    {
        $categories = Cache::remember('dlc_categories_index', 300, function () {
            $items = $this->firestore->getDailyLightCategories();

            $categories = [];
            foreach ($items as $id => $fields) {
                $languages = [];
                if (!empty($fields['pt_title']['stringValue'])) $languages[] = 'pt';
                if (!empty($fields['en_title']['stringValue'])) $languages[] = 'en';
                if (!empty($fields['es_title']['stringValue'])) $languages[] = 'es';

                $iconValue = $fields['icon']['stringValue'] ?? '';
                $iconImageKey = $fields['iconImage']['stringValue'] ?? '';
                $iconDropdownKey = $fields['iconDropdown']['stringValue'] ?? '';

                $categories[] = [
                    'id' => $id,
                    'title' => $fields['pt_title']['stringValue'] ?? '',
                    'icon_svg' => $this->isInlineSvg($iconValue) ? $iconValue : null,
                    'icon_url' => !$this->isInlineSvg($iconValue) && $iconValue ? $this->getIconUrl($iconValue) : null,
                    'icon_image_url' => $iconImageKey ? $this->getR2Url($iconImageKey) : null,
                    'icon_dropdown_url' => $iconDropdownKey ? $this->getR2Url($iconDropdownKey) : null,
                    'languages' => $languages,
                    'createdAt' => $fields['createdAt']['timestampValue'] ?? '',
                ];
            }

            usort($categories, fn($a, $b) => strcmp($b['createdAt'], $a['createdAt']));
            return $categories;
        });

        return view('pages.daily-light-categories.index', compact('categories'));
    }

    public function store(Request $request)
    {
        $rules = [
            'title_pt' => 'required|string|max:255',
            'title_en' => 'required|string|max:255',
            'title_es' => 'required|string|max:255',
            'svg_code' => 'required|string',
            'send_notification' => 'nullable|boolean',
        ];
        $messages = [
            'title_pt.required' => __('common.required_field'),
            'title_en.required' => __('common.required_field'),
            'title_es.required' => __('common.required_field'),
            'svg_code.required' => __('common.required_field'),
        ];

        if ($request->boolean('send_notification')) {
            $rules['notif_title'] = 'required|string|max:255';
            $rules['notif_message'] = 'required|string|max:500';
            $rules['notif_time'] = 'required|string';
            $messages['notif_title.required'] = __('daily_light_categories.notif_title_required');
            $messages['notif_message.required'] = __('daily_light_categories.notif_message_required');
            $messages['notif_time.required'] = __('daily_light_categories.notif_time_required');
        }

        $request->validate($rules, $messages);

        try {
            $fields = [
                'pt_title' => ['stringValue' => (string) $request->input('title_pt', '')],
                'en_title' => ['stringValue' => (string) $request->input('title_en', '')],
                'es_title' => ['stringValue' => (string) $request->input('title_es', '')],
                'icon' => ['stringValue' => base64_decode($request->input('svg_code', ''))],
                'createdAt' => ['timestampValue' => now()->toIso8601String()],
                'updatedAt' => ['timestampValue' => now()->toIso8601String()],
            ];

            // Upload icon PNG images if provided
            if ($request->hasFile('icon_image')) {
                $iconImageMeta = $this->fileStorage->uploadCategoryIcon($request->file('icon_image'), 'original');
                $fields['iconImage'] = ['stringValue' => $iconImageMeta['storage_key']];
            }
            if ($request->hasFile('icon_dropdown')) {
                $iconDropdownMeta = $this->fileStorage->uploadCategoryIcon($request->file('icon_dropdown'), 'dropdown');
                $fields['iconDropdown'] = ['stringValue' => $iconDropdownMeta['storage_key']];
            }

            $result = $this->firestore->createDailyLightCategory($fields);

            if (!$result['success']) {
                return response()->json(['success' => false, 'message' => __('daily_light_categories.failed_create')], 500);
            }

            // Schedule notification if enabled
            if ($request->boolean('send_notification')) {
                $this->scheduleNotification($request, $result['docId']);
            }

            Cache::forget('dlc_categories_index');

            return response()->json(['success' => true, 'message' => __('daily_light_categories.created_success')]);
        } catch (\Exception $e) {
            \Log::error('DailyLightCategory store exception', ['message' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => __('common.error') . ': ' . $e->getMessage()], 500);
        }
    }

    public function edit(string $id)
    {
        $fields = $this->firestore->getDailyLightCategory($id);

        if (!$fields) {
            return response()->json(['success' => false, 'message' => __('daily_light_categories.not_found')], 404);
        }

        $iconValue = $fields['icon']['stringValue'] ?? '';
        $isInline = $iconValue && $this->isInlineSvg($iconValue);

        $category = [
            'id' => $id,
            'pt_title' => $fields['pt_title']['stringValue'] ?? '',
            'en_title' => $fields['en_title']['stringValue'] ?? '',
            'es_title' => $fields['es_title']['stringValue'] ?? '',
            'icon_svg' => $isInline ? $iconValue : $this->getIconSvg($iconValue),
            'icon_url' => $isInline ? null : $this->getIconUrl($iconValue),
        ];

        return response()->json(['success' => true, 'category' => $category]);
    }

    public function update(Request $request, string $id)
    {
        $rules = [
            'title_pt' => 'required|string|max:255',
            'title_en' => 'required|string|max:255',
            'title_es' => 'required|string|max:255',
            'svg_code' => 'nullable|string',
            'send_notification' => 'nullable|boolean',
        ];
        $messages = [
            'title_pt.required' => __('common.required_field'),
            'title_en.required' => __('common.required_field'),
            'title_es.required' => __('common.required_field'),
        ];

        if ($request->boolean('send_notification')) {
            $rules['notif_title'] = 'required|string|max:255';
            $rules['notif_message'] = 'required|string|max:500';
            $rules['notif_time'] = 'required|string';
            $messages['notif_title.required'] = __('daily_light_categories.notif_title_required');
            $messages['notif_message.required'] = __('daily_light_categories.notif_message_required');
            $messages['notif_time.required'] = __('daily_light_categories.notif_time_required');
        }

        $request->validate($rules, $messages);

        try {
            $existing = $this->firestore->getDailyLightCategory($id);
            $iconValue = $existing['icon']['stringValue'] ?? '';

            $svgCode = $request->input('svg_code') ? base64_decode($request->input('svg_code')) : '';
            if ($svgCode) {
                // Delete old R2 file if it was a legacy path
                if ($iconValue && !$this->isInlineSvg($iconValue)) {
                    try { Storage::disk('r2')->delete($iconValue); } catch (\Exception $e) {}
                }
                $iconValue = $svgCode;
            }

            $fields = [
                'pt_title' => ['stringValue' => (string) $request->input('title_pt', '')],
                'en_title' => ['stringValue' => (string) $request->input('title_en', '')],
                'es_title' => ['stringValue' => (string) $request->input('title_es', '')],
                'icon' => ['stringValue' => $iconValue],
                'updatedAt' => ['timestampValue' => now()->toIso8601String()],
            ];

            // Upload new icon PNG images if SVG changed
            if ($request->hasFile('icon_image')) {
                // Delete old PNG files from R2
                $oldIconImage = $existing['iconImage']['stringValue'] ?? '';
                $oldIconDropdown = $existing['iconDropdown']['stringValue'] ?? '';
                if ($oldIconImage) $this->fileStorage->deleteCategoryIcon($oldIconImage);
                if ($oldIconDropdown) $this->fileStorage->deleteCategoryIcon($oldIconDropdown);

                $iconImageMeta = $this->fileStorage->uploadCategoryIcon($request->file('icon_image'), 'original');
                $fields['iconImage'] = ['stringValue' => $iconImageMeta['storage_key']];
            }
            if ($request->hasFile('icon_dropdown')) {
                $iconDropdownMeta = $this->fileStorage->uploadCategoryIcon($request->file('icon_dropdown'), 'dropdown');
                $fields['iconDropdown'] = ['stringValue' => $iconDropdownMeta['storage_key']];
            }

            $success = $this->firestore->updateDailyLightCategory($id, $fields);

            if (!$success) {
                return response()->json(['success' => false, 'message' => __('daily_light_categories.failed_update')], 500);
            }

            // Schedule notification if enabled
            if ($request->boolean('send_notification')) {
                $this->scheduleNotification($request, $id);
            }

            Cache::forget('dlc_categories_index');

            return response()->json(['success' => true, 'message' => __('daily_light_categories.updated_success')]);
        } catch (\Exception $e) {
            \Log::error('DailyLightCategory update exception', ['message' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => __('common.error') . ': ' . $e->getMessage()], 500);
        }
    }

    public function checkUsage(string $id)
    {
        $inUse = $this->firestore->categoryHasDailyLights($id);
        return response()->json(['in_use' => $inUse]);
    }

    public function destroy(string $id)
    {
        try {
            if ($this->firestore->categoryHasDailyLights($id)) {
                return back()->with('error', __('daily_light_categories.has_daily_lights'));
            }

            // Delete R2 icon files
            $fields = $this->firestore->getDailyLightCategory($id);
            if ($fields) {
                $iconValue = $fields['icon']['stringValue'] ?? '';
                if ($iconValue && !$this->isInlineSvg($iconValue)) {
                    try { Storage::disk('r2')->delete($iconValue); } catch (\Exception $e) {}
                }
                $iconImageKey = $fields['iconImage']['stringValue'] ?? '';
                $iconDropdownKey = $fields['iconDropdown']['stringValue'] ?? '';
                if ($iconImageKey) $this->fileStorage->deleteCategoryIcon($iconImageKey);
                if ($iconDropdownKey) $this->fileStorage->deleteCategoryIcon($iconDropdownKey);
            }

            $success = $this->firestore->deleteDailyLightCategory($id);

            if ($success) {
                Cache::forget('dlc_categories_index');
                return redirect()->route('daily-light-categories.index')->with('success', __('daily_light_categories.deleted_success'));
            }

            return back()->with('error', __('daily_light_categories.failed_delete'));
        } catch (\Exception $e) {
            \Log::error('DailyLightCategory delete exception', ['message' => $e->getMessage()]);
            return back()->with('error', __('common.error') . ': ' . $e->getMessage());
        }
    }

    private function scheduleNotification(Request $request, string $categoryId): void
    {
        $time = $request->input('notif_time'); // HH:mm format (UTC)
        $scheduledAtUtc = Carbon::now('UTC')->format('Y-m-d') . 'T' . $time . ':00Z';

        $notifFields = [
            'title' => ['stringValue' => $request->input('notif_title')],
            'body' => ['stringValue' => $request->input('notif_message')],
            'scheduled_at' => ['timestampValue' => $scheduledAtUtc],
            'sent' => ['booleanValue' => false],
            'source_type' => ['stringValue' => 'daily_light_category'],
            'source_id' => ['stringValue' => $categoryId],
            'admin_id' => ['stringValue' => session('admin.id', '')],
            'createdAt' => ['timestampValue' => now()->toIso8601String()],
        ];

        $this->firestore->createScheduledNotification($notifFields);
    }

    public function cronSendScheduledNotifications()
    {
        $pending = $this->firestore->getPendingScheduledNotifications();

        if (empty($pending)) {
            return response()->json(['status' => 'ok', 'sent' => 0, 'message' => 'No pending notifications.']);
        }

        $now = Carbon::now('UTC');
        $sent = 0;

        foreach ($pending as $id => $fields) {
            $scheduledAt = $fields['scheduled_at']['timestampValue'] ?? '';
            if (!$scheduledAt) continue;

            try {
                $scheduledTime = Carbon::parse($scheduledAt);
                if ($scheduledTime->gt($now)) continue; // Not yet time

                $title = $fields['title']['stringValue'] ?? '';
                $body = $fields['body']['stringValue'] ?? '';
                $senderId = $fields['admin_id']['stringValue'] ?? '';

                if ($title && $body) {
                    $this->notification->sendToAllUsers($title, $body, $senderId, 'daily_light_category');
                }

                $this->firestore->markNotificationSent($id);
                $sent++;
            } catch (\Exception $e) {
                \Log::error('Scheduled notification send failed', ['id' => $id, 'error' => $e->getMessage()]);
            }
        }

        return response()->json(['status' => 'ok', 'sent' => $sent, 'total' => count($pending)]);
    }
}
