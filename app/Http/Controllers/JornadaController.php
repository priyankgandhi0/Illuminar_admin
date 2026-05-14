<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\FirestoreService;
use App\Services\FileStorageService;
use Illuminate\Support\Facades\Storage;
use Aws\S3\S3Client;

class JornadaController extends Controller
{
    protected $firestore;
    protected $fileStorage;

    public function __construct(FirestoreService $firestore, FileStorageService $fileStorage)
    {
        $this->firestore = $firestore;

        $this->fileStorage = $fileStorage;
    }

    public function index()
    {

        $items = $this->firestore->getJornadas();

        // Build category map for PT names and order
        $categories = $this->firestore->getJornadaCategories();
        $categoryMap = [];
        $categoryOrderMap = [];
        foreach ($categories as $catId => $catFields) {
            $categoryMap[$catId] = $catFields['pt_title']['stringValue'] ?? '';
            $categoryOrderMap[$catId] = (int) ($catFields['order']['integerValue'] ?? 9999);
        }

        $jornadas = [];
        foreach ($items as $id => $data) {
            $fields = $data['fields'];
            $catId = $fields['category_id']['stringValue'] ?? '';
            $jornadas[] = [
                'id' => $id,
                'title' => $data['ptTitle'] ?? '',
                'category' => $categoryMap[$catId] ?? '',
                'category_id' => $catId,
                'status' => $fields['status']['stringValue'] ?? 'draft',
                'languages' => $data['languages'] ?? [],
                'order' => (int) ($fields['order']['integerValue'] ?? 9999),
                'categoryOrder' => $categoryOrderMap[$catId] ?? 9999,
                'createdAt' => $fields['createdAt']['timestampValue'] ?? '',
            ];
        }

        // Sort by category order first, then journey order within each category
        // usort($jornadas, function ($a, $b) {
        //     if ($a['categoryOrder'] !== $b['categoryOrder']) return $a['categoryOrder'] <=> $b['categoryOrder'];
        //     if ($a['order'] !== $b['order']) return $a['order'] <=> $b['order'];
        //     return strcmp($b['createdAt'], $a['createdAt']);
        // });

        usort($jornadas, function ($a, $b) {

            // Latest createdAt first (DESC)
            if ($a['createdAt'] !== $b['createdAt']) {
                return strcmp($b['createdAt'], $a['createdAt']);
            }

            // categoryOrder ASC
            if ($a['categoryOrder'] !== $b['categoryOrder']) {
                return $a['categoryOrder'] <=> $b['categoryOrder'];
            }

            // order ASC
            return $a['order'] <=> $b['order'];
            
        });

        $categoryNames = array_unique(array_filter(array_values($categoryMap)));
        sort($categoryNames);
        $hasCategories = count($categories) > 0;
        return view('pages.jornadas.index', compact('jornadas', 'categoryNames', 'hasCategories'));
    }

    public function create()
    {
        $categories = $this->firestore->getJornadaCategories();

        if (empty($categories)) {
            return redirect()->route('jornadas.index')
                ->with('no_categories', true);
        }

        return view('pages.jornadas.create', compact('categories'));
    }

    public function store(Request $request)
    {
        $enabledLangs = $this->getEnabledLanguages($request);

        $rules = [
            'status' => 'required|in:published,draft',
            'category_id' => 'required|string',
        ];
        $messages = [
            'category_id.required' => __('jornadas.select_category'),
        ];

        foreach ($enabledLangs as $lang) {
            $lessonCount = (int) $request->input("lesson_count_{$lang}", 0);

            $rules["title_{$lang}"] = 'required|string|max:255';
            $rules["description_{$lang}"] = 'required|string';
            $rules["cover_image_{$lang}"] = 'required|image|max:5120';
            $messages["title_{$lang}.required"] = __('common.required_field');
            $messages["description_{$lang}.required"] = __('common.required_field');
            $messages["cover_image_{$lang}.required"] = __('common.image_required');

            for ($i = 0; $i < $lessonCount; $i++) {
                $rules["lesson_title_{$lang}_{$i}"] = 'required|string|max:255';
                $rules["lesson_description_{$lang}_{$i}"] = 'required|string';
                $rules["lesson_audio_{$lang}_{$i}"] = 'required|file|mimes:mp3,wav,m4a,aac,ogg,opus,mp4|max:51200';
                $messages["lesson_title_{$lang}_{$i}.required"] = __('common.required_field');
                $messages["lesson_description_{$lang}_{$i}.required"] = __('common.required_field');
                $messages["lesson_audio_{$lang}_{$i}.required"] = __('common.audio_required');
            }
        }

        $request->validate($rules, $messages);

        try {
            $mainFields = [
                'status' => ['stringValue' => $request->input('status')],
                'category_id' => ['stringValue' => $request->input('category_id')],
                'createdAt' => ['timestampValue' => now()->toIso8601String()],
                'updatedAt' => ['timestampValue' => now()->toIso8601String()],
            ];

            $result = $this->firestore->createJornada($mainFields);

            if (!$result['success']) {
                return back()->with('error', __('jornadas.failed_create'))->withInput();
            }

            $docId = $result['docId'];

            // Save jornada translations (title, description, image)
            foreach ($enabledLangs as $lang) {
                $imageKey = '';
                if ($request->hasFile("cover_image_{$lang}")) {
                    $uploaded = $this->fileStorage->uploadJornadaFile(
                        $request->file("cover_image_{$lang}"), $docId, $lang
                    );
                    $imageKey = $uploaded['storage_key'];
                }

                $transFields = [
                    'title' => ['stringValue' => (string) $request->input("title_{$lang}")],
                    'description' => ['stringValue' => (string) $request->input("description_{$lang}")],
                    'image' => ['stringValue' => $imageKey],
                ];

                $this->firestore->saveJornadaTranslation($docId, $lang, $transFields);
            }

            // Create ONE lesson document
            $lessonResult = $this->firestore->createLesson($docId, [
                'createdAt' => ['timestampValue' => now()->toIso8601String()],
                'updatedAt' => ['timestampValue' => now()->toIso8601String()],
            ]);

            if ($lessonResult['success']) {
                $lessonDocId = $lessonResult['lessonId'];

                // For each language, build lessons array and save as lesson translation
                foreach ($enabledLangs as $lang) {
                    $lessonCount = (int) $request->input("lesson_count_{$lang}", 0);
                    $lessonsArray = [];

                    for ($i = 0; $i < $lessonCount; $i++) {
                        $audioDuration = '00:00';
                        $audioKey = '';

                        if ($request->hasFile("lesson_audio_{$lang}_{$i}")) {
                            $audioFile = $request->file("lesson_audio_{$lang}_{$i}");
                            $audioDuration = $this->getAudioDuration($audioFile->getPathname());
                            $uploaded = $this->fileStorage->uploadJornadaFile($audioFile, $docId, $lang, "lesson_{$i}");
                            $audioKey = $uploaded['storage_key'];
                        }

                        $lessonsArray[] = [
                            'mapValue' => [
                                'fields' => [
                                    'index' => ['integerValue' => (string) ($i + 1)],
                                    'title' => ['stringValue' => (string) $request->input("lesson_title_{$lang}_{$i}")],
                                    'description' => ['stringValue' => (string) $request->input("lesson_description_{$lang}_{$i}")],
                                    'audio_path' => ['stringValue' => $audioKey],
                                    'audioDuration' => ['stringValue' => $audioDuration],
                                ]
                            ]
                        ];
                    }

                    $this->firestore->saveLessonTranslation($docId, $lessonDocId, $lang, [
                        'lessons' => [
                            'arrayValue' => [
                                'values' => $lessonsArray
                            ]
                        ]
                    ]);
                }
            }

            return redirect()->route('jornadas.index')->with('success', __('jornadas.created_success'));

        } catch (\Exception $e) {
            \Log::error('Jornada store exception', ['message' => $e->getMessage()]);
            return back()->with('error', __('common.error') . ': ' . $e->getMessage())->withInput();
        }
    }

    public function edit(string $id)
    {
        $data = $this->firestore->getJornada($id);

        if (!$data) {
            return redirect()->route('jornadas.index')->with('error', __('jornadas.not_found'));
        }

        $mainFields = $data['main'];

        $jornada = [
            'id' => $id,
            'status' => $mainFields['status']['stringValue'] ?? 'draft',
            'category_id' => $mainFields['category_id']['stringValue'] ?? '',
            'translations' => [],
        ];

        $allLanguages = ['pt', 'en', 'es'];
        foreach ($allLanguages as $lang) {
            if (!isset($data['translations'][$lang])) continue;

            $langFields = $data['translations'][$lang];
            $imageKey = $langFields['image']['stringValue'] ?? '';
            $imageUrl = null;
            if ($imageKey) {
                try {
                    $imageUrl = Storage::disk('r2')->temporaryUrl($imageKey, now()->addMinutes(5));
                } catch (\Exception $e) {}
            }

            $jornada['translations'][$lang] = [
                'title' => $langFields['title']['stringValue'] ?? $langFields['tittle']['stringValue'] ?? '',
                'description' => $langFields['description']['stringValue'] ?? '',
                'image' => $imageKey,
                'image_url' => $imageUrl,
            ];
        }

        // Build lessonsByLang with audio URLs
        $lessonsByLang = $data['lessons_by_lang'] ?? [];
        foreach ($lessonsByLang as $lang => &$lessons) {
            foreach ($lessons as &$lesson) {
                $audioKey = $lesson['audio_path'] ?? '';
                $lesson['audio_url'] = null;
                if ($audioKey) {
                    try {
                        $lesson['audio_url'] = Storage::disk('r2')->temporaryUrl($audioKey, now()->addMinutes(5));
                    } catch (\Exception $e) {}
                }
            }
            unset($lesson);
        }
        unset($lessons);

        $lessonDocId = $data['lesson_doc_id'] ?? null;
        $categories = $this->firestore->getJornadaCategories();
        return view('pages.jornadas.edit', compact('jornada', 'lessonsByLang', 'lessonDocId', 'categories'));
    }

    public function update(Request $request, string $id)
    {
        $enabledLangs = $this->getEnabledLanguages($request);

        $rules = [
            'status' => 'required|in:published,draft',
            'category_id' => 'required|string',
        ];
        $messages = [
            'category_id.required' => __('jornadas.select_category'),
        ];

        foreach ($enabledLangs as $lang) {
            $lessonCount = (int) $request->input("lesson_count_{$lang}", 0);

            $rules["title_{$lang}"] = 'required|string|max:255';
            $rules["description_{$lang}"] = 'required|string';
            $rules["cover_image_{$lang}"] = 'nullable|image|max:5120';
            $messages["title_{$lang}.required"] = __('common.required_field');
            $messages["description_{$lang}.required"] = __('common.required_field');

            for ($i = 0; $i < $lessonCount; $i++) {
                $rules["lesson_title_{$lang}_{$i}"] = 'required|string|max:255';
                $rules["lesson_description_{$lang}_{$i}"] = 'required|string';
                $hasExisting = $request->input("lesson_existing_audio_{$lang}_{$i}");
                if (!$hasExisting) {
                    $rules["lesson_audio_{$lang}_{$i}"] = 'required|file|mimes:mp3,wav,m4a,aac,ogg,opus,mp4|max:51200';
                    $messages["lesson_audio_{$lang}_{$i}.required"] = __('common.audio_required');
                } else {
                    $rules["lesson_audio_{$lang}_{$i}"] = 'nullable|file|mimes:mp3,wav,m4a,aac,ogg,opus,mp4|max:51200';
                }
                $messages["lesson_title_{$lang}_{$i}.required"] = __('common.required_field');
                $messages["lesson_description_{$lang}_{$i}.required"] = __('common.required_field');
            }
        }

        $request->validate($rules, $messages);

        try {
            $existingData = $this->firestore->getJornada($id);
            $existingTranslations = $existingData['translations'] ?? [];
            $existingLessonsByLang = $existingData['lessons_by_lang'] ?? [];
            $lessonDocId = $request->input('lesson_doc_id') ?: ($existingData['lesson_doc_id'] ?? null);

            // Collect old audio paths for R2 cleanup
            $oldAudioPaths = [];
            foreach ($existingLessonsByLang as $lang => $lessons) {
                foreach ($lessons as $lesson) {
                    if (!empty($lesson['audio_path'])) {
                        $oldAudioPaths[] = $lesson['audio_path'];
                    }
                }
            }

            $mainFields = [
                'status' => ['stringValue' => $request->input('status')],
                'category_id' => ['stringValue' => $request->input('category_id')],
                'id' => ['stringValue' => $id],
                'createdAt' => $existingData['main']['createdAt'] ?? ['timestampValue' => now()->toIso8601String()],
                'updatedAt' => ['timestampValue' => now()->toIso8601String()],
            ];

            // Update jornada translations
            $translations = [];
            foreach ($enabledLangs as $lang) {
                $existingImageKey = $existingTranslations[$lang]['image']['stringValue'] ?? '';
                $imageKey = $existingImageKey;

                if ($request->hasFile("cover_image_{$lang}")) {
                    $uploaded = $this->fileStorage->uploadJornadaFile(
                        $request->file("cover_image_{$lang}"), $id, $lang
                    );
                    $imageKey = $uploaded['storage_key'];
                }

                $translations[$lang] = [
                    'title' => ['stringValue' => (string) $request->input("title_{$lang}")],
                    'description' => ['stringValue' => (string) $request->input("description_{$lang}")],
                    'image' => ['stringValue' => $imageKey],
                ];
            }

            $result = $this->firestore->updateJornada($id, $mainFields, $translations);
            if (!$result['success']) {
                return back()->with('error', __('jornadas.failed_update'))->withInput();
            }

            // If no lesson doc exists (old format or new), clean up old docs and create new one
            if (!$lessonDocId) {
                $this->firestore->deleteAllLessons($id);
                $lessonResult = $this->firestore->createLesson($id, [
                    'createdAt' => ['timestampValue' => now()->toIso8601String()],
                    'updatedAt' => ['timestampValue' => now()->toIso8601String()],
                ]);
                if ($lessonResult['success']) {
                    $lessonDocId = $lessonResult['lessonId'];
                }
            } else {
                $this->firestore->updateLesson($id, $lessonDocId, [
                    'updatedAt' => ['timestampValue' => now()->toIso8601String()],
                ]);
            }

            // Build and save lessons array for each enabled language
            $newAudioPaths = [];
            foreach ($enabledLangs as $lang) {
                $lessonCount = (int) $request->input("lesson_count_{$lang}", 0);
                $lessonsArray = [];

                for ($i = 0; $i < $lessonCount; $i++) {
                    $audioKey = $request->input("lesson_existing_audio_{$lang}_{$i}", '');
                    $audioDuration = $request->input("lesson_existing_duration_{$lang}_{$i}", '00:00');

                    if ($request->hasFile("lesson_audio_{$lang}_{$i}")) {
                        $audioFile = $request->file("lesson_audio_{$lang}_{$i}");
                        $audioDuration = $this->getAudioDuration($audioFile->getPathname());
                        $uploaded = $this->fileStorage->uploadJornadaFile($audioFile, $id, $lang, "lesson_{$i}");
                        $audioKey = $uploaded['storage_key'];
                    }

                    if ($audioKey) {
                        $newAudioPaths[] = $audioKey;
                    }

                    $lessonsArray[] = [
                        'mapValue' => [
                            'fields' => [
                                'index' => ['integerValue' => (string) ($i + 1)],
                                'title' => ['stringValue' => (string) $request->input("lesson_title_{$lang}_{$i}")],
                                'description' => ['stringValue' => (string) $request->input("lesson_description_{$lang}_{$i}")],
                                'audio_path' => ['stringValue' => $audioKey],
                                'audioDuration' => ['stringValue' => $audioDuration],
                            ]
                        ]
                    ];
                }

                if ($lessonDocId) {
                    $this->firestore->saveLessonTranslation($id, $lessonDocId, $lang, [
                        'lessons' => [
                            'arrayValue' => [
                                'values' => $lessonsArray
                            ]
                        ]
                    ]);
                }
            }

            // Clean up removed audio files from R2
            $removedAudios = array_diff($oldAudioPaths, $newAudioPaths);
            foreach ($removedAudios as $audioPath) {
                try { Storage::disk('r2')->delete($audioPath); } catch (\Exception $e) {}
            }

            // Delete jornada translations + lesson translations for disabled languages
            $allLangs = ['pt', 'en', 'es'];
            foreach ($allLangs as $lang) {
                if (!in_array($lang, $enabledLangs)) {
                    if (isset($existingTranslations[$lang])) {
                        $imgKey = $existingTranslations[$lang]['image']['stringValue'] ?? '';
                        if ($imgKey) {
                            try { Storage::disk('r2')->delete($imgKey); } catch (\Exception $e) {}
                        }
                        $this->firestore->deleteJornadaTranslation($id, $lang);
                    }
                    if ($lessonDocId) {
                        $this->firestore->deleteLessonTranslation($id, $lessonDocId, $lang);
                    }
                }
            }

            return redirect()->route('jornadas.index')->with('success', __('jornadas.updated_success'));

        } catch (\Exception $e) {
            \Log::error('Jornada update exception', ['message' => $e->getMessage()]);
            return back()->with('error', __('common.error') . ': ' . $e->getMessage())->withInput();
        }
    }

    public function destroy(string $id)
    {
        try {
            $data = $this->firestore->getJornada($id);
            if ($data) {
                // Delete cover images from R2
                foreach ($data['translations'] as $lang => $trans) {
                    $imgKey = $trans['image']['stringValue'] ?? '';
                    if ($imgKey) {
                        try { Storage::disk('r2')->delete($imgKey); } catch (\Exception $e) {}
                    }
                }
                // Delete lesson audio files from R2
                foreach ($data['lessons_by_lang'] as $lang => $langLessons) {
                    foreach ($langLessons as $lesson) {
                        $audioKey = $lesson['audio_path'] ?? '';
                        if ($audioKey) {
                            try { Storage::disk('r2')->delete($audioKey); } catch (\Exception $e) {}
                        }
                    }
                }
            }

            $success = $this->firestore->deleteJornada($id);

            if ($success) {
                return redirect()->route('jornadas.index')->with('success', __('jornadas.deleted_success'));
            }

            return back()->with('error', __('jornadas.failed_delete'));
        } catch (\Exception $e) {
            \Log::error('Jornada delete exception', ['message' => $e->getMessage()]);
            return back()->with('error', __('common.error') . ': ' . $e->getMessage());
        }
    }

    /**
     * Step 1: Create/update main Jornada doc + lesson doc. Returns JSON.
     */
    public function storeMain(Request $request)
    {
        $isEdit = $request->boolean('is_edit');
        $docId  = $request->input('doc_id', '');
        $enabledLangs = $this->getEnabledLanguages($request);

        $request->validate([
            'status'      => 'required|in:published,draft',
            'category_id' => 'required|string',
        ], ['category_id.required' => __('jornadas.select_category')]);

        try {
            $mainFields = [
                'status'      => ['stringValue' => $request->input('status')],
                'category_id' => ['stringValue' => $request->input('category_id')],
                'updatedAt'   => ['timestampValue' => now()->toIso8601String()],
            ];

            if ($isEdit && $docId) {
                $existingData = $this->firestore->getJornada($docId);
                $mainFields['id']         = ['stringValue' => $docId];
                $mainFields['createdAt']  = $existingData['main']['createdAt'] ?? ['timestampValue' => now()->toIso8601String()];

                $lessonDocId = $request->input('lesson_doc_id') ?: ($existingData['lesson_doc_id'] ?? null);

                if (!$lessonDocId) {
                    $this->firestore->deleteAllLessons($docId);
                    $lessonResult = $this->firestore->createLesson($docId, [
                        'createdAt' => ['timestampValue' => now()->toIso8601String()],
                        'updatedAt' => ['timestampValue' => now()->toIso8601String()],
                    ]);
                    $lessonDocId = $lessonResult['success'] ? $lessonResult['lessonId'] : null;
                } else {
                    $this->firestore->updateLesson($docId, $lessonDocId, [
                        'updatedAt' => ['timestampValue' => now()->toIso8601String()],
                    ]);
                }

                $result = $this->firestore->updateJornada($docId, $mainFields, []);
                if (!$result['success']) {
                    return response()->json(['success' => false, 'message' => __('jornadas.failed_update')], 500);
                }

                // Delete disabled language data
                $existingTranslations = $existingData['translations'] ?? [];
                foreach (['pt', 'en', 'es'] as $lang) {
                    if (!in_array($lang, $enabledLangs)) {
                        if (isset($existingTranslations[$lang])) {
                            $imgKey = $existingTranslations[$lang]['image']['stringValue'] ?? '';
                            if ($imgKey) {
                                try { Storage::disk('r2')->delete($imgKey); } catch (\Exception $e) {}
                            }
                            $this->firestore->deleteJornadaTranslation($docId, $lang);
                        }
                        if ($lessonDocId) {
                            $this->firestore->deleteLessonTranslation($docId, $lessonDocId, $lang);
                        }
                    }
                }

                return response()->json(['success' => true, 'docId' => $docId, 'lessonDocId' => $lessonDocId, 'enabledLangs' => $enabledLangs]);
            } else {
                $mainFields['createdAt'] = ['timestampValue' => now()->toIso8601String()];
                $catId = $request->input('category_id', '');
                $existing = $this->firestore->getJornadas();
                $countInCategory = 0;
                foreach ($existing as $data) {
                    if (($data['fields']['category_id']['stringValue'] ?? '') === $catId) {
                        $countInCategory++;
                    }
                }
                $mainFields['order'] = ['integerValue' => $countInCategory + 1];

                $result = $this->firestore->createJornada($mainFields);
                if (!$result['success']) {
                    return response()->json(['success' => false, 'message' => __('jornadas.failed_create')], 500);
                }
                $docId = $result['docId'];

                $lessonResult = $this->firestore->createLesson($docId, [
                    'createdAt' => ['timestampValue' => now()->toIso8601String()],
                    'updatedAt' => ['timestampValue' => now()->toIso8601String()],
                ]);
                $lessonDocId = $lessonResult['success'] ? $lessonResult['lessonId'] : null;

                return response()->json(['success' => true, 'docId' => $docId, 'lessonDocId' => $lessonDocId, 'enabledLangs' => $enabledLangs]);
            }
        } catch (\Exception $e) {
            \Log::error('Jornada storeMain exception', ['message' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => __('common.error') . ': ' . $e->getMessage()], 500);
        }
    }

    /**
     * Step 2a: Upload a single file (cover image or lesson audio).
     */
    // public function uploadFile(Request $request, string $id)
    // {
    //     ini_set('max_execution_time', 300);
    //     ini_set('max_input_time', 300);
    //     ini_set('memory_limit', '512M');

    //     $lang  = $request->input('lang');
    //     $type  = $request->input('type'); // 'cover_image' or 'lesson_audio'
    //     $lessonIndex = (int) $request->input('lesson_index', 0);

    //     if (!in_array($lang, ['pt', 'en', 'es'])) {
    //         return response()->json(['success' => false, 'message' => 'Invalid lang'], 422);
    //     }

    //     try {
    //         if ($type === 'cover_image' && $request->hasFile('file')) {
    //             $request->validate(['file' => 'required|image|max:5120']);
    //             $uploaded = $this->fileStorage->uploadJornadaFile($request->file('file'), $id, $lang);
    //             return response()->json(['success' => true, 'storage_key' => $uploaded['storage_key'], 'type' => 'cover_image', 'lang' => $lang]);
    //         }

    //         if ($type === 'lesson_audio' && $request->hasFile('file')) {
    //             $request->validate(['file' => 'required|file|mimes:mp3,wav,m4a,aac,ogg,opus,mp4|max:51200']);
    //             $file = $request->file('file');
    //             $audioDuration = $this->getAudioDuration($file->getPathname());
    //             $uploaded = $this->fileStorage->uploadJornadaFile($file, $id, $lang, "lesson_{$lessonIndex}");
    //             return response()->json(['success' => true, 'storage_key' => $uploaded['storage_key'], 'audio_duration' => $audioDuration, 'type' => 'lesson_audio', 'lang' => $lang, 'lesson_index' => $lessonIndex]);
    //         }

    //         return response()->json(['success' => false, 'message' => 'No valid file provided'], 422);
    //     } catch (\Exception $e) {
    //         \Log::error('Jornada uploadFile exception', ['lang' => $lang, 'type' => $type, 'error' => $e->getMessage()]);
    //         return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
    //     }
    // }

    public function uploadFile(Request $request, string $id)
    {
        $request->validate([
            'file_name' => 'required',
            'file_type' => 'required',
            'lang' => 'required'
        ]);

        $fileName = time() . '_' . $request->file_name;

        $key = "jornadas/{$id}/{$request->lang}/{$fileName}";

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
    }

    /**
     * Step 2b: Save one language's translation + lessons with pre-uploaded file keys.
     */
    public function storeLang(Request $request, string $id)
    {
        $lang = $request->input('lang');
        if (!in_array($lang, ['pt', 'en', 'es'])) {
            return response()->json(['success' => false, 'message' => 'Invalid lang'], 422);
        }

        $lessonDocId = $request->input('lesson_doc_id');
        $isEdit = $request->boolean('is_edit');

        $request->validate([
            "title_{$lang}"       => 'required|string|max:255',
            "description_{$lang}" => 'required|string',
        ], [
            "title_{$lang}.required"       => __('common.required_field'),
            "description_{$lang}.required" => __('common.required_field'),
        ]);

        try {
            $newCoverKey      = $request->input("cover_image_key_{$lang}", '');
            $existingCoverKey = $request->input("existing_cover_image_{$lang}", '');
            $coverImageKey    = $newCoverKey ?: ($isEdit ? $existingCoverKey : '');

            $transFields = [
                'title'       => ['stringValue' => (string) $request->input("title_{$lang}")],
                'description' => ['stringValue' => (string) $request->input("description_{$lang}")],
                'image'       => ['stringValue' => $coverImageKey],
            ];
            $this->firestore->saveJornadaTranslation($id, $lang, $transFields);

            $lessonCount  = (int) $request->input("lesson_count_{$lang}", 0);
            $lessonsArray = [];

            for ($i = 0; $i < $lessonCount; $i++) {
                $newAudioKey      = $request->input("lesson_audio_key_{$lang}_{$i}", '');
                $newAudioDuration = $request->input("lesson_audio_duration_{$lang}_{$i}", '00:00');
                $existingAudioKey = $request->input("lesson_existing_audio_{$lang}_{$i}", '');
                $existingDuration = $request->input("lesson_existing_duration_{$lang}_{$i}", '00:00');

                $audioKey      = $newAudioKey ?: ($isEdit ? $existingAudioKey : '');
                $audioDuration = $newAudioKey ? $newAudioDuration : ($isEdit ? $existingDuration : '00:00');

                $lessonsArray[] = [
                    'mapValue' => [
                        'fields' => [
                            'index'            => ['integerValue' => (string) ($i + 1)],
                            'title'            => ['stringValue' => (string) $request->input("lesson_title_{$lang}_{$i}")],
                            'description'      => ['stringValue' => (string) $request->input("lesson_description_{$lang}_{$i}")],
                            'audio_path'       => ['stringValue' => $audioKey],
                            'audioDuration'    => ['stringValue' => $audioDuration],
                            'subscribers_only' => ['booleanValue' => $request->input("lesson_subscribers_only_{$lang}_{$i}") === '1'],
                        ]
                    ]
                ];

                // Clean up replaced audio
                if ($isEdit && $existingAudioKey && $newAudioKey && $existingAudioKey !== $newAudioKey) {
                    try { Storage::disk('r2')->delete($existingAudioKey); } catch (\Exception $e) {}
                }
            }

            if ($lessonDocId) {
                $this->firestore->saveLessonTranslation($id, $lessonDocId, $lang, [
                    'lessons' => ['arrayValue' => ['values' => $lessonsArray]]
                ]);
            }

            // Clean up replaced cover image
            if ($isEdit && $existingCoverKey && $newCoverKey && $existingCoverKey !== $newCoverKey) {
                try { Storage::disk('r2')->delete($existingCoverKey); } catch (\Exception $e) {}
            }

            return response()->json(['success' => true, 'lang' => $lang]);
        } catch (\Exception $e) {
            \Log::error('Jornada storeLang exception', ['lang' => $lang, 'message' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => __('common.error') . ': ' . $e->getMessage()], 500);
        }
    }

    // ── Comment Moderation ─────────────────────────────────────────────────

    public function jornadaComments(string $id)
    {
        // Get jornada title for display
        $jornadaData = $this->firestore->getJornada($id);
        $translations = $jornadaData['translations'] ?? [];
        $jornadaTitle = $translations['pt']['title']['stringValue']
            ?? $translations['en']['title']['stringValue']
            ?? $translations['es']['title']['stringValue']
            ?? $id;

        // Get all lesson IDs
        $lessonIds = $this->firestore->getJornadaLessonIds($id);

        if (empty($lessonIds)) {
            return view('pages.jornadas.comments', compact('id', 'jornadaTitle'))->with('comments', []);
        }

        // Fetch comments for all lessons
        $allRaw = [];
        foreach ($lessonIds as $index => $lessonId) {
            $lessonComments = $this->firestore->getJornadaLessonComments($id, $lessonId);
            foreach ($lessonComments as $commentId => $fields) {
                $allRaw[] = [
                    'id'           => $commentId,
                    'lessonId'     => $lessonId,
                    'lessonNumber' => $index + 1,
                    'fields'       => $fields,
                ];
            }
        }

        // Collect unique user IDs
        $allUserIds = array_unique(array_filter(array_map(
            fn($c) => $c['fields']['userId']['stringValue'] ?? '',
            $allRaw
        )));

        $userInfoMap = [];
        foreach ($allUserIds as $uid) {
            if (!$uid) continue;
            $uf  = $this->firestore->getFirestoreUser($uid);
            $uTz = $uf['timezone']['stringValue'] ?? 'UTC';
            $uUpd = $uf['updatedAt']['timestampValue'] ?? '';
            $uUpdFmt = '';
            if ($uUpd) {
                try { $uUpdFmt = \Carbon\Carbon::parse($uUpd)->setTimezone($uTz)->format('d/m/Y H:i'); } catch (\Exception $e) { $uUpdFmt = \Carbon\Carbon::parse($uUpd)->format('d/m/Y H:i'); }
            }
            $userInfoMap[$uid] = [
                'isBlocked' => $uf['isBlocked']['booleanValue'] ?? false,
                'email'     => $uf['email']['stringValue'] ?? '',
                'updatedAt' => $uUpdFmt,
                'timezone'  => $uTz,
            ];
        }

        // Build final comments array
        $comments = [];
        foreach ($allRaw as $c) {
            $f      = $c['fields'];
            $userId = $f['userId']['stringValue'] ?? '';
            $comments[] = [
                'id'               => $c['id'],
                'lessonId'         => $c['lessonId'],
                'lessonNumber'     => $c['lessonNumber'],
                'userId'           => $userId,
                'userName'         => $f['userName']['stringValue'] ?? '',
                'userEmail'        => $userInfoMap[$userId]['email'] ?? '',
                'userUpdatedAt'    => $userInfoMap[$userId]['updatedAt'] ?? '',
                'userTimezone'     => $userInfoMap[$userId]['timezone'] ?? '',
                'message'          => $f['message']['stringValue'] ?? '',
                'createdAt'        => $f['createdAt']['timestampValue'] ?? '',
                'isSubscribedUser' => $f['isSubscribedUser']['booleanValue'] ?? false,
                'likes'            => (int) ($f['likes']['integerValue'] ?? 0),
                'isBlocked'        => $userInfoMap[$userId]['isBlocked'] ?? false,
                'isHidden'         => $f['isHidden']['booleanValue'] ?? false,
                'isProhibitedWord' => $f['isProhibitedWord']['booleanValue'] ?? false,
                'isReported'       => $f['isReported']['booleanValue'] ?? false,
            ];
        }

        usort($comments, fn($a, $b) => strcmp($b['createdAt'], $a['createdAt']));

        return view('pages.jornadas.comments', compact('id', 'jornadaTitle', 'comments'));
    }

    public function deleteJornadaComment(string $id, string $lessonId, string $commentId)
    {
        $success = $this->firestore->deleteJornadaComment($id, $lessonId, $commentId);
        return response()->json(['success' => $success]);
    }

    public function hideJornadaComment(string $id, string $lessonId, string $commentId)
    {
        $success = $this->firestore->hideJornadaComment($id, $lessonId, $commentId);
        return response()->json(['success' => $success]);
    }

    public function approveJornadaComment(string $id, string $lessonId, string $commentId)
    {
        $success = $this->firestore->approveJornadaComment($id, $lessonId, $commentId);
        return response()->json(['success' => $success]);
    }

    // ---- Helpers ----

    private function getEnabledLanguages(Request $request): array
    {
        $langs = ['pt'];
        if ($request->boolean('lang_enabled_en')) $langs[] = 'en';
        if ($request->boolean('lang_enabled_es')) $langs[] = 'es';
        return $langs;
    }

    public function reorder(Request $request)
    {
        try {
            $items = $request->input('items', []);
            foreach ($items as $item) {
                $this->firestore->updateJornadaOrder($item['id'], (int) $item['order']);
            }
            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            \Log::error('Jornada reorder exception', ['message' => $e->getMessage()]);
            return response()->json(['success' => false], 500);
        }
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
}
