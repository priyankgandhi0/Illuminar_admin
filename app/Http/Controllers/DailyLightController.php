<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\FirestoreService;
use App\Services\FileStorageService;
use App\Services\DateTimeService;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Cache;
use Aws\S3\S3Client;

class DailyLightController extends Controller
{
    protected $firestore;
    protected $fileStorage;
    protected $notification;

    public function __construct(
        FirestoreService $firestore,
        FileStorageService $fileStorage,
        NotificationService $notification
    ) {
        $this->firestore = $firestore;
        $this->fileStorage = $fileStorage;
        $this->notification = $notification;
    }

    public function index()
    {
        $items = $this->firestore->getDailyLights();

        $dailyLights = [];
        foreach ($items as $id => $data) {
            $fields = $data['fields'];
            $rawDate = $fields['date']['stringValue'] ?? '';
            $rawTime = $fields['publishTime']['stringValue'] ?? '';
            $utcDatetime = $fields['publishDateTimeUtc']['stringValue'] ?? '';
            $status = $fields['status']['stringValue'] ?? 'draft';
            $sortTimestamp = 0;

            // Auto-publish: if publish datetime has passed and status is 'scheduled'
            if ($status === 'scheduled' && ($utcDatetime || $rawDate)) {
                try {
                    $passed = $utcDatetime
                        ? DateTimeService::isUtcInThePast($utcDatetime)
                        : $this->isPublishTimePassed($rawDate, $rawTime ?: null);
                    if ($passed) {
                        $status = 'published';
                        $this->firestore->updateDailyLightStatus($id, 'published');
                    }
                } catch (\Exception $e) {}
            }

            // Display: convert UTC → Brazil → dd-mm-yyyy HH:MM
            // Falls back to legacy string fields for records saved before this system
            if ($utcDatetime) { 
                $brazilDt = DateTimeService::utcToBrazil($utcDatetime);
                $displayDate = $brazilDt->format('d-m-Y');
                $displayTime = $brazilDt->format('H:i');
                $sortTimestamp = $brazilDt->timestamp;
            } else {
                $displayDate = $rawDate;
                $displayTime = $rawTime;
                try {
                    $parsedDate = $this->parseDailyLightDate($rawDate, $rawTime ?: null);
                    $displayDate = $parsedDate->format('d-m-Y');
                    $sortTimestamp = $parsedDate->timestamp;
                } catch (\Exception $e) {
                }
            }

            if (!$utcDatetime && $sortTimestamp === 0 && $rawDate !== '') {
                try {
                    $fallbackDate = $this->parseDailyLightDate($rawDate, null);
                    $sortTimestamp = $fallbackDate->timestamp;
                } catch (\Exception $e) {}
            }

            $dailyLights[] = [
                'id' => $id,
                'title' => $data['ptTitle'] ?? '',
                'date' => $displayDate,
                'publishTime' => $displayTime,
                'rawDate' => $rawDate,
                'status' => $status,
                'isFeatured' => $fields['isFeatured']['booleanValue'] ?? true,
                'languages' => $data['languages'] ?? [],
                'createdAt' => $fields['createdAt']['timestampValue'] ?? '',
                'sortTimestamp' => $sortTimestamp ?? 0,
            ];
        }

        usort($dailyLights, function ($a, $b) {
            $dateCompare = ($b['sortTimestamp'] ?? 0) <=> ($a['sortTimestamp'] ?? 0);
            if ($dateCompare !== 0) {
                return $dateCompare;
            }

            return strcmp($b['createdAt'], $a['createdAt']);
        });

        // Daily notification settings (commented out)
        // $notifSetting = $this->firestore->getSetting('daily_notification');
        // $dailyNotificationEnabled = ($notifSetting['enabled']['booleanValue'] ?? false) === true;
        // $notifTitle = $notifSetting['title']['stringValue'] ?? 'Daily Light';
        // $notifBody = $notifSetting['body']['stringValue'] ?? 'Start your day with today\'s Daily Light';

        return view('pages.daily-lights.index', compact('dailyLights'));
    }

    public function create()
    {
        $categories = $this->getDailyLightCategoryOptions();
        return view('pages.daily-lights.create', compact('categories'));
    }

    public function store(Request $request)
    {
        ini_set('max_execution_time', 300);
        ini_set('memory_limit', '512M');
        set_time_limit(300);

        $enabledLangs = $this->getEnabledLanguages($request);

        $rules = [
            'publishDate' => 'required|date',
            'publishTime' => 'nullable|date_format:H:i',
        ];
        $messages = [];

        foreach ($enabledLangs as $lang) {
            $rules["title_{$lang}"] = 'required|string|max:255';
            $rules["description_{$lang}"] = 'required|string';
            $messages["title_{$lang}.required"] = __('common.required_field');
            $messages["description_{$lang}.required"] = __('common.required_field');

            // Steps 1-4: required
            for ($i = 1; $i <= 4; $i++) {
                $rules["step_category_{$lang}_{$i}"] = 'required|string';
                $rules["section_title_{$lang}_{$i}"] = 'required|string|max:255';
                $rules["section_description_{$lang}_{$i}"] = 'required|string';
                $rules["section_image_{$lang}_{$i}"] = 'required|image|max:5120';
                $rules["section_audio_{$lang}_{$i}"] = 'required|file|mimes:mp3,wav,mpeg|max:51200';
                $messages["step_category_{$lang}_{$i}.required"] = __('common.required_field');
                $messages["section_title_{$lang}_{$i}.required"] = __('common.required_field');
                $messages["section_description_{$lang}_{$i}.required"] = __('common.required_field');
                $messages["section_image_{$lang}_{$i}.required"] = __('common.image_required');
                $messages["section_audio_{$lang}_{$i}.required"] = __('common.audio_required');
            }

            // Step 5: required (same as steps 1-4, plus bg_image)
            $rules["section_title_{$lang}_5"] = 'required|string|max:255';
            $rules["section_description_{$lang}_5"] = 'required|string';
            $rules["section_image_{$lang}_5"] = 'required|image|max:5120';
            $rules["section_bg_image_{$lang}_5"] = 'required|image|max:5120';
            $rules["section_audio_{$lang}_5"] = 'required|file|mimes:mp3,wav,mpeg|max:51200';
            $messages["section_title_{$lang}_5.required"] = __('common.required_field');
            $messages["section_description_{$lang}_5.required"] = __('common.required_field');
            $messages["section_image_{$lang}_5.required"] = __('common.image_required');
            $messages["section_bg_image_{$lang}_5.required"] = __('common.bg_image_required');
            $messages["section_audio_{$lang}_5.required"] = __('common.audio_required');
        }

        // Per-language notification validation (saved to DB only, not sent)
        foreach ($enabledLangs as $lang) {
            if ($request->boolean("send_notification_{$lang}")) {
                $rules["notif_title_{$lang}"] = 'required|string|max:255';
                $rules["notif_message_{$lang}"] = 'required|string|max:500';
                $rules["notif_time_{$lang}"] = 'required|string';
                $messages["notif_title_{$lang}.required"] = __('daily_light_categories.notif_title_required');
                $messages["notif_message_{$lang}.required"] = __('daily_light_categories.notif_message_required');
                $messages["notif_time_{$lang}.required"] = __('daily_light_categories.notif_time_required');
            }
        }

        try {
            $request->validate($rules, $messages);
        } catch (\Illuminate\Validation\ValidationException $e) {
            \Log::warning('Daily Light store validation failed', [
                'errors' => $e->errors(),
                'enabledLangs' => $enabledLangs,
                'filesCount' => count($request->allFiles()),
            ]);
            throw $e;
        }

        // Validate times
        $publishDate = (string) $request->input('publishDate');
        $publishTime = (string) ($request->input('publishTime') ?? '');
        $brazilNow = $this->brazilNow();
        $tz = $brazilNow->timezone;
        $isToday = \Carbon\Carbon::parse($publishDate, $tz)->isToday();

        // Notification time must be after publish time
        if ($publishTime) {
            foreach ($enabledLangs as $lang) {
                if ($request->boolean("send_notification_{$lang}")) {
                    $notifTime = $request->input("notif_time_{$lang}");
                    if ($notifTime && $notifTime <= $publishTime) {
                        return back()->withErrors(["notif_time_{$lang}" => __('daily_light_categories.notif_time_before_publish')])->withInput();
                    }
                }
            }
        }

        if ($isToday) {
            $nowMinutes = $brazilNow->hour * 60 + $brazilNow->minute;

            // Publish time must be in the future
            if ($publishTime) {
                $parts = explode(':', $publishTime);
                $selectedMinutes = (int) $parts[0] * 60 + (int) ($parts[1] ?? 0);
                if ($selectedMinutes <= $nowMinutes) {
                    return back()->withErrors(['publishTime' => __('daily_lights.publish_time_must_be_future')])->withInput();
                }
            }

            foreach ($enabledLangs as $lang) {
                if ($request->boolean("send_notification_{$lang}")) {
                    $time = $request->input("notif_time_{$lang}");
                    if ($time) {
                        $parts = explode(':', $time);
                        $selectedMinutes = (int) $parts[0] * 60 + (int) ($parts[1] ?? 0);
                        if ($selectedMinutes <= $nowMinutes) {
                            return back()->withErrors(["notif_time_{$lang}" => __('daily_light_categories.notif_time_must_be_future')])->withInput();
                        }
                    }
                }
            }
        }

        try {
            $docId = \Carbon\Carbon::parse($publishDate)->format('d-m-Y');

            // Check if document already exists (non-deleted)
            if ($this->firestore->dailyLightExists($docId)) {
                return back()->with('error', __('daily_lights.date_exists'))->withInput();
            }

            // If a soft-deleted record exists for this date, clean up old R2 files & translations
            $oldData = $this->firestore->getDailyLight($docId);
            if ($oldData) {
                foreach ($oldData['translations'] ?? [] as $langFields) {
                    $this->deleteR2FilesForTranslation($langFields);
                }
                $this->firestore->deleteAllTranslations($docId);
            }

            // Convert Brazil admin input → UTC for storage
            $utcDatetime = DateTimeService::brazilToUtc($publishDate, $publishTime ?: null);
            $status = DateTimeService::isUtcInThePast($utcDatetime) ? 'published' : 'scheduled';

            $mainFields = [
                'publishDateTimeUtc' => ['stringValue' => $utcDatetime],
                'status' => ['stringValue' => $status],
                'isDeleted' => ['booleanValue' => false],
                'isFeatured' => ['booleanValue' => true],
                'createdAt' => ['timestampValue' => now()->toIso8601String()],
            ];

            $translations = [];
            foreach ($enabledLangs as $lang) {
                $translations[$lang] = $this->buildTranslationFields($request, $lang, $publishDate);
            }

            $result = $this->firestore->saveDailyLight($docId, $mainFields, $translations);

            if ($result['success']) {
                return redirect()->route('daily-lights.index')->with('success', __('daily_lights.created_success'));
            }

            \Log::error('Daily Light create failed', ['error' => $result['error'] ?? 'Unknown']);
            return back()->with('error', __('daily_lights.failed_create'))->withInput();

        } catch (\Exception $e) {
            \Log::error('Daily Light store exception', ['message' => $e->getMessage()]);
            return back()->with('error', __('common.error') . ': ' . $e->getMessage())->withInput();
        }
    }

    /**
     * Step 1: Save main document (text only, no files). Returns JSON with docId.
     */
    public function storeMain(Request $request)
    {
        $enabledLangs = $this->getEnabledLanguages($request);
        $isEdit = $request->boolean('is_edit');
        $editId = $request->input('edit_id', '');

        $request->validate([
            'publishDate' => 'required|date',
            'publishTime' => 'nullable|date_format:H:i',
        ], [
            'publishDate.required' => __('common.required_field'),
        ]);

        $publishDate = (string) $request->input('publishDate');
        $publishTime = (string) ($request->input('publishTime') ?? '');

        // Validate times
        $brazilNow = $this->brazilNow();
        $tz = $brazilNow->timezone;
        $isToday = \Carbon\Carbon::parse($publishDate, $tz)->isToday();

        if ($isToday && $publishTime) {
            $parts = explode(':', $publishTime);
            $selectedMinutes = (int) $parts[0] * 60 + (int) ($parts[1] ?? 0);
            $nowMinutes = $brazilNow->hour * 60 + $brazilNow->minute;
            if ($selectedMinutes <= $nowMinutes) {
                return response()->json(['success' => false, 'message' => __('daily_lights.publish_time_must_be_future')], 422);
            }
        }

        try {
            $docId = \Carbon\Carbon::parse($publishDate)->format('d-m-Y');

            if ($isEdit) {
                // Edit mode: check existing data
                $existingData = $this->firestore->getDailyLight($editId);
                $existingUtc = $existingData['main']['publishDateTimeUtc']['stringValue'] ?? '';
                $existingDate = $existingData['main']['date']['stringValue'] ?? '';
                $existingTime = $existingData['main']['publishTime']['stringValue'] ?? '';

                $alreadyPassed = $existingUtc
                    ? DateTimeService::isUtcInThePast($existingUtc)
                    : $this->isPublishTimePassed($existingDate, $existingTime ?: null);

                if ($alreadyPassed) {
                    return response()->json(['success' => false, 'message' => __('daily_lights.cannot_edit_published')], 422);
                }

                $newDocId = $docId;
                $dateChanged = ($newDocId !== $editId);

                if ($dateChanged && $this->firestore->dailyLightExists($newDocId)) {
                    return response()->json(['success' => false, 'message' => __('daily_lights.date_exists')], 422);
                }

                // Convert Brazil admin input → UTC for storage
                $newUtcDatetime = DateTimeService::brazilToUtc($publishDate, $publishTime ?: null);
                $status = DateTimeService::isUtcInThePast($newUtcDatetime) ? 'published' : 'scheduled';

                $mainFields = [
                    'publishDateTimeUtc' => ['stringValue' => $newUtcDatetime],
                    'status' => ['stringValue' => $status],
                    'isFeatured' => ['booleanValue' => $request->boolean('is_feature')],
                    'createdAt' => $existingData['main']['createdAt'] ?? ['timestampValue' => now()->toIso8601String()],
                ];

                $result = $this->firestore->saveDailyLight($newDocId, $mainFields, []);

                if (!$result['success']) {
                    return response()->json(['success' => false, 'message' => __('daily_lights.failed_update')], 500);
                }

                // Delete translations for languages that were disabled
                $existingTranslations = $existingData['translations'] ?? [];
                $allLangs = ['pt', 'en', 'es'];
                foreach ($allLangs as $lang) {
                    if (!in_array($lang, $enabledLangs) && isset($existingTranslations[$lang])) {
                        $this->deleteR2FilesForTranslation($existingTranslations[$lang]);
                        $this->firestore->deleteDailyLightTranslation($dateChanged ? $editId : $newDocId, $lang);
                    }
                }

                // If date changed, delete old document
                if ($dateChanged) {
                    $this->firestore->deleteDailyLight($editId);
                }

                return response()->json(['success' => true, 'docId' => $newDocId, 'enabledLangs' => $enabledLangs]);
            } else {
                // Create mode
                if ($this->firestore->dailyLightExists($docId)) {
                    return response()->json(['success' => false, 'message' => __('daily_lights.date_exists')], 422);
                }

                // Clean up soft-deleted record if exists
                $oldData = $this->firestore->getDailyLight($docId);
                if ($oldData) {
                    foreach ($oldData['translations'] ?? [] as $langFields) {
                        $this->deleteR2FilesForTranslation($langFields);
                    }
                    $this->firestore->deleteAllTranslations($docId);
                }

                // Convert Brazil admin input → UTC for storage
                $newUtcDatetime = DateTimeService::brazilToUtc($publishDate, $publishTime ?: null);
                $status = DateTimeService::isUtcInThePast($newUtcDatetime) ? 'published' : 'scheduled';

                $mainFields = [
                    'publishDateTimeUtc' => ['stringValue' => $newUtcDatetime],
                    'status' => ['stringValue' => $status],
                    'isDeleted' => ['booleanValue' => false],
                    'isFeatured' => ['booleanValue' => $request->boolean('is_feature')],
                    'createdAt' => ['timestampValue' => now()->toIso8601String()],
                ];

                $result = $this->firestore->saveDailyLight($docId, $mainFields, []);

                if (!$result['success']) {
                    return response()->json(['success' => false, 'message' => __('daily_lights.failed_create')], 500);
                }

                return response()->json(['success' => true, 'docId' => $docId, 'enabledLangs' => $enabledLangs]);
            }
        } catch (\Exception $e) {
            \Log::error('Daily Light storeMain exception', ['message' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => __('common.error') . ': ' . $e->getMessage()], 500);
        }
    }

    /**
     * Step 2a: Upload a single file (image or audio). Returns JSON with storageKey.
     * Each file is uploaded individually to stay within nginx body size limits.
     */
    // public function uploadFile(Request $request, string $id)
    // {
    //     ini_set('max_execution_time', 120);
    //     ini_set('memory_limit', '256M');
    //     set_time_limit(120);

    //     $lang = $request->input('lang');
    //     $step = (int) $request->input('step');
    //     $type = $request->input('type'); // image, bgImage, audio

    //     if (!in_array($lang, ['pt', 'en', 'es']) || $step < 1 || $step > 5 || !in_array($type, ['image', 'bgImage', 'audio'])) {
    //         return response()->json(['success' => false, 'message' => 'Invalid parameters.'], 422);
    //     }

    //     if (!$request->hasFile('file')) {
    //         return response()->json(['success' => false, 'message' => 'No file provided.'], 422);
    //     }

    //     $file = $request->file('file');

    //     // Validate based on type
    //     if ($type === 'audio') {
    //         $request->validate(['file' => 'required|file|mimes:mp3,wav,mpeg|max:51200']);
    //     } else {
    //         $request->validate(['file' => 'required|image|max:5120']);
    //     }

    //     try {
    //         // Derive Brazil publish date for R2 folder path
    //         $mainData = $this->firestore->getDailyLight($id);
    //         $utcDt = $mainData['main']['publishDateTimeUtc']['stringValue'] ?? '';
    //         $publishDate = $utcDt
    //             ? DateTimeService::utcToBrazil($utcDt)->format('Y-m-d')
    //             : ($mainData['main']['date']['stringValue'] ?? $id);

    //         $uploaded = $this->fileStorage->uploadDailyLightFile($file, $publishDate, $lang, $step);

    //         $result = ['success' => true, 'storageKey' => $uploaded['storage_key']];

    //         // Include audio duration for audio files
    //         if ($type === 'audio') {
    //             $result['audioDuration'] = $this->getAudioDuration($file->getPathname());
    //         }

    //         return response()->json($result);
    //     } catch (\Exception $e) {
    //         \Log::error('Daily Light uploadFile exception', ['lang' => $lang, 'step' => $step, 'type' => $type, 'error' => $e->getMessage()]);
    //         return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
    //     }
    // }

    public function uploadFile(Request $request, string $id)
    {
        $request->validate([
            'file_name' => 'required',
            'file_type' => 'required',
            'lang' => 'required|in:pt,en,es',
            'step' => 'required|integer|min:1|max:5',
            'type' => 'required|in:image,bgImage,audio',
        ]);

        $lang = $request->lang;
        $step = $request->step;
        $type = $request->type;

        $fileName = time() . '_' . preg_replace('/\s+/', '_', $request->file_name);

        try {

            // Get publish date
            $mainData = $this->firestore->getDailyLight($id);

            $utcDt = $mainData['main']['publishDateTimeUtc']['stringValue'] ?? '';

            $publishDate = $utcDt
                ? DateTimeService::utcToBrazil($utcDt)->format('Y-m-d')
                : ($mainData['main']['date']['stringValue'] ?? $id);

            // Generate storage path
            $key = "daily-light/{$publishDate}/{$lang}/step{$step}/{$type}/{$fileName}";

            $s3 = new S3Client([
                'version' => 'latest',
                'region' => env('R2_REGION'),
                'endpoint' => env('R2_ENDPOINT'),
                'credentials' => [
                    'key' => env('R2_ACCESS_KEY_ID'),
                    'secret' => env('R2_SECRET_ACCESS_KEY'),
                ],
            ]);

            $command = $s3->getCommand('PutObject', [
                'Bucket' => env('R2_BUCKET'),
                'Key' => $key,
                'ContentType' => $request->file_type,
            ]);

            $signedUrl = $s3->createPresignedRequest($command, '+20 minutes');

            return response()->json([
                'success' => true,
                'upload_url' => (string) $signedUrl->getUri(),
                'storage_key' => $key,
            ]);

        } catch (\Exception $e) {

            \Log::error('Daily Light uploadFile exception', [
                'lang' => $lang,
                'step' => $step,
                'type' => $type,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Step 2b: Save one language's translation (text + pre-uploaded file keys). Called per-language via AJAX.
     */
    public function storeLang(Request $request, string $id)
    {
        $lang = $request->input('lang');
        if (!in_array($lang, ['pt', 'en', 'es'])) {
            return response()->json(['success' => false, 'message' => 'Invalid language.'], 422);
        }

        $isEdit = $request->boolean('is_edit');

        // Validate text fields only (no files — they were uploaded separately via uploadFile)
        $rules = [
            "title_{$lang}" => 'required|string|max:255',
            "description_{$lang}" => 'required|string',
        ];
        $messages = [
            "title_{$lang}.required" => __('common.required_field'),
            "description_{$lang}.required" => __('common.required_field'),
        ];

        for ($i = 1; $i <= 4; $i++) {
            $rules["step_category_{$lang}_{$i}"] = 'required|string';
            $rules["section_title_{$lang}_{$i}"] = 'required|string|max:255';
            $rules["section_description_{$lang}_{$i}"] = 'required|string';
            $messages["step_category_{$lang}_{$i}.required"] = __('common.required_field');
            $messages["section_title_{$lang}_{$i}.required"] = __('common.required_field');
            $messages["section_description_{$lang}_{$i}.required"] = __('common.required_field');
        }

        // Step 5
        $rules["section_title_{$lang}_5"] = 'required|string|max:255';
        $rules["section_description_{$lang}_5"] = 'required|string';
        $messages["section_title_{$lang}_5.required"] = __('common.required_field');
        $messages["section_description_{$lang}_5.required"] = __('common.required_field');

        // On create, require that file keys are present for each step
        if (!$isEdit) {
            for ($i = 1; $i <= 5; $i++) {
                $rules["file_key_image_{$i}"] = 'required|string';
                $rules["file_key_audio_{$i}"] = 'required|string';
                $messages["file_key_image_{$i}.required"] = __('common.image_required');
                $messages["file_key_audio_{$i}.required"] = __('common.audio_required');
            }
            $rules["file_key_bgImage_5"] = 'required|string';
            $messages["file_key_bgImage_5.required"] = __('common.bg_image_required');
        }

        // Completion messages
        $rules["completion_title_{$lang}"]       = 'required|string|max:255';
        $rules["completion_description_{$lang}"] = 'required|string';
        $messages["completion_title_{$lang}.required"]       = __('common.required_field');
        $messages["completion_description_{$lang}.required"] = __('common.required_field');

        // Notification
        if ($request->boolean("send_notification_{$lang}")) {
            $rules["notif_title_{$lang}"] = 'required|string|max:255';
            $rules["notif_message_{$lang}"] = 'required|string|max:500';
            $rules["notif_time_{$lang}"] = 'required|string';
            $messages["notif_title_{$lang}.required"] = __('daily_light_categories.notif_title_required');
            $messages["notif_message_{$lang}.required"] = __('daily_light_categories.notif_message_required');
            $messages["notif_time_{$lang}.required"] = __('daily_light_categories.notif_time_required');
        }

        try {
            $request->validate($rules, $messages);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['success' => false, 'errors' => $e->errors()], 422);
        }

        try {
            $mainData = $this->firestore->getDailyLight($id);
            $existingTranslations = $mainData['translations'] ?? [];
            $existingLangFields = $existingTranslations[$lang] ?? [];

            // Derive Brazil publish date for notif UTC conversion
            $mainUtcDt = $mainData['main']['publishDateTimeUtc']['stringValue'] ?? '';
            $publishDate = $mainUtcDt
                ? DateTimeService::utcToBrazil($mainUtcDt)->format('Y-m-d')
                : ($mainData['main']['date']['stringValue'] ?? $id);

            $translationFields = $this->buildTranslationFieldsFromKeys($request, $lang, $existingLangFields, $publishDate);

            $result = $this->firestore->saveDailyLightTranslation($id, $lang, $translationFields);

            if (!$result['success']) {
                \Log::error("Daily Light storeLang failed", ['lang' => $lang, 'error' => $result['error'] ?? '']);
                return response()->json(['success' => false, 'message' => __('daily_lights.failed_create')], 500);
            }

            // Store category_ids at root level for category-in-use checks
            $categoryIds = [];
            for ($i = 1; $i <= 4; $i++) {
                $catId = (string) ($request->input("step_category_{$lang}_{$i}") ?? '');
                if ($catId) $categoryIds[] = $catId;
            }
            if (!empty($categoryIds)) {
                $this->firestore->patchDailyLightCategoryIds($id, $categoryIds);
            }

            // If notification is enabled, clear sent tracking so the updated time triggers a fresh send
            if ($request->boolean("send_notification_{$lang}")) {
                $this->firestore->clearDailySentGroup($id, "utc__{$lang}");
            }

            return response()->json(['success' => true, 'lang' => $lang]);
        } catch (\Exception $e) {
            \Log::error('Daily Light storeLang exception', ['lang' => $lang, 'message' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => __('common.error') . ': ' . $e->getMessage()], 500);
        }
    }

    public function edit(string $id)
    {
        $data = $this->firestore->getDailyLight($id);

        if (!$data) {
            return redirect()->route('daily-lights.index')->with('error', __('daily_lights.daily_light') . ' not found.');
        }

        $mainFields = $data['main'];
        $rawTranslations = $data['translations'];

        // Convert stored UTC → Brazil for form field pre-fill
        $publishDateTimeUtc = $mainFields['publishDateTimeUtc']['stringValue'] ?? '';
        if ($publishDateTimeUtc) {
            $brazilDt = DateTimeService::utcToBrazil($publishDateTimeUtc);
            $formDate = $brazilDt->format('Y-m-d'); // HTML date input expects YYYY-MM-DD
            $formTime = $brazilDt->format('H:i');
        } else {
            // Legacy record: date/publishTime already stored as Brazil local values
            $formDate = $mainFields['date']['stringValue'] ?? '';
            $formTime = $mainFields['publishTime']['stringValue'] ?? '';
        }

        $dailyLight = [
            'id' => $id,
            'date' => $formDate,
            'publishTime' => $formTime,
            'status' => $mainFields['status']['stringValue'] ?? 'draft',
            'isFeatured' => ($mainFields['isFeatured']['booleanValue'] ?? false) === true,
            'translations' => [],
        ];

        $allLanguages = ['pt', 'en', 'es'];
        foreach ($allLanguages as $lang) {
            if (!isset($rawTranslations[$lang])) {
                continue;
            }

            $langFields = $rawTranslations[$lang];
            $steps = [];

            if (isset($langFields['steps']['arrayValue']['values'])) {
                foreach ($langFields['steps']['arrayValue']['values'] as $stepVal) {
                    $s = $stepVal['mapValue']['fields'] ?? [];
                    $imageKey = $s['image']['stringValue'] ?? '';
                    $bgImageKey = $s['bg_image']['stringValue'] ?? '';
                    $audioKey = $s['audio']['stringValue'] ?? '';

                    $imageUrl = null;
                    if ($imageKey) {
                        try {
                                $imageUrl = Storage::disk('r2')->temporaryUrl($imageKey, now()->addMinutes(60));
                            } catch (\Exception $e) {}
                    }

                    $bgImageUrl = null;
                    if ($bgImageKey) {
                        try { $bgImageUrl = Storage::disk('r2')->temporaryUrl($bgImageKey, now()->addMinutes(60)); } catch (\Exception $e) {}
                    }

                    $audioUrl = null;
                    if ($audioKey) {
                        try { $audioUrl = Storage::disk('r2')->temporaryUrl($audioKey, now()->addMinutes(60)); } catch (\Exception $e) {}
                    }

                    $steps[] = [
                        'title' => $s['title']['stringValue'] ?? '',
                        'description' => $s['description']['stringValue'] ?? '',
                        'image' => $imageKey,
                        'image_url' => $imageUrl,
                        'bg_image' => $bgImageKey,
                        'bg_image_url' => $bgImageUrl,
                        'audio' => $audioKey,
                        'audio_url' => $audioUrl,
                        'index' => $s['index']['integerValue'] ?? 0,
                        'category_id' => $s['category_id']['stringValue'] ?? '',
                        'forSubscribeMember' => ($s['forSubscribeMember']['booleanValue'] ?? false) === true,
                    ];
                }
            }

            while (count($steps) < 5) {
                $steps[] = ['title' => '', 'description' => '', 'image' => '', 'image_url' => null, 'bg_image' => '', 'bg_image_url' => null, 'audio' => '', 'audio_url' => null, 'index' => count($steps) + 1, 'category_id' => '', 'forSubscribeMember' => false];
            }

            // Parse notification data — convert stored UTC time back to Brazil for display
            $notifData = [];
            if (isset($langFields['notification']['mapValue']['fields'])) {
                $nf = $langFields['notification']['mapValue']['fields'];
                $notifTimeUtc = $nf['timeUtc']['stringValue'] ?? '';
                $displayTime = '';
                if ($notifTimeUtc) {
                    $displayTime = DateTimeService::utcToBrazil($notifTimeUtc)->format('H:i');
                } else {
                    $displayTime = $nf['time']['stringValue'] ?? ''; // legacy fallback
                }
                $notifData = [
                    'enabled' => ($nf['enabled']['booleanValue'] ?? false) === true,
                    'title' => $nf['title']['stringValue'] ?? '',
                    'message' => $nf['message']['stringValue'] ?? '',
                    'time' => $displayTime,
                ];
            }

            $dailyLight['translations'][$lang] = [
                'title'               => $langFields['title']['stringValue'] ?? '',
                'description'         => $langFields['description']['stringValue'] ?? '',
                'steps'               => $steps,
                'notification'        => $notifData,
                'completionTitle'     => $langFields['completionTitle']['stringValue'] ?? '',
                'completionDescription' => $langFields['completionDescription']['stringValue'] ?? '',
            ];
        }

        $categories = $this->getDailyLightCategoryOptions();
        $isReadonly = $publishDateTimeUtc
            ? DateTimeService::isUtcInThePast($publishDateTimeUtc)
            : $this->isPublishTimePassed($dailyLight['date'], $dailyLight['publishTime'] ?: null);
        return view('pages.daily-lights.edit', compact('dailyLight', 'categories', 'isReadonly'));
    }

    public function update(Request $request, string $id)
    {
        ini_set('max_execution_time', 300);
        ini_set('memory_limit', '512M');
        set_time_limit(300);

        $enabledLangs = $this->getEnabledLanguages($request);

        $rules = [
            'publishDate' => 'required|date',
            'publishTime' => 'nullable|date_format:H:i',
        ];
        $messages = [];

        foreach ($enabledLangs as $lang) {
            $rules["title_{$lang}"] = 'required|string|max:255';
            $rules["description_{$lang}"] = 'required|string';
            $messages["title_{$lang}.required"] = __('common.required_field');
            $messages["description_{$lang}.required"] = __('common.required_field');

            // Steps 1-4: title + description + category required, files nullable (keep existing)
            for ($i = 1; $i <= 4; $i++) {
                $rules["step_category_{$lang}_{$i}"] = 'required|string';
                $rules["section_title_{$lang}_{$i}"] = 'required|string|max:255';
                $rules["section_description_{$lang}_{$i}"] = 'required|string';
                $rules["section_image_{$lang}_{$i}"] = 'nullable|image|max:5120';
                $rules["section_audio_{$lang}_{$i}"] = 'nullable|file|mimes:mp3,wav,mpeg|max:51200';
                $messages["step_category_{$lang}_{$i}.required"] = __('common.required_field');
                $messages["section_title_{$lang}_{$i}.required"] = __('common.required_field');
                $messages["section_description_{$lang}_{$i}.required"] = __('common.required_field');
            }

            // Step 5: required (text fields required, files nullable for edit — keeps existing)
            $rules["section_title_{$lang}_5"] = 'required|string|max:255';
            $rules["section_description_{$lang}_5"] = 'required|string';
            $rules["section_image_{$lang}_5"] = 'nullable|image|max:5120';
            $rules["section_bg_image_{$lang}_5"] = 'nullable|image|max:5120';
            $rules["section_audio_{$lang}_5"] = 'nullable|file|mimes:mp3,wav,mpeg|max:51200';
            $messages["section_title_{$lang}_5.required"] = __('common.required_field');
            $messages["section_description_{$lang}_5.required"] = __('common.required_field');
        }

        // Per-language notification validation (saved to DB only, not sent)
        foreach ($enabledLangs as $lang) {
            if ($request->boolean("send_notification_{$lang}")) {
                $rules["notif_title_{$lang}"] = 'required|string|max:255';
                $rules["notif_message_{$lang}"] = 'required|string|max:500';
                $rules["notif_time_{$lang}"] = 'required|string';
                $messages["notif_title_{$lang}.required"] = __('daily_light_categories.notif_title_required');
                $messages["notif_message_{$lang}.required"] = __('daily_light_categories.notif_message_required');
                $messages["notif_time_{$lang}.required"] = __('daily_light_categories.notif_time_required');
            }
        }

        $request->validate($rules, $messages);

        // Validate times
        $brazilNow = $this->brazilNow();
        $tz = $brazilNow->timezone;
        $updatePublishDate = (string) $request->input('publishDate');
        $updatePublishTime = (string) ($request->input('publishTime') ?? '');
        $isToday = \Carbon\Carbon::parse($updatePublishDate, $tz)->isToday();

        // Notification time must be after publish time
        if ($updatePublishTime) {
            foreach ($enabledLangs as $lang) {
                if ($request->boolean("send_notification_{$lang}")) {
                    $notifTime = $request->input("notif_time_{$lang}");
                    if ($notifTime && $notifTime <= $updatePublishTime) {
                        return back()->withErrors(["notif_time_{$lang}" => __('daily_light_categories.notif_time_before_publish')])->withInput();
                    }
                }
            }
        }

        if ($isToday) {
            $nowMinutes = $brazilNow->hour * 60 + $brazilNow->minute;

            // Publish time must be in the future
            if ($updatePublishTime) {
                $parts = explode(':', $updatePublishTime);
                $selectedMinutes = (int) $parts[0] * 60 + (int) ($parts[1] ?? 0);
                if ($selectedMinutes <= $nowMinutes) {
                    return back()->withErrors(['publishTime' => __('daily_lights.publish_time_must_be_future')])->withInput();
                }
            }

            foreach ($enabledLangs as $lang) {
                if ($request->boolean("send_notification_{$lang}")) {
                    $time = $request->input("notif_time_{$lang}");
                    if ($time) {
                        $parts = explode(':', $time);
                        $selectedMinutes = (int) $parts[0] * 60 + (int) ($parts[1] ?? 0);
                        if ($selectedMinutes <= $nowMinutes) {
                            return back()->withErrors(["notif_time_{$lang}" => __('daily_light_categories.notif_time_must_be_future')])->withInput();
                        }
                    }
                }
            }
        }

        try {
            // Get existing data for preserving file keys
            $existingData = $this->firestore->getDailyLight($id);
            $existingUtc = $existingData['main']['publishDateTimeUtc']['stringValue'] ?? '';
            $existingDate = $existingData['main']['date']['stringValue'] ?? '';
            $existingTime = $existingData['main']['publishTime']['stringValue'] ?? '';

            // GUARD: If publish datetime has already passed, reject all updates
            $alreadyPassed = $existingUtc
                ? DateTimeService::isUtcInThePast($existingUtc)
                : $this->isPublishTimePassed($existingDate, $existingTime ?: null);
            if ($alreadyPassed) {
                return back()->with('error', __('daily_lights.cannot_edit_published'));
            }

            $publishDate = (string) $request->input('publishDate');
            $publishTime = (string) ($request->input('publishTime') ?? '');

            // Convert Brazil admin input → UTC for storage
            $newUtcDatetime = DateTimeService::brazilToUtc($publishDate, $publishTime ?: null);
            $status = DateTimeService::isUtcInThePast($newUtcDatetime) ? 'published' : 'scheduled';
            $existingTranslations = $existingData['translations'] ?? [];

            // Determine new doc ID from the date
            $newDocId = \Carbon\Carbon::parse($publishDate)->format('d-m-Y');
            $dateChanged = ($newDocId !== $id);

            // If date changed, check new date doesn't already exist
            if ($dateChanged && $this->firestore->dailyLightExists($newDocId)) {
                return back()->with('error', __('daily_lights.date_exists'))->withInput();
            }

            // Collect all unique category IDs across all enabled languages' steps
            $categoryIds = [];
            foreach ($enabledLangs as $lang) {
                for ($i = 1; $i <= 4; $i++) {
                    $catId = (string) ($request->input("step_category_{$lang}_{$i}") ?? '');
                    if ($catId) $categoryIds[] = $catId;
                }
            }
            $categoryIds = array_values(array_unique(array_filter($categoryIds)));

            $mainFields = [
                'publishDateTimeUtc' => ['stringValue' => $newUtcDatetime],
                'status' => ['stringValue' => $status],
                'isFeatured' => $existingData['main']['isFeatured'] ?? ['booleanValue' => true],
                'createdAt' => $existingData['main']['createdAt'] ?? ['timestampValue' => now()->toIso8601String()],
                'category_ids' => ['arrayValue' => ['values' => array_map(fn($id) => ['stringValue' => $id], $categoryIds)]],
            ];

            // When date changes, new uploads go to new date folder; existing file keys stay as-is
            $translations = [];
            foreach ($enabledLangs as $lang) {
                $translations[$lang] = $this->buildTranslationFields($request, $lang, $publishDate, $existingTranslations[$lang] ?? []);
            }

            $result = $this->firestore->saveDailyLight($newDocId, $mainFields, $translations);

            if ($result['success']) {
                // Delete translations + R2 files for languages that were disabled
                $allLangs = ['pt', 'en', 'es'];
                foreach ($allLangs as $lang) {
                    if (!in_array($lang, $enabledLangs) && isset($existingTranslations[$lang])) {
                        $this->deleteR2FilesForTranslation($existingTranslations[$lang]);
                        $this->firestore->deleteDailyLightTranslation($dateChanged ? $id : $newDocId, $lang);
                    }
                }

                // If date changed, delete the old document entirely
                if ($dateChanged) {
                    $this->firestore->deleteDailyLight($id);
                }

                // Clear sent tracking for languages with updated notifications so cron re-fires
                foreach ($enabledLangs as $lang) {
                    if ($request->boolean("send_notification_{$lang}")) {
                        $this->firestore->clearDailySentGroup($newDocId, "utc__{$lang}");
                    }
                }

                return redirect()->route('daily-lights.index')->with('success', __('daily_lights.updated_success'));
            }

            \Log::error('Daily Light update failed', ['error' => $result['error'] ?? 'Unknown']);
            return back()->with('error', __('daily_lights.failed_update'))->withInput();

        } catch (\Exception $e) {
            \Log::error('Daily Light update exception', ['message' => $e->getMessage()]);
            return back()->with('error', __('common.error') . ': ' . $e->getMessage())->withInput();
        }
    }

    public function destroy(string $id)
    {
        try {
            // Fetch existing data to clean up R2 files
            $data = $this->firestore->getDailyLight($id);
            if ($data) {
                foreach ($data['translations'] ?? [] as $langFields) {
                    $this->deleteR2FilesForTranslation($langFields);
                }
            }

            // Hard delete from Firestore (document + all translations)
            $success = $this->firestore->deleteDailyLight($id);

            if ($success) {
                // Delete sent-tracking so notifications can fire again if re-created
                $this->firestore->deleteDailySentTracking($id);

                // Background: delete this date's dailyProgress entry from all users
                // Convert DD-MM-YYYY (daily light ID) → YYYY-MM-DD (dailyProgress key)
                try {
                    $progressDate = \Carbon\Carbon::createFromFormat('d-m-Y', $id)->format('Y-m-d');
                    $firestore = $this->firestore;
                    dispatch(function () use ($progressDate, $firestore) {
                        $firestore->deleteAllUsersDailyProgress($progressDate);
                    })->afterResponse();
                } catch (\Exception $e) {
                    // Non-critical: daily light deleted, progress cleanup will be skipped
                    \Log::warning('Could not schedule dailyProgress cleanup', ['id' => $id, 'error' => $e->getMessage()]);
                }

                return redirect()->route('daily-lights.index')->with('success', __('daily_lights.deleted_success'));
            }

            return back()->with('error', __('daily_lights.failed_delete'));
        } catch (\Exception $e) {
            \Log::error('Daily Light delete exception', ['message' => $e->getMessage()]);
            return back()->with('error', __('common.error') . ': ' . $e->getMessage());
        }
    }

    public function toggleFeatured(Request $request, string $id)
    {
        $isFeatured = $request->boolean('isFeatured');
        $success = $this->firestore->toggleDailyLightFeatured($id, $isFeatured);

        return response()->json(['success' => $success]);
    }

    public function checkDate(Request $request)
    {
        $date = $request->input('date');
        $excludeId = $request->input('exclude_id');

        if (!$date) {
            return response()->json(['exists' => false]);
        }

        try {
            $docId = \Carbon\Carbon::parse($date)->format('d-m-Y');
        } catch (\Exception $e) {
            return response()->json(['exists' => false]);
        }

        // If editing the same document, skip
        if ($excludeId && $excludeId === $docId) {
            return response()->json(['exists' => false]);
        }

        $exists = $this->firestore->dailyLightExists($docId);
        return response()->json(['exists' => $exists]);
    }

    // ---- Helpers ----

    private function deleteR2FilesForTranslation(array $langFields): void
    {
        $steps = $langFields['steps']['arrayValue']['values'] ?? [];
        foreach ($steps as $stepVal) {
            $s = $stepVal['mapValue']['fields'] ?? [];
            $imageKey = $s['image']['stringValue'] ?? '';
            $bgImageKey = $s['bg_image']['stringValue'] ?? '';
            $audioKey = $s['audio']['stringValue'] ?? '';

            if ($imageKey) {
                try { Storage::disk('r2')->delete($imageKey); } catch (\Exception $e) {}
            }
            if ($bgImageKey) {
                try { Storage::disk('r2')->delete($bgImageKey); } catch (\Exception $e) {}
            }
            if ($audioKey) {
                try { Storage::disk('r2')->delete($audioKey); } catch (\Exception $e) {}
            }
        }
    }

    private function getEnabledLanguages(Request $request): array
    {
        $langs = ['pt']; // PT always required
        if ($request->boolean('lang_enabled_en')) $langs[] = 'en';
        if ($request->boolean('lang_enabled_es')) $langs[] = 'es';
        return $langs;
    }

    private function buildTranslationFields(Request $request, string $lang, string $date, array $existingLangFields = []): array
    {
        $title = (string) ($request->input("title_{$lang}") ?? '');
        $description = (string) ($request->input("description_{$lang}") ?? '');

        // Parse existing steps for preserving file keys
        $existingSteps = [];
        if (isset($existingLangFields['steps']['arrayValue']['values'])) {
            foreach ($existingLangFields['steps']['arrayValue']['values'] as $sv) {
                $sf = $sv['mapValue']['fields'] ?? [];
                $existingSteps[] = [
                    'image' => $sf['image']['stringValue'] ?? '',
                    'bg_image' => $sf['bg_image']['stringValue'] ?? '',
                    'audio' => $sf['audio']['stringValue'] ?? '',
                    'audioDuration' => $sf['audioDuration']['stringValue'] ?? '',
                ];
            }
        }

        $stepsArray = [];
        for ($i = 1; $i <= 5; $i++) {
            $stepTitle = (string) ($request->input("section_title_{$lang}_{$i}") ?? '');
            $stepDescription = (string) ($request->input("section_description_{$lang}_{$i}") ?? '');

            $existingStep = $existingSteps[$i - 1] ?? [];

            $imageKey = (string) ($existingStep['image'] ?? '');
            if ($request->hasFile("section_image_{$lang}_{$i}")) {
                $uploaded = $this->fileStorage->uploadDailyLightFile(
                    $request->file("section_image_{$lang}_{$i}"), $date, $lang, $i
                );
                $imageKey = $uploaded['storage_key'];
            }

            // Background image (step 5 only)
            $bgImageKey = (string) ($existingStep['bg_image'] ?? '');
            if ($i === 5 && $request->hasFile("section_bg_image_{$lang}_5")) {
                $uploaded = $this->fileStorage->uploadDailyLightFile(
                    $request->file("section_bg_image_{$lang}_5"), $date, $lang, $i
                );
                $bgImageKey = $uploaded['storage_key'];
            }

            $audioKey = (string) ($existingStep['audio'] ?? '');
            $audioDuration = (string) ($existingStep['audioDuration'] ?? '');
            if ($request->hasFile("section_audio_{$lang}_{$i}")) {
                $audioFile = $request->file("section_audio_{$lang}_{$i}");
                $audioDuration = $this->getAudioDuration($audioFile->getPathname());
                $uploaded = $this->fileStorage->uploadDailyLightFile(
                    $audioFile, $date, $lang, $i
                );
                $audioKey = $uploaded['storage_key'];
            }

            $stepFields = [
                'title' => ['stringValue' => $stepTitle],
                'description' => ['stringValue' => $stepDescription],
                'image' => ['stringValue' => $imageKey],
                'bg_image' => ['stringValue' => $bgImageKey],
                'audio' => ['stringValue' => $audioKey],
                'audioDuration' => ['stringValue' => $audioDuration],
                'index' => ['integerValue' => (string) $i],
            ];

            // Save category_id inside steps 1-4
            if ($i <= 4) {
                $stepFields['category_id'] = ['stringValue' => (string) ($request->input("step_category_{$lang}_{$i}") ?? '')];
            }

            if ($i === 5) {
                $stepFields['forSubscribeMember'] = ['booleanValue' => $request->boolean("forSubscribeMember_{$lang}")];
            }

            $stepsArray[] = [
                'mapValue' => [
                    'fields' => $stepFields
                ]
            ];
        }

        $translationFields = [
            'title' => ['stringValue' => $title],
            'description' => ['stringValue' => $description],
            'steps' => [
                'arrayValue' => [
                    'values' => $stepsArray
                ]
            ]
        ];

        // Save notification data per-language (DB only, not sent)
        // Admin enters Brazil time → convert to UTC for storage
        if ($request->boolean("send_notification_{$lang}")) {
            $brazilNotifTime = (string) ($request->input("notif_time_{$lang}") ?? '');
            $notifTimeUtc = $brazilNotifTime ? DateTimeService::brazilToUtc($date, $brazilNotifTime) : '';
            $translationFields['notification'] = [
                'mapValue' => [
                    'fields' => [
                        'enabled' => ['booleanValue' => true],
                        'title' => ['stringValue' => (string) ($request->input("notif_title_{$lang}") ?? '')],
                        'message' => ['stringValue' => (string) ($request->input("notif_message_{$lang}") ?? '')],
                        'timeUtc' => ['stringValue' => $notifTimeUtc],
                    ]
                ]
            ];
        } else {
            $translationFields['notification'] = [
                'mapValue' => [
                    'fields' => [
                        'enabled' => ['booleanValue' => false],
                        'title' => ['stringValue' => ''],
                        'message' => ['stringValue' => ''],
                        'timeUtc' => ['stringValue' => ''],
                    ]
                ]
            ];
        }

        return $translationFields;
    }

    /**
     * Build translation fields using pre-uploaded file keys (no file uploads in this request).
     */
    private function buildTranslationFieldsFromKeys(Request $request, string $lang, array $existingLangFields = [], string $publishDate = ''): array
    {
        $title = (string) ($request->input("title_{$lang}") ?? '');
        $description = (string) ($request->input("description_{$lang}") ?? '');

        // Parse existing steps for preserving file keys
        $existingSteps = [];
        if (isset($existingLangFields['steps']['arrayValue']['values'])) {
            foreach ($existingLangFields['steps']['arrayValue']['values'] as $sv) {
                $sf = $sv['mapValue']['fields'] ?? [];
                $existingSteps[] = [
                    'image' => $sf['image']['stringValue'] ?? '',
                    'bg_image' => $sf['bg_image']['stringValue'] ?? '',
                    'audio' => $sf['audio']['stringValue'] ?? '',
                    'audioDuration' => $sf['audioDuration']['stringValue'] ?? '',
                ];
            }
        }

        $stepsArray = [];
        for ($i = 1; $i <= 5; $i++) {
            $stepTitle = (string) ($request->input("section_title_{$lang}_{$i}") ?? '');
            $stepDescription = (string) ($request->input("section_description_{$lang}_{$i}") ?? '');
            $existingStep = $existingSteps[$i - 1] ?? [];

            // Use pre-uploaded key if provided, otherwise keep existing
            $imageKey = $request->input("file_key_image_{$i}") ?: ($existingStep['image'] ?? '');
            $bgImageKey = ($i === 5) ? ($request->input("file_key_bgImage_5") ?: ($existingStep['bg_image'] ?? '')) : ($existingStep['bg_image'] ?? '');
            $audioKey = $request->input("file_key_audio_{$i}") ?: ($existingStep['audio'] ?? '');
            $audioDuration = $request->input("file_duration_audio_{$i}") ?: ($existingStep['audioDuration'] ?? '');

            $stepFields = [
                'title' => ['stringValue' => $stepTitle],
                'description' => ['stringValue' => $stepDescription],
                'image' => ['stringValue' => $imageKey],
                'bg_image' => ['stringValue' => $bgImageKey],
                'audio' => ['stringValue' => $audioKey],
                'audioDuration' => ['stringValue' => $audioDuration],
                'index' => ['integerValue' => (string) $i],
            ];

            if ($i <= 4) {
                $stepFields['category_id'] = ['stringValue' => (string) ($request->input("step_category_{$lang}_{$i}") ?? '')];
            }
            if ($i === 5) {
                $stepFields['forSubscribeMember'] = ['booleanValue' => $request->boolean("forSubscribeMember_{$lang}")];
            }

            $stepsArray[] = ['mapValue' => ['fields' => $stepFields]];
        }

        $translationFields = [
            'title'               => ['stringValue' => $title],
            'description'         => ['stringValue' => $description],
            'steps'               => ['arrayValue' => ['values' => $stepsArray]],
            'completionTitle'     => ['stringValue' => (string) ($request->input("completion_title_{$lang}") ?? '')],
            'completionDescription' => ['stringValue' => (string) ($request->input("completion_description_{$lang}") ?? '')],
        ];

        if ($request->boolean("send_notification_{$lang}")) {
            $brazilNotifTime = (string) ($request->input("notif_time_{$lang}") ?? '');
            $notifTimeUtc = ($brazilNotifTime && $publishDate) ? DateTimeService::brazilToUtc($publishDate, $brazilNotifTime) : '';
            $translationFields['notification'] = [
                'mapValue' => [
                    'fields' => [
                        'enabled' => ['booleanValue' => true],
                        'title' => ['stringValue' => (string) ($request->input("notif_title_{$lang}") ?? '')],
                        'message' => ['stringValue' => (string) ($request->input("notif_message_{$lang}") ?? '')],
                        'timeUtc' => ['stringValue' => $notifTimeUtc],
                    ]
                ]
            ];
        } else {
            $translationFields['notification'] = [
                'mapValue' => [
                    'fields' => [
                        'enabled' => ['booleanValue' => false],
                        'title' => ['stringValue' => ''],
                        'message' => ['stringValue' => ''],
                        'timeUtc' => ['stringValue' => ''],
                    ]
                ]
            ];
        }

        return $translationFields;
    }

    private function getAudioDuration(string $filePath): string
    {
        try {
            $getID3 = new \getID3();
            $fileInfo = $getID3->analyze($filePath);
            $seconds = (int) floor($fileInfo['playtime_seconds'] ?? 0);
            $minutes = intdiv($seconds, 60);
            $secs = $seconds % 60;
            return sprintf('%02d:%02d', $minutes, $secs);
        } catch (\Exception $e) {
            \Log::error('getAudioDuration failed', ['path' => $filePath, 'error' => $e->getMessage()]);
            return '00:00';
        }
    }

    /**
     * Toggle daily morning notification setting.
     */
    public function toggleDailyNotification(Request $request)
    {   
        $enabled = $request->boolean('enabled');
        $title = trim((string) $request->input('title', ''));
        $body = trim((string) $request->input('body', ''));

        // Validate title & body only when saving from modal
        if ($request->boolean('save_content') && ($title === '' || $body === '')) {
            return response()->json(['success' => false, 'error' => 'Title and message are required.'], 422);
        }

        // Only update title/body if provided (toggle-only won't overwrite them)
        $fields = ['enabled' => ['booleanValue' => $enabled]];
        if ($title !== '') $fields['title'] = ['stringValue' => $title];
        if ($body !== '') $fields['body'] = ['stringValue' => $body];

        // Store admin ID so cron can use it as senderId
        $adminId = session('admin.id', '');
        if ($adminId !== '') {
            $fields['adminId'] = ['stringValue' => $adminId];
        }

        $this->firestore->setSetting('daily_notification', $fields);

        return response()->json(['success' => true, 'enabled' => $enabled]);
    }

    /**
     * Cron endpoint: send daily notification per timezone and language.
     * Called by external cron every 2 minutes.
     */
    public function cronDailyNotification()
    {
        // Build a dedicated per-date log file directly via Monolog (no config cache dependency)
        $today    = now('UTC')->format('Y-m-d');
        $logPath  = storage_path("logs/cron-{$today}.log");
        $monolog  = new \Monolog\Logger('cron');
        $monolog->pushHandler(new \Monolog\Handler\StreamHandler($logPath, \Monolog\Level::Debug));
        $cronLog  = new \Illuminate\Log\Logger($monolog);

        $cronLog->info('Cron daily notification started', ['utc_now' => now('UTC')->toIso8601String()]);

        // Get sender admin ID from settings (used as senderId for push notifications)
        $setting = $this->firestore->getSetting('daily_notification');

        // Use Brazil timezone for Daily Light document ID (matches how they are created)
        $localTimezone = env('APP_LOCAL_TIMEZONE', 'America/Sao_Paulo');
        $todayId = now($localTimezone)->format('d-m-Y');
        $cronLog->info('Cron looking for daily light', ['doc_id' => $todayId]);

        $data = $this->firestore->getDailyLight($todayId);

        if (!$data) {
            $cronLog->info('Cron skipped: no daily light found', ['doc_id' => $todayId]);
            return response()->json(['status' => 'skipped', 'reason' => 'No Daily Light found for today (' . $todayId . ').']);
        }

        $status = $data['main']['status']['stringValue'] ?? '';

        // Auto-publish if scheduled and publish time has passed (don't rely on index() being visited)
        if ($status === 'scheduled') {
            $publishUtc = $data['main']['publishDateTimeUtc']['stringValue'] ?? '';
            $legacyDate = $data['main']['date']['stringValue'] ?? '';
            $legacyTime = $data['main']['publishTime']['stringValue'] ?? '';
            $publishPassed = $publishUtc
                ? DateTimeService::isUtcInThePast($publishUtc)
                : $this->isPublishTimePassed($legacyDate, $legacyTime ?: null);
            if ($publishPassed) {
                $cronLog->info('Cron auto-publishing daily light', ['doc_id' => $todayId, 'publishDateTimeUtc' => $publishUtc]);
                $this->firestore->updateDailyLightStatus($todayId, 'published');
                $status = 'published';
            } else {
                $cronLog->info('Cron skipped: scheduled but publish time not yet passed', [
                    'doc_id' => $todayId,
                    'publishDateTimeUtc' => $publishUtc,
                ]);
            }
        }

        if ($status !== 'published') {
            $cronLog->info('Cron skipped: status is not published', ['doc_id' => $todayId, 'status' => $status]);
            return response()->json(['status' => 'skipped', 'reason' => 'Today\'s Daily Light is not published.']);
        }

        $senderId = $setting['adminId']['stringValue'] ?? '';

        // Collect per-language notification configs from translations
        $langNotifications = [];
        foreach ($data['translations'] ?? [] as $lang => $langFields) {
            if (!isset($langFields['notification']['mapValue']['fields'])) {
                $cronLog->info("Cron [{$lang}]: no notification map found, skipping");
                continue;
            }
            $nf = $langFields['notification']['mapValue']['fields'];
            if (($nf['enabled']['booleanValue'] ?? false) !== true) {
                $cronLog->info("Cron [{$lang}]: notification disabled, skipping");
                continue;
            }
            $title = $nf['title']['stringValue'] ?? '';
            $message = $nf['message']['stringValue'] ?? '';
            $timeUtc = $nf['timeUtc']['stringValue'] ?? '';
            if ($title === '' || $message === '' || $timeUtc === '') {
                $cronLog->info("Cron [{$lang}]: missing title/message/timeUtc, skipping", [
                    'title' => $title, 'message' => $message, 'timeUtc' => $timeUtc,
                ]);
                continue;
            }
            $langNotifications[$lang] = [
                'title' => $title,
                'message' => $message,
                'timeUtc' => $timeUtc,
            ];
        }

        if (empty($langNotifications)) {
            $cronLog->info('Cron skipped: no enabled language notifications found', ['doc_id' => $todayId]);
            return response()->json(['status' => 'skipped', 'reason' => 'No enabled language notifications found.']);
        }

        // Get sent tracking for today
        $sentTracking = $this->firestore->getDailySentTracking($todayId);

        // Fetch all active users with preferences
        $allUsers = $this->notification->getAllActiveUsersWithPreferences();
        $cronLog->info('Cron fetched users', ['total_users' => count($allUsers)]);

        $nowUtc = \Carbon\Carbon::now('UTC');
        $results = [];

        foreach ($langNotifications as $lang => $notif) {
            // UTC-based: compare current UTC time against stored UTC notification datetime
            $groupKey = "utc__{$lang}";

            // Skip if already sent for this language today
            if (isset($sentTracking[$groupKey])) {
                $cronLog->info("Cron [{$lang}]: already sent today, skipping", ['group' => $groupKey]);
                $results[$lang] = ['status' => 'already_sent', 'group' => $groupKey];
                continue;
            }

            // Fire if scheduled time has passed and within 30-minute catch-up window
            // (wider window prevents missed notifications when cron has temporary failures)
            $notifMoment = \Carbon\Carbon::parse($notif['timeUtc']);
            $isPast = $notifMoment->lte($nowUtc);
            $diffMinutes = $nowUtc->diffInMinutes($notifMoment);
            $cronLog->info("Cron [{$lang}]: time check", [
                'now_utc'      => $nowUtc->toIso8601String(),
                'notif_utc'    => $notif['timeUtc'],
                'is_past'      => $isPast,
                'diff_minutes' => $diffMinutes,
            ]);
            if (!$isPast) {
                $cronLog->info("Cron [{$lang}]: notification time not yet reached, skipping", ['diff_minutes' => $diffMinutes]);
                continue;
            }
            if ($diffMinutes > 30) {
                $cronLog->info("Cron [{$lang}]: outside 30-min catch-up window, skipping", ['diff_minutes' => $diffMinutes]);
                continue;
            }

            // Collect all users who have this language active
            // timezone check removed — UTC-based sending does not need user timezone
            $users = [];
            foreach ($allUsers as $user) {
                if (empty($user['activeLanguage'])) continue;
                if ($user['activeLanguage'] !== $lang) continue;
                $users[] = $user;
            }

            $cronLog->info("Cron [{$lang}]: matched users", ['count' => count($users)]);

            if (empty($users)) {
                $cronLog->info("Cron [{$lang}]: no users with this activeLanguage, skipping");
                $results[$lang] = ['status' => 'no_users', 'group' => $groupKey];
                continue;
            }

            try {
                $result = $this->notification->sendToSpecificUsers(
                    array_values($users),
                    $notif['title'],
                    $notif['message'],
                    $senderId,
                    'daily_light'
                );

                // Mark as sent to prevent duplicates
                $this->firestore->markDailySentGroup($todayId, $groupKey);

                $cronLog->info("Cron [{$lang}]: notification sent successfully", [
                    'group'    => $groupKey,
                    'utc_time' => $nowUtc->toIso8601String(),
                    'users'    => count($users),
                    'result'   => $result,
                ]);
                $results[$lang] = [
                    'status' => 'sent',
                    'group' => $groupKey,
                    'utc_time' => $nowUtc->format('H:i'),
                    'result' => $result,
                ];
            } catch (\Exception $e) {
                $cronLog->error("Cron [{$lang}]: notification FAILED", [
                    'group' => $groupKey,
                    'error' => $e->getMessage(),
                ]);
                $results[$lang] = ['status' => 'error', 'group' => $groupKey, 'error' => $e->getMessage()];
            }
        }

        $cronLog->info('Cron finished', ['date' => $todayId, 'results' => $results]);
        return response()->json(['status' => 'ok', 'date' => $todayId, 'results' => $results]);
    }

    /**
     * Build a Firestore-safe group key (e.g. "pt" + "America/Sao_Paulo" → "pt__America__Sao_Paulo").
     */
    private function buildSentGroupKey(string $lang, string $timezone): string
    {
        $safeTimezone = str_replace('/', '__', $timezone);
        return "{$lang}__{$safeTimezone}";
    }

    /**
     * Get current datetime in Brazil timezone.
     */
    private function brazilNow(): \Carbon\Carbon
    {
        return \Carbon\Carbon::now(env('APP_LOCAL_TIMEZONE', 'America/Sao_Paulo'));
    }

    /**
     * Check if a publish date+time has passed in Brazil timezone.
     */
    private function isPublishTimePassed(string $date, ?string $time): bool
    {
        $tz = env('APP_LOCAL_TIMEZONE', 'America/Sao_Paulo');
        $publishMoment = \Carbon\Carbon::parse($date, $tz)->startOfDay();
        if ($time) {
            $parts = explode(':', $time);
            $publishMoment->setHour((int) $parts[0])->setMinute((int) ($parts[1] ?? 0));
        }
        return $publishMoment->lte(\Carbon\Carbon::now($tz));
    }

    /**
     * Parse Daily Light dates stored in either `d-m-Y` or `Y-m-d`.
     */
    private function parseDailyLightDate(string $date, ?string $time = null): \Carbon\Carbon
    {
        $tz = env('APP_LOCAL_TIMEZONE', 'America/Sao_Paulo');
        $formats = $time ? ['d-m-Y H:i', 'Y-m-d H:i', 'd-m-Y', 'Y-m-d'] : ['d-m-Y', 'Y-m-d'];

        foreach ($formats as $format) {
            try {
                return \Carbon\Carbon::createFromFormat(
                    $format,
                    $time && str_contains($format, 'H:i') ? "{$date} {$time}" : $date,
                    $tz
                );
            } catch (\Exception $e) {}
        }

        return \Carbon\Carbon::parse($time ? "{$date} {$time}" : $date, $tz);
    }

    private function getDailyLightCategoryOptions(): array
    {
        $items = $this->firestore->getDailyLightCategories();
        $categories = [];
        foreach ($items as $id => $fields) {
            $iconValue = $fields['icon']['stringValue'] ?? '';
            $iconDropdownKey = $fields['iconDropdown']['stringValue'] ?? '';
            $iconImageKey = $fields['iconImage']['stringValue'] ?? '';

            // Dropdown icon: prefer pre-rendered PNG from R2
            $iconDropdownUrl = null;
            if ($iconDropdownKey) {
                try {
                    $iconDropdownUrl = Storage::disk('r2')->temporaryUrl($iconDropdownKey, now()->addMinutes(60));
                } catch (\Exception $e) {}
            }

            // Original icon URL (fallback for display)
            $iconUrl = null;
            if ($iconImageKey) {
                try {
                    $iconUrl = Storage::disk('r2')->temporaryUrl($iconImageKey, now()->addMinutes(60));
                } catch (\Exception $e) {}
            }
            if (!$iconUrl && $iconValue) {
                if (str_starts_with(trim($iconValue), '<')) {
                    $iconUrl = 'data:image/svg+xml;base64,' . base64_encode($iconValue);
                } else {
                    try {
                        $ext = strtolower(pathinfo($iconValue, PATHINFO_EXTENSION));
                        if ($ext === 'svg') {
                            $content = Storage::disk('r2')->get($iconValue);
                            $iconUrl = 'data:image/svg+xml;base64,' . base64_encode($content);
                        } else {
                            $iconUrl = Storage::disk('r2')->temporaryUrl($iconValue, now()->addMinutes(30));
                        }
                    } catch (\Exception $e) {}
                }
            }

            $categories[] = [
                'id' => $id,
                'title' => $fields['pt_title']['stringValue'] ?? '',
                'title_pt' => $fields['pt_title']['stringValue'] ?? '',
                'title_en' => $fields['en_title']['stringValue'] ?? '',
                'title_es' => $fields['es_title']['stringValue'] ?? '',
                'icon_url' => $iconUrl,
                'icon_dropdown_url' => $iconDropdownUrl,
            ];
        }
        usort($categories, fn($a, $b) => strcasecmp($a['title'], $b['title']));
        return $categories;
    }

    // ---- Comments ----

    public function commentsIndex()
    {
        $items = $this->firestore->getDailyLights();

        $dailyLights = [];
        foreach ($items as $id => $data) {
            $fields = $data['fields'];
            $rawDate = $fields['date']['stringValue'] ?? '';
            $rawTime = $fields['publishTime']['stringValue'] ?? '';
            $utcDatetime = $fields['publishDateTimeUtc']['stringValue'] ?? '';
            $sortTimestamp = 0;

            if ($utcDatetime) {
                $brazilDt = DateTimeService::utcToBrazil($utcDatetime);
                $displayDate = $brazilDt->format('d-m-Y');
                $sortTimestamp = $brazilDt->timestamp;
            } else {
                $displayDate = $rawDate;
                try {
                    $parsedDate = $this->parseDailyLightDate($rawDate, $rawTime ?: null);
                    $displayDate = $parsedDate->format('d-m-Y');
                    $sortTimestamp = $parsedDate->timestamp;
                } catch (\Exception $e) {}
            }

            $dailyLights[] = [
                'id'            => $id,
                'title'         => $data['ptTitle'] ?? '',
                'date'          => $displayDate,
                'status'        => $fields['status']['stringValue'] ?? 'draft',
                'sortTimestamp' => $sortTimestamp,
            ];
        }

        usort($dailyLights, fn($a, $b) => ($b['sortTimestamp'] ?? 0) <=> ($a['sortTimestamp'] ?? 0));

        return view('pages.comments.index', compact('dailyLights'));
    }

    public function comments(string $id)
    {
        $rawComments = $this->firestore->getComments($id);

        // Collect unique user IDs from both comments and their replies
        $commentUserIds = array_unique(array_filter(array_map(
            fn($f) => $f['userId']['stringValue'] ?? '',
            $rawComments
        )));

        $replyUserIds = [];
        foreach ($rawComments as $fields) {
            foreach ($fields['repliesList']['arrayValue']['values'] ?? [] as $rv) {
                $uid = $rv['mapValue']['fields']['userId']['stringValue'] ?? '';
                if ($uid) $replyUserIds[] = $uid;
            }
        }

        $allUserIds = array_unique(array_merge($commentUserIds, $replyUserIds));
        $userInfoMap = [];
        foreach ($allUserIds as $uid) {
            if (!$uid) continue;
            $userFields   = $this->firestore->getFirestoreUser($uid);
            $updatedAtRaw = $userFields['updatedAt']['timestampValue'] ?? '';
            $userTz       = $userFields['timezone']['stringValue'] ?? 'UTC';
            $updatedAtFmt = '';
            if ($updatedAtRaw) {
                try {
                    $updatedAtFmt = \Carbon\Carbon::parse($updatedAtRaw)
                        ->setTimezone($userTz)
                        ->format('d/m/Y H:i');
                } catch (\Exception $e) {
                    $updatedAtFmt = \Carbon\Carbon::parse($updatedAtRaw)->format('d/m/Y H:i');
                }
            }
            $userInfoMap[$uid] = [
                'isBlocked' => $userFields['isBlocked']['booleanValue'] ?? false,
                'email'     => $userFields['email']['stringValue'] ?? '',
                'updatedAt' => $updatedAtFmt,
                'timezone'  => $userTz,
            ];
        }

        $comments = [];
        foreach ($rawComments as $commentId => $fields) {
            $userId = $fields['userId']['stringValue'] ?? '';

            // Parse repliesList (Firestore arrayValue of mapValue items)
            $repliesRaw = $fields['repliesList']['arrayValue']['values'] ?? [];
            $repliesList = [];
            foreach ($repliesRaw as $rv) {
                $rf = $rv['mapValue']['fields'] ?? [];
                $rUserId = $rf['userId']['stringValue'] ?? '';
                $repliesList[] = [
                    'id'               => $rf['id']['stringValue'] ?? '',
                    'userId'           => $rUserId,
                    'userName'         => $rf['userName']['stringValue'] ?? '',
                    'userEmail'        => $userInfoMap[$rUserId]['email'] ?? '',
                    'userUpdatedAt'    => $userInfoMap[$rUserId]['updatedAt'] ?? '',
                    'userTimezone'     => $userInfoMap[$rUserId]['timezone'] ?? '',
                    'message'          => $rf['message']['stringValue'] ?? '',
                    'createdAt'        => $rf['createdAt']['timestampValue'] ?? '',
                    'isSubscribedUser' => $rf['isSubscribedUser']['booleanValue'] ?? false,
                    'likes'            => (int) ($rf['likes']['integerValue'] ?? 0),
                    'isBlocked'        => $userInfoMap[$rUserId]['isBlocked'] ?? false,
                    'isHidden'         => $rf['isHidden']['booleanValue'] ?? false,
                    'isSpam'           => $rf['isSpam']['booleanValue'] ?? false,
                    'isProhibitedWord' => $rf['isProhibitedWord']['booleanValue'] ?? false,
                    'isReported'       => $rf['isReported']['booleanValue'] ?? false,
                    'reportCount'      => count($rf['reportedBy']['arrayValue']['values'] ?? []),
                ];
            }

            $comments[] = [
                'id'               => $commentId,
                'userId'           => $userId,
                'userName'         => $fields['userName']['stringValue'] ?? '',
                'userEmail'        => $userInfoMap[$userId]['email'] ?? '',
                'userUpdatedAt'    => $userInfoMap[$userId]['updatedAt'] ?? '',
                'userTimezone'     => $userInfoMap[$userId]['timezone'] ?? '',
                'message'          => $fields['message']['stringValue'] ?? '',
                'createdAt'        => $fields['createdAt']['timestampValue'] ?? '',
                'isSubscribedUser' => $fields['isSubscribedUser']['booleanValue'] ?? false,
                'likes'            => (int) ($fields['likes']['integerValue'] ?? 0),
                'repliesCount'     => (int) ($fields['replies']['integerValue'] ?? count($repliesList)),
                'repliesList'      => $repliesList,
                'isBlocked'        => $userInfoMap[$userId]['isBlocked'] ?? false,
                'isHidden'         => $fields['isHidden']['booleanValue'] ?? false,
                'isSpam'           => $fields['isSpam']['booleanValue'] ?? false,
                'isProhibitedWord' => $fields['isProhibitedWord']['booleanValue'] ?? false,
                'isReported'       => $fields['isReported']['booleanValue'] ?? false,
                'reportCount'      => count($fields['reportedBy']['arrayValue']['values'] ?? []),
            ];
        }

        // For comments with no repliesList but with replies in the subcollection, fetch them now
        $needsSub = array_values(array_filter($comments, fn($c) => empty($c['repliesList']) && $c['repliesCount'] > 0));
        if (!empty($needsSub)) {
            $needsIds    = array_column($needsSub, 'id');
            $subReplies  = $this->firestore->getCommentRepliesBulk($id, $needsIds);

            // Collect extra user IDs from subcollection replies
            $extraUids = [];
            foreach ($subReplies as $subData) {
                foreach ($subData['docs'] ?? [] as $doc) {
                    $uid = $doc['fields']['userId']['stringValue'] ?? '';
                    if ($uid && !isset($userInfoMap[$uid])) $extraUids[] = $uid;
                }
            }
            foreach (array_unique($extraUids) as $uid) {
                $uf = $this->firestore->getFirestoreUser($uid);
                $uTz = $uf['timezone']['stringValue'] ?? 'UTC';
                $uUpd = $uf['updatedAt']['timestampValue'] ?? '';
                $uUpdFmt = '';
                if ($uUpd) { try { $uUpdFmt = \Carbon\Carbon::parse($uUpd)->setTimezone($uTz)->format('d/m/Y H:i'); } catch (\Exception $e) { $uUpdFmt = \Carbon\Carbon::parse($uUpd)->format('d/m/Y H:i'); } }
                $userInfoMap[$uid] = ['isBlocked' => $uf['isBlocked']['booleanValue'] ?? false, 'email' => $uf['email']['stringValue'] ?? '', 'updatedAt' => $uUpdFmt, 'timezone' => $uTz];
            }

            // Merge subcollection replies into the comments array
            foreach ($comments as &$c) {
                if (!empty($c['repliesList']) || !isset($subReplies[$c['id']])) continue;
                $subData     = $subReplies[$c['id']];
                $rDocs       = $subData['docs'] ?? [];
                $rCounts     = $subData['reportCounts'] ?? [];
                foreach ($rDocs as $rDoc) {
                    $rf      = $rDoc['fields'] ?? [];
                    $parts   = explode('/', $rDoc['name'] ?? '');
                    $rId     = end($parts);
                    $rUserId = $rf['userId']['stringValue'] ?? '';
                    $rDate   = $rf['createdAt']['timestampValue'] ?? '';
                    $c['repliesList'][] = [
                        'id'               => $rId,
                        'userId'           => $rUserId,
                        'userName'         => $rf['userName']['stringValue'] ?? ($userInfoMap[$rUserId]['name'] ?? ''),
                        'userEmail'        => $userInfoMap[$rUserId]['email'] ?? '',
                        'userUpdatedAt'    => $userInfoMap[$rUserId]['updatedAt'] ?? '',
                        'userTimezone'     => $userInfoMap[$rUserId]['timezone'] ?? '',
                        'message'          => $rf['message']['stringValue'] ?? '',
                        'createdAt'        => $rDate,
                        'isSubscribedUser' => $rf['isSubscribedUser']['booleanValue'] ?? false,
                        'likes'            => (int) ($rf['likes']['integerValue'] ?? 0),
                        'isBlocked'        => $userInfoMap[$rUserId]['isBlocked'] ?? false,
                        'isHidden'         => $rf['isHidden']['booleanValue'] ?? false,
                        'isSpam'           => $rf['isSpam']['booleanValue'] ?? false,
                        'isProhibitedWord' => $rf['isProhibitedWord']['booleanValue'] ?? false,
                        'isReported'       => $rf['isReported']['booleanValue'] ?? false,
                        'reportCount'      => $rCounts[$rId] ?? count($rf['reportedBy']['arrayValue']['values'] ?? []),
                    ];
                }
            }
            unset($c);
        }

        usort($comments, fn($a, $b) => strcmp($b['createdAt'], $a['createdAt']));

        return view('pages.daily-lights.comments', compact('comments', 'id'));
    }

    public function deleteComment(string $id, string $commentId)
    {
        $success = $this->firestore->deleteComment($id, $commentId);
        return response()->json(['success' => $success]);
    }

    public function deleteReply(string $id, string $commentId, string $replyId)
    {
        $success = $this->firestore->deleteReply($id, $commentId, $replyId);
        return response()->json(['success' => $success]);
    }

    public function toggleBlockUser(string $userId)
    {
        $userFields = $this->firestore->getFirestoreUser($userId);
        $currentBlocked = $userFields['isBlocked']['booleanValue'] ?? false;
        $newBlocked = !$currentBlocked;
        $success = $this->firestore->setUserBlockStatus($userId, $newBlocked);
        return response()->json(['success' => $success, 'isBlocked' => $newBlocked]);
    }

    public function clearCommentReport(string $id, string $commentId)
    {
        $success = $this->firestore->clearCommentReport($id, $commentId);
        return response()->json(['success' => $success]);
    }

    public function clearReplyReport(string $id, string $commentId, string $replyId)
    {
        $success = $this->firestore->clearReplyReport($id, $commentId, $replyId);
        return response()->json(['success' => $success]);
    }

    public function hideComment(string $id, string $commentId)
    {
        $success = $this->firestore->hideComment($id, $commentId);
        return response()->json(['success' => $success]);
    }

    public function approveComment(string $id, string $commentId)
    {
        $success = $this->firestore->approveComment($id, $commentId);
        return response()->json(['success' => $success]);
    }

    public function markCommentAsSpam(string $id, string $commentId)
    {
        $success = $this->firestore->markCommentAsSpam($id, $commentId);
        return response()->json(['success' => $success]);
    }

    public function unspamComment(string $id, string $commentId)
    {
        $success = $this->firestore->unspamComment($id, $commentId);
        return response()->json(['success' => $success]);
    }

    public function approveProhibitedWord(string $id, string $commentId)
    {
        $success = $this->firestore->approveProhibitedWord($id, $commentId);
        return response()->json(['success' => $success]);
    }

    public function approveReplyProhibitedWord(string $id, string $commentId, string $replyId)
    {
        $success = $this->firestore->approveReplyProhibitedWord($id, $commentId, $replyId);
        return response()->json(['success' => $success]);
    }

    public function hideReply(string $id, string $commentId, string $replyId)
    {
        $success = $this->firestore->hideReply($id, $commentId, $replyId);
        return response()->json(['success' => $success]);
    }

    public function approveReply(string $id, string $commentId, string $replyId)
    {
        $success = $this->firestore->approveReply($id, $commentId, $replyId);
        return response()->json(['success' => $success]);
    }

    public function commentModeration()
    {
        ['comments' => $comments, 'dlMap' => $dlMap] = $this->fetchAllComments();
        $activeTab = in_array(request('tab'), ['reported', 'hidden']) ? request('tab') : 'all';
        return view('pages.comments.moderation', compact('comments', 'dlMap', 'activeTab'));
    }

    public function reportedComments()
    {
        return redirect()->route('comment-moderation.index', ['tab' => 'reported']);
    }

    public function hiddenComments()
    {
        return redirect()->route('comment-moderation.index', ['tab' => 'hidden']);
    }

    private function fetchAllComments(): array
    {
        // 1. Build daily light source map
        $items = $this->firestore->getDailyLights();
        $dlMap = [];
        $publishedIds = [];

        foreach ($items as $id => $data) {
            $fields      = $data['fields'];
            $status      = $fields['status']['stringValue'] ?? 'draft';
            $utcDatetime = $fields['publishDateTimeUtc']['stringValue'] ?? '';
            $rawDate     = $fields['date']['stringValue'] ?? '';
            $rawTime     = $fields['publishTime']['stringValue'] ?? '';

            if ($utcDatetime) {
                $brazilDt    = DateTimeService::utcToBrazil($utcDatetime);
                $displayDate = $brazilDt->format('d-m-Y');
            } else {
                $displayDate = $rawDate;
                try {
                    $parsed      = $this->parseDailyLightDate($rawDate, $rawTime ?: null);
                    $displayDate = $parsed->format('d-m-Y');
                } catch (\Exception $e) {}
            }

            $dlMap[$id] = ['date' => $displayDate, 'title' => $data['ptTitle'] ?? '', 'status' => $status];

            if ($status === 'published') {
                $publishedIds[] = $id;
            }
        }

        // 2. Fetch all comments concurrently
        $rawComments = $this->firestore->getAllDailyLightComments($publishedIds);

        // 3a. Fetch reply + report subcollections for all comments in parallel (new structure)
        $commentMetas = array_map(fn($c) => ['dlId' => $c['dlId'], 'id' => $c['id']], $rawComments);
        $subcollections = $this->firestore->getCommentSubcollections($commentMetas);

        // 3b. Collect unique user IDs from comment + replies (both old array and new subcollection)
        $allUserIds = [];
        foreach ($rawComments as $c) {
            $uid = $c['fields']['userId']['stringValue'] ?? '';
            if ($uid) $allUserIds[] = $uid;
            // Old array structure
            foreach ($c['fields']['repliesList']['arrayValue']['values'] ?? [] as $rv) {
                $rUid = $rv['mapValue']['fields']['userId']['stringValue'] ?? '';
                if ($rUid) $allUserIds[] = $rUid;
            }
        }
        // New subcollection structure
        foreach ($subcollections as $subData) {
            foreach ($subData['replies'] as $replyDoc) {
                $rUid = $replyDoc['fields']['userId']['stringValue'] ?? '';
                if ($rUid) $allUserIds[] = $rUid;
            }
        }
        $allUserIds = array_values(array_unique($allUserIds));

        $rawUsers    = $this->firestore->batchGetUsers($allUserIds);
        $userInfoMap = [];
        foreach ($rawUsers as $uid => $uf) {
            $userInfoMap[$uid] = [
                'name'      => $uf['name']['stringValue'] ?? '',
                'email'     => $uf['email']['stringValue'] ?? '',
                'isBlocked' => $uf['isBlocked']['booleanValue'] ?? false,
                'timezone'  => $uf['timezone']['stringValue'] ?? '',
                'photoUrl'  => $uf['profileImage']['stringValue'] ?? '',
            ];
        }

        // 4. Normalise
        $comments = [];
        foreach ($rawComments as $raw) {
            $fields    = $raw['fields'];
            $dlId      = $raw['dlId'];
            $commentId = $raw['id'];
            $userId    = $fields['userId']['stringValue'] ?? '';
            $subData   = $subcollections[$commentId] ?? ['replies' => [], 'reports' => [], 'replyReportCounts' => []];

            // ---- Parse replies: prefer subcollection, fall back to repliesList array ----
            $repliesList = [];
            if (!empty($subData['replies'])) {
                // New structure: reply documents in subcollection
                foreach ($subData['replies'] as $replyDoc) {
                    $rf      = $replyDoc['fields'] ?? [];
                    $rUserId = $rf['userId']['stringValue'] ?? '';
                    $rDate   = '';
                    try {
                        $tsRaw = $rf['createdAt']['timestampValue'] ?? ($replyDoc['createTime'] ?? '');
                        if ($tsRaw) $rDate = \Carbon\Carbon::parse($tsRaw)->format('d/m/Y H:i');
                    } catch (\Exception $e) {}
                    // Reply ID = document name basename
                    $parts = explode('/', $replyDoc['name'] ?? '');
                    $rId   = end($parts);
                    $repliesList[] = [
                        'id'               => $rId,
                        'userId'           => $rUserId,
                        'userName'         => $rf['userName']['stringValue'] ?? ($userInfoMap[$rUserId]['name'] ?? ''),
                        'userEmail'        => $userInfoMap[$rUserId]['email'] ?? '',
                        'message'          => $rf['message']['stringValue'] ?? '',
                        'createdAt'        => $rDate,
                        'isBlocked'        => $userInfoMap[$rUserId]['isBlocked'] ?? false,
                        'isHidden'         => $rf['isHidden']['booleanValue'] ?? false,
                        'isProhibitedWord' => $rf['isProhibitedWord']['booleanValue'] ?? false,
                        'isReported'       => $rf['isReported']['booleanValue'] ?? false,
                        'reportCount'      => $subData['replyReportCounts'][$rId] ?? count($rf['reportedBy']['arrayValue']['values'] ?? []),
                        'likes'            => (int) ($rf['likes']['integerValue'] ?? 0),
                        'photoUrl'         => $userInfoMap[$rUserId]['photoUrl'] ?? ($rf['userPhoto']['stringValue'] ?? ''),
                        'isSubscribedUser' => $rf['isSubscribedUser']['booleanValue'] ?? false,
                    ];
                }
            } else {
                // Old structure: repliesList array embedded in comment document
                foreach ($fields['repliesList']['arrayValue']['values'] ?? [] as $rv) {
                    $rf      = $rv['mapValue']['fields'] ?? [];
                    $rUserId = $rf['userId']['stringValue'] ?? '';
                    $rDate   = '';
                    try {
                        if (!empty($rf['createdAt']['timestampValue'])) {
                            $rDate = \Carbon\Carbon::parse($rf['createdAt']['timestampValue'])->format('d/m/Y H:i');
                        }
                    } catch (\Exception $e) {}
                    $repliesList[] = [
                        'id'               => $rf['id']['stringValue'] ?? '',
                        'userId'           => $rUserId,
                        'userName'         => $rf['userName']['stringValue'] ?? '',
                        'userEmail'        => $userInfoMap[$rUserId]['email'] ?? '',
                        'message'          => $rf['message']['stringValue'] ?? '',
                        'createdAt'        => $rDate,
                        'isBlocked'        => $userInfoMap[$rUserId]['isBlocked'] ?? false,
                        'isHidden'         => $rf['isHidden']['booleanValue'] ?? false,
                        'isProhibitedWord' => $rf['isProhibitedWord']['booleanValue'] ?? false,
                        'isReported'       => $rf['isReported']['booleanValue'] ?? false,
                        'reportCount'      => count($rf['reportedBy']['arrayValue']['values'] ?? []),
                        'likes'            => (int) ($rf['likes']['integerValue'] ?? 0),
                        'photoUrl'         => $userInfoMap[$rUserId]['photoUrl'] ?? '',
                        'isSubscribedUser' => $rf['isSubscribedUser']['booleanValue'] ?? false,
                    ];
                }
            }

            // ---- Report count: prefer subcollection, fall back to reportedBy array ----
            $reportCount = !empty($subData['reports'])
                ? count($subData['reports'])
                : count($fields['reportedBy']['arrayValue']['values'] ?? []);

            // ---- isReported: true if reportCount > 0 OR field says true ----
            $isReported = ($fields['isReported']['booleanValue'] ?? false) || $reportCount > 0;

            $hasReportedReply = collect($repliesList)->contains(fn($r) => !empty($r['isReported']) || ($r['reportCount'] ?? 0) > 0);

            $comments[] = [
                'id'               => $commentId,
                'sourceType'       => 'dl',
                'jornadaId'        => '',
                'lessonId'         => '',
                'dlId'             => $dlId,
                'dlDate'           => $dlMap[$dlId]['date'] ?? $dlId,
                'dlTitle'          => $dlMap[$dlId]['title'] ?? '',
                'userId'           => $userId,
                'userName'         => $fields['userName']['stringValue'] ?? ($userInfoMap[$userId]['name'] ?? ''),
                'userEmail'        => $userInfoMap[$userId]['email'] ?? '',
                'userTimezone'     => $userInfoMap[$userId]['timezone'] ?? '',
                'userPhotoUrl'     => $userInfoMap[$userId]['photoUrl'] ?? '',
                'isBlocked'        => $userInfoMap[$userId]['isBlocked'] ?? false,
                'message'          => $fields['message']['stringValue'] ?? '',
                'createdAt'        => $fields['createdAt']['timestampValue'] ?? '',
                'isReported'       => $isReported,
                'reportCount'      => $reportCount,
                'isHidden'         => $fields['isHidden']['booleanValue'] ?? false,
                'isSpam'           => $fields['isSpam']['booleanValue'] ?? false,
                'isProhibitedWord' => $fields['isProhibitedWord']['booleanValue'] ?? false,
                'isSubscribedUser' => $fields['isSubscribedUser']['booleanValue'] ?? false,
                'likes'            => (int) ($fields['likes']['integerValue'] ?? 0),
                'repliesCount'     => !empty($subData['replies']) ? count($repliesList) : (int) ($fields['replies']['integerValue'] ?? count($repliesList)),
                'repliesList'      => $repliesList,
                'hasReportedReply' => $hasReportedReply,
                'autoHidden'       => false,
            ];
        }

        // 4b. Fetch and merge jornada lesson comments
        $jornadaItems = $this->firestore->getJornadas();
        $publishedJornadaIds = [];
        $jornadaMap = [];
        foreach ($jornadaItems as $jId => $jData) {
            $jStatus = $jData['fields']['status']['stringValue'] ?? 'draft';
            $jornadaMap[$jId] = $jData['ptTitle'] ?? '';
            if ($jStatus === 'published') {
                $publishedJornadaIds[] = $jId;
            }
        }

        $rawJornadaComments = $this->firestore->getAllJornadaComments($publishedJornadaIds);

        // Collect jornada comment user IDs not already fetched
        $jnUserIds = [];
        foreach ($rawJornadaComments as $jc) {
            $uid = $jc['fields']['userId']['stringValue'] ?? '';
            if ($uid && !isset($userInfoMap[$uid])) {
                $jnUserIds[] = $uid;
            }
        }
        $jnUserIds = array_values(array_unique($jnUserIds));
        if (!empty($jnUserIds)) {
            $newUsers = $this->firestore->batchGetUsers($jnUserIds);
            foreach ($newUsers as $uid => $uf) {
                $userInfoMap[$uid] = [
                    'name'      => $uf['name']['stringValue'] ?? '',
                    'email'     => $uf['email']['stringValue'] ?? '',
                    'isBlocked' => $uf['isBlocked']['booleanValue'] ?? false,
                    'timezone'  => $uf['timezone']['stringValue'] ?? '',
                    'photoUrl'  => $uf['profileImage']['stringValue'] ?? '',
                ];
            }
        }

        foreach ($rawJornadaComments as $jc) {
            $jFields    = $jc['fields'];
            $jornadaId  = $jc['jornadaId'];
            $lessonId   = $jc['lessonId'];
            $commentId  = $jc['id'];
            $userId     = $jFields['userId']['stringValue'] ?? '';
            $isReported = $jFields['isReported']['booleanValue'] ?? false;

            $comments[] = [
                'id'               => $commentId,
                'sourceType'       => 'jornada',
                'jornadaId'        => $jornadaId,
                'lessonId'         => $lessonId,
                'dlId'             => '',
                'dlDate'           => '',
                'dlTitle'          => $jornadaMap[$jornadaId] ?? '',
                'userId'           => $userId,
                'userName'         => $jFields['userName']['stringValue'] ?? ($userInfoMap[$userId]['name'] ?? ''),
                'userEmail'        => $userInfoMap[$userId]['email'] ?? '',
                'userTimezone'     => $userInfoMap[$userId]['timezone'] ?? '',
                'userPhotoUrl'     => $userInfoMap[$userId]['photoUrl'] ?? ($jFields['avatarUrl']['stringValue'] ?? ''),
                'isBlocked'        => $userInfoMap[$userId]['isBlocked'] ?? false,
                'message'          => $jFields['message']['stringValue'] ?? '',
                'createdAt'        => $jFields['createdAt']['timestampValue'] ?? '',
                'isReported'       => $isReported,
                'reportCount'      => $isReported ? 1 : 0,
                'isHidden'         => $jFields['isHidden']['booleanValue'] ?? false,
                'isSpam'           => false,
                'isProhibitedWord' => $jFields['isProhibitedWord']['booleanValue'] ?? false,
                'isSubscribedUser' => $jFields['isSubscribedUser']['booleanValue'] ?? false,
                'likes'            => (int) ($jFields['likes']['integerValue'] ?? 0),
                'repliesCount'     => 0,
                'repliesList'      => [],
                'hasReportedReply' => false,
                'autoHidden'       => false,
            ];
        }

        usort($comments, fn($a, $b) => strcmp($b['createdAt'], $a['createdAt']));

        // 5. Auto-hide DL comments that have 3+ reports and are not yet hidden
        $toAutoHide = [];
        foreach ($comments as &$comment) {
            if ($comment['sourceType'] === 'dl' && $comment['reportCount'] >= 3 && !$comment['isHidden']) {
                $comment['isHidden']   = true;
                $comment['autoHidden'] = true;
                $toAutoHide[] = ['dlId' => $comment['dlId'], 'id' => $comment['id']];
            }
        }
        unset($comment);
        foreach ($toAutoHide as $item) {
            $this->firestore->hideComment($item['dlId'], $item['id']);
        }

        return compact('comments', 'dlMap');
    }

}
