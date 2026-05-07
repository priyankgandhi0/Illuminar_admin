<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Kreait\Firebase\Factory;

class FirestoreService
{
    protected Factory $factory;
    protected string $projectId;

    public function __construct()
    {
        $this->projectId = config('firebase.project_id') ?: env('FIREBASE_PROJECT_ID', '');

        $this->factory = (new Factory)
            ->withServiceAccount(storage_path('app/firebase/service-account.json'))
            ->withProjectId($this->projectId);
    }

    public function findActiveAdminByEmail(string $email): ?array
    {
        $token = GoogleAccessTokenService::generate();

        $projectId = $this->projectId;

        $url = "https://firestore.googleapis.com/v1/projects/{$projectId}/databases/(default)/documents:runQuery";

        $response = Http::withToken($token)
            ->timeout(10)
            ->post($url, [
                'structuredQuery' => [
                    'from' => [
                        ['collectionId' => 'admins']
                    ],
                    'where' => [
                        'compositeFilter' => [
                            'op' => 'AND',
                            'filters' => [
                                [
                                    'fieldFilter' => [
                                        'field' => ['fieldPath' => 'email'],
                                        'op'    => 'EQUAL',
                                        'value' => ['stringValue' => strtolower($email)]
                                    ]
                                ],
                                [
                                    'fieldFilter' => [
                                        'field' => ['fieldPath' => 'isActive'],
                                        'op'    => 'EQUAL',
                                        'value' => ['booleanValue' => true]
                                    ]
                                ],
                                [
                                    'fieldFilter' => [
                                        'field' => ['fieldPath' => 'isDeleted'],
                                        'op'    => 'EQUAL',
                                        'value' => ['booleanValue' => false]
                                    ]
                                ]
                            ]
                        ]
                    ],
                    'limit' => 1
                ]
            ]);

        if (!$response->successful()) {
            \Log::error('Firestore findActiveAdminByEmail failed', [
                'status' => $response->status(),
                'body'   => substr($response->body(), 0, 300),
            ]);
            return null;
        }

        foreach ($response->json() as $row) {
            if (!isset($row['document'])) {
                continue;
            }

            $f = $row['document']['fields'];

            return [
                'id'       => basename($row['document']['name']),
                'email'    => $f['email']['stringValue'],
                'password' => $f['password']['stringValue'],
                'name'     => $f['fullName']['stringValue'],
            ];
        }

        return null;
    }

    public function getAuthUsers(): array
    {
        return cache()->remember('firebase_auth_users', 60, function () {

            $users = $this->factory->createAuth()->listUsers();
            $map = [];

            foreach ($users as $user) {
                $createdAtRaw = $user->metadata->createdAt;

                $createdAt = $createdAtRaw instanceof \DateTimeInterface
                    ? $createdAtRaw->getTimestamp() * 1000
                    : (int) $createdAtRaw;

                $map[$user->uid] = [
                    'uid'       => $user->uid,
                    'email'     => $user->email ?? '',
                    'disabled'  => (bool) $user->disabled,
                    'createdAt' => $createdAt,
                ];
            }

            return $map;
        });
    }

    public function getFirestoreUser(string $uid): ?array
    {
        $token = GoogleAccessTokenService::generate();
        $projectId = $this->projectId;

        $url = "https://firestore.googleapis.com/v1/projects/{$projectId}/databases/(default)/documents/users/{$uid}";

        $response = Http::withToken($token)->timeout(8)->get($url);

        if (!$response->successful()) {
            return null;
        }

        return $response->json('fields') ?? null;
    }

    public function batchGetUsers(array $userIds): array
    {
        if (empty($userIds)) return [];
        try {
            $token     = GoogleAccessTokenService::generate();
            $projectId = $this->projectId;
            $baseUrl   = "https://firestore.googleapis.com/v1/projects/{$projectId}/databases/(default)/documents/users";
            $responses = Http::pool(function ($pool) use ($userIds, $token, $baseUrl) {
                foreach ($userIds as $uid) {
                    $pool->as($uid)->withToken($token)->timeout(10)->get("{$baseUrl}/{$uid}");
                }
            });
            $result = [];
            foreach ($responses as $uid => $response) {
                if (!($response instanceof \Throwable) && $response->successful()) {
                    $result[$uid] = $response->json('fields') ?? [];
                }
            }
            return $result;
        } catch (\Exception $e) {
            \Log::error('batchGetUsers failed', ['error' => $e->getMessage()]);
            return [];
        }
    }

    public function getFirestoreUsersMap(): array
    {
        $token = GoogleAccessTokenService::generate();
        $projectId = $this->projectId;

        $url = "https://firestore.googleapis.com/v1/projects/{$projectId}/databases/(default)/documents/users";

        $response = Http::withToken($token)->timeout(8)->get($url);

        if (!$response->successful()) {
            return [];
        }

        $map = [];

        foreach ($response->json('documents') ?? [] as $doc) {
            $f = $doc['fields'] ?? [];
            $uid = $f['uid']['stringValue'] ?? basename($doc['name']);
            $map[$uid] = $f;
        }

        return $map;
    }

    public function createUser(array $data)
    {
        $token = GoogleAccessTokenService::generate();
        $projectId = $this->projectId;

        $url = "https://firestore.googleapis.com/v1/projects/{$projectId}/databases/(default)/documents/users";

        $response = Http::withToken($token)->post($url, [
            'fields' => [
                'userName' => ['stringValue' => $data['userName']]
            ]
        ]);

        return $response->json();
    }

    public function createUserFile(array $data)
    {
        $token = GoogleAccessTokenService::generate();
        $projectId = $this->projectId;

        $url = "https://firestore.googleapis.com/v1/projects/{$projectId}/databases/(default)/documents/userFiles";

        return Http::withToken($token)->post($url, [
            'fields' => [
                'user_id' => ['stringValue' => $data['user_id']],
                'storage_key' => ['stringValue' => $data['storage_key']],
                'file_name' => ['stringValue' => $data['file_name']],
                'file_type' => ['stringValue' => $data['file_type']],
                'file_size' => ['integerValue' => $data['file_size']],
                'created_at' => ['timestampValue' => now()->toIso8601String()]
            ]
        ]);
    }
public function getUserFilesMap(): array
{
    $token = GoogleAccessTokenService::generate();
    $projectId = $this->projectId;

    $url = "https://firestore.googleapis.com/v1/projects/{$projectId}/databases/(default)/documents/userFiles";

    $response = \Illuminate\Support\Facades\Http::withToken($token)
        ->timeout(8)
        ->get($url);

    if (!$response->successful()) {
        return [];
    }

    $map = [];

    foreach ($response->json('documents') ?? [] as $doc) {

        $fields = $doc['fields'] ?? [];

        $userId = $fields['user_id']['stringValue'] ?? null;

        if ($userId) {
            $map[$userId] = $fields;
        }
    }

    return $map;
}

    // ---- Daily Light (light_content) Methods ----

    private function dlBaseUrl(): string
    {
        $projectId = $this->projectId;
        return "https://firestore.googleapis.com/v1/projects/{$projectId}/databases/(default)/documents/light_content";
    }

    public function getDailyLights(): array
    {
        $token     = GoogleAccessTokenService::generate();
        $queryUrl  = "https://firestore.googleapis.com/v1/projects/{$this->projectId}/databases/(default)/documents:runQuery";
        $dlBaseUrl = $this->dlBaseUrl();

        // Fire both requests concurrently — total wait = max(t1, t2) instead of t1 + t2
        $responses = Http::pool(function (\Illuminate\Http\Client\Pool $pool) use ($token, $queryUrl, $dlBaseUrl) {
            $pool->as('main')->withToken($token)->timeout(10)->get($dlBaseUrl);
            $pool->as('trans')->withToken($token)->timeout(15)->post($queryUrl, [
                'structuredQuery' => [
                    'from'   => [['collectionId' => 'translations', 'allDescendants' => true]],
                    'select' => ['fields' => [['fieldPath' => 'title']]],
                ],
            ]);
        });

        $mainResp  = $responses['main'];
        $transResp = $responses['trans'];

        if (!$mainResp->successful()) {
            return [];
        }

        $items = [];
        foreach ($mainResp->json('documents') ?? [] as $doc) {
            $id     = basename($doc['name']);
            $fields = $doc['fields'] ?? [];
            if (($fields['isDeleted']['booleanValue'] ?? false) === true) {
                continue;
            }
            $items[$id] = ['fields' => $fields, 'ptTitle' => '', 'languages' => []];
        }

        if (empty($items)) {
            return [];
        }

        if ($transResp->successful()) {
            foreach ($transResp->json() ?? [] as $result) {
                if (!isset($result['document']['name'])) {
                    continue;
                }
                $name = $result['document']['name'];
                if (!preg_match('/\/light_content\/([^\/]+)\/translations\/([^\/]+)$/', $name, $m)) {
                    continue;
                }
                [$_, $docId, $lang] = $m;
                if (!isset($items[$docId])) {
                    continue;
                }
                $items[$docId]['languages'][] = $lang;
                if ($lang === 'pt') {
                    $items[$docId]['ptTitle'] = $result['document']['fields']['title']['stringValue'] ?? '';
                }
            }
        }

        return $items;
    }

    public function getDailyLight(string $id): ?array
    {
        $token = GoogleAccessTokenService::generate();
        $response = Http::withToken($token)->timeout(8)->get($this->dlBaseUrl() . "/{$id}");

        if (!$response->successful()) {
            return null;
        }

        $mainFields = $response->json('fields') ?? [];

        // Fetch all translations from subcollection
        $transUrl = $this->dlBaseUrl() . "/{$id}/translations";
        $transResp = Http::withToken($token)->timeout(8)->get($transUrl);

        $translations = [];
        if ($transResp->successful()) {
            foreach ($transResp->json('documents') ?? [] as $doc) {
                $lang = basename($doc['name']);
                $translations[$lang] = $doc['fields'] ?? [];
            }
        }

        return [
            'main' => $mainFields,
            'translations' => $translations,
        ];
    }

    public function saveDailyLight(string $docId, array $mainFields, array $translations): array
    {
        $token = GoogleAccessTokenService::generate();

        // Upsert main document using PATCH
        $response = Http::withToken($token)->patch($this->dlBaseUrl() . "/{$docId}", [
            'fields' => $mainFields
        ]);

        if (!$response->successful()) {
            return [
                'success' => false,
                'error' => 'Main doc: ' . $response->status() . ' - ' . substr($response->body(), 0, 200),
            ];
        }

        // Upsert each translation subcollection document
        foreach ($translations as $lang => $langFields) {
            $langResp = Http::withToken($token)->patch(
                $this->dlBaseUrl() . "/{$docId}/translations/{$lang}",
                ['fields' => $langFields]
            );

            if (!$langResp->successful()) {
                return [
                    'success' => false,
                    'error' => "Translation ({$lang}): " . $langResp->status() . ' - ' . substr($langResp->body(), 0, 200),
                ];
            }
        }

        return ['success' => true];
    }

    public function saveDailyLightTranslation(string $docId, string $lang, array $langFields): array
    {
        $token = GoogleAccessTokenService::generate();

        $response = Http::withToken($token)->timeout(120)->patch(
            $this->dlBaseUrl() . "/{$docId}/translations/{$lang}",
            ['fields' => $langFields]
        );

        if (!$response->successful()) {
            return [
                'success' => false,
                'error' => "Translation ({$lang}): " . $response->status() . ' - ' . substr($response->body(), 0, 200),
            ];
        }

        return ['success' => true];
    }

    public function updateDailyLightStatus(string $docId, string $status): bool
    {
        $token = GoogleAccessTokenService::generate();

        $response = Http::withToken($token)->patch(
            $this->dlBaseUrl() . "/{$docId}?updateMask.fieldPaths=status",
            ['fields' => ['status' => ['stringValue' => $status]]]
        );

        return $response->successful();
    }

    public function deleteDailyLight(string $id): bool
    {
        $token = GoogleAccessTokenService::generate();
        $base  = $this->dlBaseUrl() . "/{$id}";

        // 1. Delete translations subcollection
        $transResp = Http::withToken($token)->timeout(8)->get("{$base}/translations");
        if ($transResp->successful()) {
            foreach ($transResp->json('documents') ?? [] as $doc) {
                Http::withToken($token)->delete("https://firestore.googleapis.com/v1/{$doc['name']}");
            }
        }

        // 2. Delete comment subcollection (each comment document)
        $commentResp = Http::withToken($token)->timeout(10)->get("{$base}/comment");
        if ($commentResp->successful()) {
            foreach ($commentResp->json('documents') ?? [] as $doc) {
                Http::withToken($token)->delete("https://firestore.googleapis.com/v1/{$doc['name']}");
            }
        }

        // 3. Delete main document (also removes the `likes` field it contains)
        $response = Http::withToken($token)->delete($base);
        return $response->successful();
    }

    public function softDeleteDailyLight(string $id): bool
    {
        $token = GoogleAccessTokenService::generate();
        $response = Http::withToken($token)->patch(
            $this->dlBaseUrl() . "/{$id}?updateMask.fieldPaths=isDeleted",
            ['fields' => ['isDeleted' => ['booleanValue' => true]]]
        );
        return $response->successful();
    }

    public function deleteAllTranslations(string $docId): void
    {
        $token = GoogleAccessTokenService::generate();
        $transUrl = $this->dlBaseUrl() . "/{$docId}/translations";
        $transResp = Http::withToken($token)->timeout(8)->get($transUrl);

        if ($transResp->successful()) {
            foreach ($transResp->json('documents') ?? [] as $doc) {
                $docPath = $doc['name'];
                Http::withToken($token)->delete("https://firestore.googleapis.com/v1/{$docPath}");
            }
        }

    }

    // ---- User Management Methods ----

    public function updateUserField(string $uid, string $field, array $value): bool
    {
        $token = GoogleAccessTokenService::generate();
        $projectId = $this->projectId;

        $url = "https://firestore.googleapis.com/v1/projects/{$projectId}/databases/(default)/documents/users/{$uid}?updateMask.fieldPaths={$field}";

        $response = Http::withToken($token)->patch($url, [
            'fields' => [$field => $value]
        ]);

        return $response->successful();
    }

    public function updateUserFields(string $uid, array $fields): bool
    {
        $token = GoogleAccessTokenService::generate();
        $projectId = $this->projectId;

        $queryParams = collect(array_keys($fields))
            ->map(fn($f) => "updateMask.fieldPaths={$f}")
            ->implode('&');

        $url = "https://firestore.googleapis.com/v1/projects/{$projectId}/databases/(default)/documents/users/{$uid}?{$queryParams}";

        $response = Http::withToken($token)->patch($url, [
            'fields' => $fields
        ]);

        return $response->successful();
    }

    public function toggleUserActive(string $uid, bool $isActive): bool
    {
        return $this->updateUserField($uid, 'isActive', ['booleanValue' => $isActive]);
    }

    public function softDeleteUser(string $uid): bool
    {
        return $this->updateUserField($uid, 'isDeleted', ['booleanValue' => true]);
    }

    public function hardDeleteUser(string $uid): bool
    {
        $token = GoogleAccessTokenService::generate();
        $projectId = $this->projectId;

        $url = "https://firestore.googleapis.com/v1/projects/{$projectId}/databases/(default)/documents/users/{$uid}";
        $response = Http::withToken($token)->delete($url);
        return $response->successful();
    }

    public function deleteFirebaseAuthUser(string $uid): bool
    {
        try {
            $this->factory->createAuth()->deleteUser($uid);
            return true;
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Firebase Auth delete failed', ['uid' => $uid, 'error' => $e->getMessage()]);
            return false;
        }
    }

    public function deleteDailyLightTranslation(string $docId, string $lang): bool
    {
        $token = GoogleAccessTokenService::generate();
        $response = Http::withToken($token)->delete($this->dlBaseUrl() . "/{$docId}/translations/{$lang}");
        return $response->successful();
    }

    public function toggleDailyLightFeatured(string $id, bool $isFeatured): bool
    {
        $token = GoogleAccessTokenService::generate();
        $response = Http::withToken($token)->patch(
            $this->dlBaseUrl() . "/{$id}?updateMask.fieldPaths=isFeatured",
            ['fields' => ['isFeatured' => ['booleanValue' => $isFeatured]]]
        );
        return $response->successful();
    }

    public function dailyLightExists(string $docId): bool
    {
        $token = GoogleAccessTokenService::generate();
        $response = Http::withToken($token)->timeout(5)->get($this->dlBaseUrl() . "/{$docId}");

        if (!$response->successful()) {
            return false;
        }

        // Treat soft-deleted as not existing
        $fields = $response->json('fields') ?? [];
        if (($fields['isDeleted']['booleanValue'] ?? false) === true) {
            return false;
        }

        return true;
    }

    // ---- Jornada Methods ----

    private function jnBaseUrl(): string
    {
        $projectId = $this->projectId;
        return "https://firestore.googleapis.com/v1/projects/{$projectId}/databases/(default)/documents/jornadas";
    }

    public function getJornadas(): array
    {
        $token     = GoogleAccessTokenService::generate();
        $queryUrl  = "https://firestore.googleapis.com/v1/projects/{$this->projectId}/databases/(default)/documents:runQuery";
        $jnBaseUrl = $this->jnBaseUrl();

        // Fire both requests concurrently
        $responses = Http::pool(function (\Illuminate\Http\Client\Pool $pool) use ($token, $queryUrl, $jnBaseUrl) {
            $pool->as('main')->withToken($token)->timeout(10)->get($jnBaseUrl);
            $pool->as('trans')->withToken($token)->timeout(15)->post($queryUrl, [
                'structuredQuery' => [
                    'from'   => [['collectionId' => 'translations', 'allDescendants' => true]],
                    'select' => ['fields' => [['fieldPath' => 'title']]],
                ],
            ]);
        });

        $mainResp  = $responses['main'];
        $transResp = $responses['trans'];

        if (!$mainResp->successful()) {
            return [];
        }

        $items = [];
        foreach ($mainResp->json('documents') ?? [] as $doc) {
            $id     = basename($doc['name']);
            $fields = $doc['fields'] ?? [];
            $items[$id] = ['fields' => $fields, 'ptTitle' => '', 'languages' => []];
        }

        if (empty($items)) {
            return [];
        }

        if ($transResp->successful()) {
            foreach ($transResp->json() ?? [] as $result) {
                if (!isset($result['document']['name'])) {
                    continue;
                }
                $name = $result['document']['name'];
                if (!preg_match('/\/jornadas\/([^\/]+)\/translations\/([^\/]+)$/', $name, $m)) {
                    continue;
                }
                [$_, $docId, $lang] = $m;
                if (!isset($items[$docId])) {
                    continue;
                }
                $items[$docId]['languages'][] = $lang;
                if ($lang === 'pt') {
                    $items[$docId]['ptTitle'] = $result['document']['fields']['title']['stringValue'] ?? '';
                }
            }
        }

        return $items;
    }

    public function getJornada(string $id): ?array
    {
        $token = GoogleAccessTokenService::generate();

        $response = Http::withToken($token)->timeout(8)->get($this->jnBaseUrl() . "/{$id}");
        if (!$response->successful()) {
            return null;
        }
        $mainFields = $response->json('fields') ?? [];

        // Get jornada translations
        $transResp = Http::withToken($token)->timeout(8)
            ->get($this->jnBaseUrl() . "/{$id}/translations");
        $translations = [];
        if ($transResp->successful()) {
            foreach ($transResp->json('documents') ?? [] as $doc) {
                $lang = basename($doc['name']);
                $translations[$lang] = $doc['fields'] ?? [];
            }
        }

        // Get lesson docs from subcollection
        $lessonsResp = Http::withToken($token)->timeout(8)
            ->get($this->jnBaseUrl() . "/{$id}/lessons");
        $lessonsByLang = [];
        $lessonDocId = null;

        if ($lessonsResp->successful()) {
            $lessonDocs = $lessonsResp->json('documents') ?? [];

            if (count($lessonDocs) > 0) {
                $firstDocId = basename($lessonDocs[0]['name']);

                // Fetch first doc's translations to detect format
                $ltResp = Http::withToken($token)->timeout(5)
                    ->get($this->jnBaseUrl() . "/{$id}/lessons/{$firstDocId}/translations");
                $ltDocs = $ltResp->successful() ? ($ltResp->json('documents') ?? []) : [];

                // Check if array format (new: lessons field in translation)
                $isArrayFormat = false;
                foreach ($ltDocs as $ltDoc) {
                    if (isset(($ltDoc['fields'] ?? [])['lessons'])) {
                        $isArrayFormat = true;
                        break;
                    }
                }

                if ($isArrayFormat) {
                    // New format: ONE lesson doc with lessons array in translations
                    $lessonDocId = $firstDocId;
                    foreach ($ltDocs as $ltDoc) {
                        $lang = basename($ltDoc['name']);
                        $ltFields = $ltDoc['fields'] ?? [];
                        $values = $ltFields['lessons']['arrayValue']['values'] ?? [];
                        foreach ($values as $item) {
                            $map = $item['mapValue']['fields'] ?? [];
                            $lessonsByLang[$lang][] = [
                                'title'            => $map['title']['stringValue'] ?? '',
                                'description'      => $map['description']['stringValue'] ?? '',
                                'audio_path'       => $map['audio_path']['stringValue'] ?? '',
                                'audioDuration'    => $map['audioDuration']['stringValue'] ?? '',
                                'subscribers_only' => $map['subscribers_only']['booleanValue'] ?? false,
                            ];
                        }
                    }
                } else {
                    // Old format: multiple lesson docs with flat translations
                    $lf = $lessonDocs[0]['fields'] ?? [];
                    $order = (int) ($lf['order']['integerValue'] ?? 0);
                    foreach ($ltDocs as $ltDoc) {
                        $lang = basename($ltDoc['name']);
                        $ltFields = $ltDoc['fields'] ?? [];
                        $lessonsByLang[$lang][] = [
                            'order'            => $order,
                            'title'            => $ltFields['title']['stringValue'] ?? $ltFields['tittle']['stringValue'] ?? '',
                            'description'      => $ltFields['description']['stringValue'] ?? '',
                            'audio_path'       => $ltFields['audio_path']['stringValue'] ?? '',
                            'audioDuration'    => $ltFields['audioDuration']['stringValue'] ?? '',
                            'subscribers_only' => $ltFields['subscribers_only']['booleanValue'] ?? false,
                        ];
                    }

                    for ($di = 1; $di < count($lessonDocs); $di++) {
                        $lid = basename($lessonDocs[$di]['name']);
                        $lf = $lessonDocs[$di]['fields'] ?? [];
                        $order = (int) ($lf['order']['integerValue'] ?? 0);

                        $ltResp2 = Http::withToken($token)->timeout(5)
                            ->get($this->jnBaseUrl() . "/{$id}/lessons/{$lid}/translations");
                        if ($ltResp2->successful()) {
                            foreach ($ltResp2->json('documents') ?? [] as $ltDoc) {
                                $lang = basename($ltDoc['name']);
                                $ltFields = $ltDoc['fields'] ?? [];
                                $lessonsByLang[$lang][] = [
                                    'order'            => $order,
                                    'title'            => $ltFields['title']['stringValue'] ?? $ltFields['tittle']['stringValue'] ?? '',
                                    'description'      => $ltFields['description']['stringValue'] ?? '',
                                    'audio_path'       => $ltFields['audio_path']['stringValue'] ?? '',
                                    'audioDuration'    => $ltFields['audioDuration']['stringValue'] ?? '',
                                    'subscribers_only' => $ltFields['subscribers_only']['booleanValue'] ?? false,
                                ];
                            }
                        }
                    }

                    foreach ($lessonsByLang as &$lessons) {
                        usort($lessons, fn($a, $b) => ($a['order'] ?? 0) - ($b['order'] ?? 0));
                    }
                    unset($lessons);
                }
            }
        }

        return [
            'main' => $mainFields,
            'translations' => $translations,
            'lessons_by_lang' => $lessonsByLang,
            'lesson_doc_id' => $lessonDocId,
        ];
    }

    public function createJornada(array $mainFields): array
    {
        $token = GoogleAccessTokenService::generate();

        $response = Http::withToken($token)->post($this->jnBaseUrl(), [
            'fields' => $mainFields
        ]);

        if (!$response->successful()) {
            return ['success' => false, 'error' => 'Main doc: ' . $response->status()];
        }

        $docId = basename($response->json('name'));

        Http::withToken($token)->patch(
            $this->jnBaseUrl() . "/{$docId}?updateMask.fieldPaths=id",
            ['fields' => ['id' => ['stringValue' => $docId]]]
        );

        return ['success' => true, 'docId' => $docId];
    }

    public function updateJornadaOrder(string $id, int $order): bool
    {
        $token = GoogleAccessTokenService::generate();
        $response = Http::withToken($token)->patch(
            $this->jnBaseUrl() . "/{$id}?updateMask.fieldPaths=order",
            ['fields' => ['order' => ['integerValue' => $order]]]
        );
        return $response->successful();
    }

    public function updateJornada(string $docId, array $mainFields, array $translations): array
    {
        $token = GoogleAccessTokenService::generate();

        $response = Http::withToken($token)->patch(
            $this->jnBaseUrl() . "/{$docId}",
            ['fields' => $mainFields]
        );

        if (!$response->successful()) {
            return ['success' => false, 'error' => 'Main doc: ' . $response->status()];
        }

        foreach ($translations as $lang => $langFields) {
            $langResp = Http::withToken($token)->patch(
                $this->jnBaseUrl() . "/{$docId}/translations/{$lang}",
                ['fields' => $langFields]
            );
            if (!$langResp->successful()) {
                return ['success' => false, 'error' => "Translation ({$lang}): " . $langResp->status()];
            }
        }

        return ['success' => true];
    }

    public function saveJornadaTranslation(string $docId, string $lang, array $fields): bool
    {
        $token = GoogleAccessTokenService::generate();
        $response = Http::withToken($token)->patch(
            $this->jnBaseUrl() . "/{$docId}/translations/{$lang}",
            ['fields' => $fields]
        );
        return $response->successful();
    }

    public function createLesson(string $jornadaId, array $lessonFields): array
    {
        $token = GoogleAccessTokenService::generate();

        $response = Http::withToken($token)->post(
            $this->jnBaseUrl() . "/{$jornadaId}/lessons",
            ['fields' => $lessonFields]
        );

        if (!$response->successful()) {
            return ['success' => false, 'error' => $response->status()];
        }

        $lessonId = basename($response->json('name'));

        Http::withToken($token)->patch(
            $this->jnBaseUrl() . "/{$jornadaId}/lessons/{$lessonId}?updateMask.fieldPaths=lesson_id",
            ['fields' => ['lesson_id' => ['stringValue' => $lessonId]]]
        );

        return ['success' => true, 'lessonId' => $lessonId];
    }

    public function updateLesson(string $jornadaId, string $lessonId, array $lessonFields): bool
    {
        $token = GoogleAccessTokenService::generate();

        // Build updateMask so only specified fields are updated (not replace all)
        $fieldPaths = array_keys($lessonFields);
        $maskParams = implode('&', array_map(fn($f) => "updateMask.fieldPaths={$f}", $fieldPaths));

        $response = Http::withToken($token)->patch(
            $this->jnBaseUrl() . "/{$jornadaId}/lessons/{$lessonId}?{$maskParams}",
            ['fields' => $lessonFields]
        );
        return $response->successful();
    }

    public function saveLessonTranslation(string $jornadaId, string $lessonId, string $lang, array $fields): bool
    {
        $token = GoogleAccessTokenService::generate();
        $response = Http::withToken($token)->patch(
            $this->jnBaseUrl() . "/{$jornadaId}/lessons/{$lessonId}/translations/{$lang}",
            ['fields' => $fields]
        );
        return $response->successful();
    }

    public function deleteLesson(string $jornadaId, string $lessonId): bool
    {
        $token = GoogleAccessTokenService::generate();

        $transUrl = $this->jnBaseUrl() . "/{$jornadaId}/lessons/{$lessonId}/translations";
        $transResp = Http::withToken($token)->timeout(5)->get($transUrl);
        if ($transResp->successful()) {
            foreach ($transResp->json('documents') ?? [] as $doc) {
                Http::withToken($token)->delete("https://firestore.googleapis.com/v1/{$doc['name']}");
            }
        }

        $response = Http::withToken($token)->delete(
            $this->jnBaseUrl() . "/{$jornadaId}/lessons/{$lessonId}"
        );
        return $response->successful();
    }

    public function deleteAllLessons(string $jornadaId): void
    {
        $token = GoogleAccessTokenService::generate();
        $lessonsResp = Http::withToken($token)->timeout(8)
            ->get($this->jnBaseUrl() . "/{$jornadaId}/lessons");
        if ($lessonsResp->successful()) {
            foreach ($lessonsResp->json('documents') ?? [] as $lessonDoc) {
                $lessonId = basename($lessonDoc['name']);
                $ltResp = Http::withToken($token)->timeout(5)
                    ->get($this->jnBaseUrl() . "/{$jornadaId}/lessons/{$lessonId}/translations");
                if ($ltResp->successful()) {
                    foreach ($ltResp->json('documents') ?? [] as $ltDoc) {
                        Http::withToken($token)->delete("https://firestore.googleapis.com/v1/{$ltDoc['name']}");
                    }
                }
                Http::withToken($token)->delete("https://firestore.googleapis.com/v1/{$lessonDoc['name']}");
            }
        }
    }

    public function deleteJornada(string $id): bool
    {
        $token = GoogleAccessTokenService::generate();

        // Delete lesson subcollection (each lesson has its own translations)
        $lessonsResp = Http::withToken($token)->timeout(8)
            ->get($this->jnBaseUrl() . "/{$id}/lessons");
        if ($lessonsResp->successful()) {
            foreach ($lessonsResp->json('documents') ?? [] as $lessonDoc) {
                $lessonId = basename($lessonDoc['name']);
                $ltResp = Http::withToken($token)->timeout(5)
                    ->get($this->jnBaseUrl() . "/{$id}/lessons/{$lessonId}/translations");
                if ($ltResp->successful()) {
                    foreach ($ltResp->json('documents') ?? [] as $ltDoc) {
                        Http::withToken($token)->delete("https://firestore.googleapis.com/v1/{$ltDoc['name']}");
                    }
                }
                Http::withToken($token)->delete("https://firestore.googleapis.com/v1/{$lessonDoc['name']}");
            }
        }

        // Delete jornada translations
        $transResp = Http::withToken($token)->timeout(8)
            ->get($this->jnBaseUrl() . "/{$id}/translations");
        if ($transResp->successful()) {
            foreach ($transResp->json('documents') ?? [] as $doc) {
                Http::withToken($token)->delete("https://firestore.googleapis.com/v1/{$doc['name']}");
            }
        }

        // Delete main document
        $response = Http::withToken($token)->delete($this->jnBaseUrl() . "/{$id}");
        return $response->successful();
    }

    public function deleteJornadaTranslation(string $docId, string $lang): bool
    {
        $token = GoogleAccessTokenService::generate();
        $response = Http::withToken($token)->delete(
            $this->jnBaseUrl() . "/{$docId}/translations/{$lang}"
        );
        return $response->successful();
    }

    public function deleteLessonTranslation(string $jornadaId, string $lessonId, string $lang): bool
    {
        $token = GoogleAccessTokenService::generate();
        $response = Http::withToken($token)->delete(
            $this->jnBaseUrl() . "/{$jornadaId}/lessons/{$lessonId}/translations/{$lang}"
        );
        return $response->successful();
    }

    // ---- Jornada Category Methods ----

    private function jcBaseUrl(): string
    {
        $projectId = $this->projectId;
        return "https://firestore.googleapis.com/v1/projects/{$projectId}/databases/(default)/documents/jornadas_category";
    }

    public function getJornadaCategories(): array
    {
        $token = GoogleAccessTokenService::generate();
        $response = Http::withToken($token)->timeout(10)->get($this->jcBaseUrl());

        if (!$response->successful()) {
            return [];
        }

        $items = [];
        foreach ($response->json('documents') ?? [] as $doc) {
            $id = basename($doc['name']);
            $fields = $doc['fields'] ?? [];
            $items[$id] = $fields;
        }

        return $items;
    }

    public function getJornadaCategory(string $id): ?array
    {
        $token = GoogleAccessTokenService::generate();
        $response = Http::withToken($token)->timeout(8)->get($this->jcBaseUrl() . "/{$id}");

        if (!$response->successful()) {
            return null;
        }

        return $response->json('fields') ?? [];
    }

    public function createJornadaCategory(array $fields): array
    {
        $token = GoogleAccessTokenService::generate();
        $response = Http::withToken($token)->post($this->jcBaseUrl(), [
            'fields' => $fields
        ]);

        if (!$response->successful()) {
            return ['success' => false];
        }

        return ['success' => true, 'docId' => basename($response->json('name'))];
    }

    public function updateJornadaCategory(string $id, array $fields): bool
    {
        $token = GoogleAccessTokenService::generate();
        $response = Http::withToken($token)->patch(
            $this->jcBaseUrl() . "/{$id}",
            ['fields' => $fields]
        );
        return $response->successful();
    }

    public function updateCategoryOrder(string $id, int $order): bool
    {
        $token = GoogleAccessTokenService::generate();
        $response = Http::withToken($token)->patch(
            $this->jcBaseUrl() . "/{$id}?updateMask.fieldPaths=order",
            ['fields' => ['order' => ['integerValue' => $order]]]
        );
        return $response->successful();
    }

    public function categoryHasJornadas(string $categoryId): bool
    {
        $token = GoogleAccessTokenService::generate();
        $projectId = $this->projectId;

        $url = "https://firestore.googleapis.com/v1/projects/{$projectId}/databases/(default)/documents:runQuery";

        $response = Http::withToken($token)->timeout(10)->post($url, [
            'structuredQuery' => [
                'from' => [['collectionId' => 'jornadas']],
                'where' => [
                    'fieldFilter' => [
                        'field' => ['fieldPath' => 'category_id'],
                        'op' => 'EQUAL',
                        'value' => ['stringValue' => $categoryId]
                    ]
                ],
                'limit' => 1
            ]
        ]);

        if (!$response->successful()) {
            return false;
        }

        foreach ($response->json() as $row) {
            if (isset($row['document'])) {
                return true;
            }
        }

        return false;
    }

    public function deleteJornadaCategory(string $id): bool
    {
        $token = GoogleAccessTokenService::generate();
        $response = Http::withToken($token)->delete($this->jcBaseUrl() . "/{$id}");
        return $response->successful();
    }

    // ---- Daily Light Category Methods ----

    private function dlcBaseUrl(): string
    {
        $projectId = $this->projectId;
        return "https://firestore.googleapis.com/v1/projects/{$projectId}/databases/(default)/documents/daily_light_categories";
    }

    public function getDailyLightCategories(): array
    {
        $token = GoogleAccessTokenService::generate();
        $response = Http::withToken($token)->timeout(10)->get($this->dlcBaseUrl());

        if (!$response->successful()) {
            return [];
        }

        $items = [];
        foreach ($response->json('documents') ?? [] as $doc) {
            $id = basename($doc['name']);
            $fields = $doc['fields'] ?? [];
            $items[$id] = $fields;
        }

        return $items;
    }

    public function getDailyLightCategory(string $id): ?array
    {
        $token = GoogleAccessTokenService::generate();
        $response = Http::withToken($token)->timeout(8)->get($this->dlcBaseUrl() . "/{$id}");

        if (!$response->successful()) {
            return null;
        }

        return $response->json('fields') ?? [];
    }

    public function createDailyLightCategory(array $fields): array
    {
        $token = GoogleAccessTokenService::generate();
        $response = Http::withToken($token)->post($this->dlcBaseUrl(), [
            'fields' => $fields
        ]);

        if (!$response->successful()) {
            return ['success' => false];
        }

        return ['success' => true, 'docId' => basename($response->json('name'))];
    }

    public function updateDailyLightCategory(string $id, array $fields): bool
    {
        $token = GoogleAccessTokenService::generate();
        $response = Http::withToken($token)->patch(
            $this->dlcBaseUrl() . "/{$id}",
            ['fields' => $fields]
        );
        return $response->successful();
    }

    public function deleteDailyLightCategory(string $id): bool
    {
        $token = GoogleAccessTokenService::generate();
        $response = Http::withToken($token)->delete($this->dlcBaseUrl() . "/{$id}");
        return $response->successful();
    }

    public function categoryHasDailyLights(string $categoryId): bool
    {
        $token = GoogleAccessTokenService::generate();
        $projectId = $this->projectId;

        $url = "https://firestore.googleapis.com/v1/projects/{$projectId}/databases/(default)/documents:runQuery";

        $response = Http::withToken($token)->timeout(10)->post($url, [
            'structuredQuery' => [
                'from' => [['collectionId' => 'light_content']],
                'where' => [
                    'fieldFilter' => [
                        'field' => ['fieldPath' => 'category_ids'],
                        'op' => 'ARRAY_CONTAINS',
                        'value' => ['stringValue' => $categoryId]
                    ]
                ],
                'limit' => 1
            ]
        ]);

        if (!$response->successful()) {
            return false;
        }

        foreach ($response->json() as $row) {
            if (isset($row['document'])) {
                return true;
            }
        }

        return false;
    }

    public function patchDailyLightCategoryIds(string $docId, array $categoryIds): void
    {
        $token = GoogleAccessTokenService::generate();
        $values = array_values(array_map(
            fn($id) => ['stringValue' => $id],
            array_unique(array_filter($categoryIds))
        ));
        Http::withToken($token)->timeout(10)->patch(
            $this->dlBaseUrl() . "/{$docId}?updateMask.fieldPaths=category_ids",
            ['fields' => ['category_ids' => ['arrayValue' => ['values' => $values]]]]
        );
    }

    // ---- Scheduled Notifications ----

    private function scheduledNotifBaseUrl(): string
    {
        $projectId = $this->projectId;
        return "https://firestore.googleapis.com/v1/projects/{$projectId}/databases/(default)/documents/scheduled_notifications";
    }

    public function createScheduledNotification(array $fields): array
    {
        $token = GoogleAccessTokenService::generate();
        $response = Http::withToken($token)->post($this->scheduledNotifBaseUrl(), [
            'fields' => $fields
        ]);

        if (!$response->successful()) {
            return ['success' => false];
        }

        return ['success' => true, 'docId' => basename($response->json('name'))];
    }

    public function getPendingScheduledNotifications(): array
    {
        $token = GoogleAccessTokenService::generate();
        $projectId = $this->projectId;
        $url = "https://firestore.googleapis.com/v1/projects/{$projectId}/databases/(default)/documents:runQuery";

        $response = Http::withToken($token)->timeout(10)->post($url, [
            'structuredQuery' => [
                'from' => [['collectionId' => 'scheduled_notifications']],
                'where' => [
                    'fieldFilter' => [
                        'field' => ['fieldPath' => 'sent'],
                        'op' => 'EQUAL',
                        'value' => ['booleanValue' => false]
                    ]
                ],
                'limit' => 50
            ]
        ]);

        if (!$response->successful()) {
            return [];
        }

        $items = [];
        foreach ($response->json() as $row) {
            if (isset($row['document'])) {
                $id = basename($row['document']['name']);
                $items[$id] = $row['document']['fields'] ?? [];
            }
        }

        return $items;
    }

    public function markNotificationSent(string $id): bool
    {
        $token = GoogleAccessTokenService::generate();
        $response = Http::withToken($token)->patch(
            $this->scheduledNotifBaseUrl() . "/{$id}?updateMask.fieldPaths=sent&updateMask.fieldPaths=sentAt",
            ['fields' => [
                'sent' => ['booleanValue' => true],
                'sentAt' => ['timestampValue' => now()->toIso8601String()],
            ]]
        );
        return $response->successful();
    }

    // ---- Settings Methods ----

    private function settingsBaseUrl(): string
    {
        $projectId = $this->projectId;
        return "https://firestore.googleapis.com/v1/projects/{$projectId}/databases/(default)/documents/settings";
    }

    public function getSetting(string $key): ?array
    {
        $token = GoogleAccessTokenService::generate();
        $response = Http::withToken($token)->timeout(8)->get($this->settingsBaseUrl() . "/{$key}");

        if (!$response->successful()) {
            return null;
        }

        return $response->json('fields') ?? null;
    }

    public function setSetting(string $key, array $fields): bool
    {
        $token = GoogleAccessTokenService::generate();
        $response = Http::withToken($token)->patch(
            $this->settingsBaseUrl() . "/{$key}",
            ['fields' => $fields]
        );
        return $response->successful();
    }

    /**
     * Get sent tracking for daily light notifications on a given date.
     * Returns array of group keys that have already been sent (e.g. ['pt_m0300' => true]).
     */
    public function getDailySentTracking(string $date): array
    {
        $fields = $this->getSetting("daily_light_sent_{$date}");

        if (!$fields) {
            return [];
        }

        $sent = [];
        foreach ($fields as $key => $val) {
            if (($val['booleanValue'] ?? false) === true) {
                $sent[$key] = true;
            }
        }

        return $sent;
    }

    /**
     * Mark a notification group as sent for a given date.
     */
    public function markDailySentGroup(string $date, string $groupKey): bool
    {
        $token = GoogleAccessTokenService::generate();
        $url = $this->settingsBaseUrl() . "/daily_light_sent_{$date}?updateMask.fieldPaths={$groupKey}";
        $response = Http::withToken($token)->patch($url, [
            'fields' => [
                $groupKey => ['booleanValue' => true],
            ]
        ]);
        return $response->successful();
    }

    /**
     * Clear a sent notification group so it can be re-sent (e.g. after admin updates the time).
     */
    public function clearDailySentGroup(string $date, string $groupKey): bool
    {
        $token = GoogleAccessTokenService::generate();
        $url = $this->settingsBaseUrl() . "/daily_light_sent_{$date}?updateMask.fieldPaths={$groupKey}";
        $response = Http::withToken($token)->patch($url, [
            'fields' => [
                $groupKey => ['booleanValue' => false],
            ]
        ]);
        return $response->successful();
    }

    /**
     * Delete the entire sent-tracking document for a date (called when Daily Light is deleted).
     */
    public function deleteDailySentTracking(string $date): void
    {
        try {
            $token = GoogleAccessTokenService::generate();
            Http::withToken($token)->timeout(5)->delete($this->settingsBaseUrl() . "/daily_light_sent_{$date}");
        } catch (\Exception $e) {
            \Log::warning('deleteDailySentTracking failed', ['date' => $date, 'error' => $e->getMessage()]);
        }
    }

    // ---- Comment Methods ----

    public function getComments(string $date): array
    {
        try {
            $token = GoogleAccessTokenService::generate();
            $url = $this->dlBaseUrl() . "/{$date}/comment";
            $response = Http::withToken($token)->timeout(10)->get($url);
            if (!$response->successful()) return [];
            $docs = $response->json('documents') ?? [];
            $comments = [];
            foreach ($docs as $doc) {
                $parts = explode('/', $doc['name']);
                $id = end($parts);
                $comments[$id] = $doc['fields'] ?? [];
            }
            return $comments;
        } catch (\Exception $e) {
            \Log::error('getComments failed', ['date' => $date, 'error' => $e->getMessage()]);
            return [];
        }
    }

    public function deleteComment(string $date, string $commentId): bool
    {
        try {
            $token = GoogleAccessTokenService::generate();
            $url = $this->dlBaseUrl() . "/{$date}/comment/{$commentId}";
            $response = Http::withToken($token)->timeout(8)->delete($url);
            return $response->successful();
        } catch (\Exception $e) {
            \Log::error('deleteComment failed', ['date' => $date, 'commentId' => $commentId, 'error' => $e->getMessage()]);
            return false;
        }
    }

    public function deleteReply(string $date, string $commentId, string $replyId): bool
    {
        try {
            $token   = GoogleAccessTokenService::generate();
            $base    = $this->dlBaseUrl();
            $subUrl  = "{$base}/{$date}/comment/{$commentId}/replies/{$replyId}";

            // Try subcollection first
            $checkRes = Http::withToken($token)->timeout(8)->get($subUrl);
            if ($checkRes->successful() && isset($checkRes->json()['name'])) {
                $delRes = Http::withToken($token)->timeout(8)->delete($subUrl);
                return $delRes->successful();
            }

            // Fall back: old repliesList array in parent document
            $url = "{$base}/{$date}/comment/{$commentId}";
            $res = Http::withToken($token)->timeout(8)->get($url);
            if (!$res->successful()) return false;

            $fields     = $res->json('fields') ?? [];
            $repliesRaw = $fields['repliesList']['arrayValue']['values'] ?? [];
            $filtered   = array_values(array_filter($repliesRaw, function ($rv) use ($replyId) {
                return ($rv['mapValue']['fields']['id']['stringValue'] ?? '') !== $replyId;
            }));
            $newCount = max(0, count($filtered));

            $patchUrl = $url . '?updateMask.fieldPaths=repliesList&updateMask.fieldPaths=replies';
            $patch = Http::withToken($token)->timeout(8)->patch($patchUrl, [
                'fields' => [
                    'repliesList' => ['arrayValue' => ['values' => $filtered]],
                    'replies'     => ['integerValue' => (string) $newCount],
                ],
            ]);
            return $patch->successful();
        } catch (\Exception $e) {
            \Log::error('deleteReply failed', ['date' => $date, 'commentId' => $commentId, 'replyId' => $replyId, 'error' => $e->getMessage()]);
            return false;
        }
    }

    public function setUserBlockStatus(string $userId, bool $isBlocked): bool
    {
        return $this->updateUserField($userId, 'isBlocked', ['booleanValue' => $isBlocked]);
    }

    public function getAllDailyLightComments(array $dlIds): array
    {
        if (empty($dlIds)) return [];
        try {
            $token = GoogleAccessTokenService::generate();
            $base  = $this->dlBaseUrl();
            $responses = Http::pool(function ($pool) use ($dlIds, $token, $base) {
                foreach ($dlIds as $dlId) {
                    $pool->as($dlId)->withToken($token)->timeout(15)->get("{$base}/{$dlId}/comment");
                }
            });
            $allComments = [];
            foreach ($responses as $dlId => $response) {
                if (!($response instanceof \Throwable) && $response->successful()) {
                    foreach ($response->json('documents') ?? [] as $doc) {
                        $parts = explode('/', $doc['name']);
                        $commentId = end($parts);
                        $allComments[] = [
                            'id'     => $commentId,
                            'dlId'   => $dlId,
                            'fields' => $doc['fields'] ?? [],
                        ];
                    }
                }
            }
            return $allComments;
        } catch (\Exception $e) {
            \Log::error('getAllDailyLightComments failed', ['error' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * Fetch reply subcollection docs + per-reply report counts for a batch of comments.
     * Returns: [
     *   'commentId' => [
     *     'replies'          => [...reply docs],
     *     'reports'          => [...comment report docs],
     *     'replyReportCounts'=> ['replyId' => int, ...]
     *   ]
     * ]
     */
    public function getCommentSubcollections(array $commentMetas): array
    {
        if (empty($commentMetas)) return [];
        try {
            $token = GoogleAccessTokenService::generate();
            $base  = $this->dlBaseUrl();

            // Build a dlId lookup map
            $metaMap = [];
            foreach ($commentMetas as $meta) {
                $metaMap[$meta['id']] = $meta['dlId'];
            }

            // Phase 1: fetch all replies subcollections in parallel
            $phase1 = Http::pool(function ($pool) use ($commentMetas, $token, $base) {
                foreach ($commentMetas as $meta) {
                    $pool->as("replies__{$meta['id']}")
                         ->withToken($token)->timeout(15)
                         ->get("{$base}/{$meta['dlId']}/comment/{$meta['id']}/replies");
                }
            });

            $replyDocsByComment = [];
            foreach ($phase1 as $key => $response) {
                [, $commentId] = explode('__', $key, 2);
                $replyDocsByComment[$commentId] = (!($response instanceof \Throwable) && $response->successful())
                    ? ($response->json('documents') ?? [])
                    : [];
            }

            // Phase 1b: fetch comment-level report subcollections in parallel
            $commentReportDocsByComment = [];
            $phase1b = Http::pool(function ($pool) use ($commentMetas, $token, $base) {
                foreach ($commentMetas as $meta) {
                    $pool->as("reports__{$meta['id']}")
                         ->withToken($token)->timeout(10)
                         ->get("{$base}/{$meta['dlId']}/comment/{$meta['id']}/reports");
                }
            });
            foreach ($phase1b as $key => $response) {
                [, $commentId] = explode('__', $key, 2);
                $commentReportDocsByComment[$commentId] = (!($response instanceof \Throwable) && $response->successful())
                    ? ($response->json('documents') ?? [])
                    : [];
            }

            // Phase 2: fetch reports subcollection for every reply in parallel
            $replyMetas = [];
            foreach ($replyDocsByComment as $commentId => $replyDocs) {
                $dlId = $metaMap[$commentId] ?? '';
                foreach ($replyDocs as $replyDoc) {
                    $replyId = basename($replyDoc['name'] ?? '');
                    if ($replyId && $dlId) {
                        $replyMetas[] = [
                            'commentId' => $commentId,
                            'replyId'   => $replyId,
                            'dlId'      => $dlId,
                        ];
                    }
                }
            }

            $replyReportCounts = [];
            if (!empty($replyMetas)) {
                $phase2 = Http::pool(function ($pool) use ($replyMetas, $token, $base) {
                    foreach ($replyMetas as $rm) {
                        $k = "rreport__{$rm['commentId']}__{$rm['replyId']}";
                        $pool->as($k)
                             ->withToken($token)->timeout(10)
                             ->get("{$base}/{$rm['dlId']}/comment/{$rm['commentId']}/replies/{$rm['replyId']}/reports");
                    }
                });
                foreach ($phase2 as $key => $response) {
                    // key = "rreport__{commentId}__{replyId}"
                    $parts = explode('__', $key, 3);
                    if (count($parts) === 3) {
                        [, $commentId, $replyId] = $parts;
                        $count = (!($response instanceof \Throwable) && $response->successful())
                            ? count($response->json('documents') ?? [])
                            : 0;
                        $replyReportCounts["{$commentId}__{$replyId}"] = $count;
                    }
                }
            }

            // Build final result
            $result = [];
            foreach ($replyDocsByComment as $commentId => $replyDocs) {
                $perReply = [];
                foreach ($replyDocs as $replyDoc) {
                    $replyId = basename($replyDoc['name'] ?? '');
                    if ($replyId) {
                        $perReply[$replyId] = $replyReportCounts["{$commentId}__{$replyId}"] ?? 0;
                    }
                }
                $result[$commentId] = [
                    'replies'           => $replyDocs,
                    'reports'           => $commentReportDocsByComment[$commentId] ?? [],
                    'replyReportCounts' => $perReply,
                ];
            }

            return $result;
        } catch (\Exception $e) {
            \Log::error('getCommentSubcollections failed', ['error' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * Update all reply subcollection documents for a comment with the given fields.
     * Used when hiding/spamming a comment (affects all its replies).
     */
    private function updateAllReplySubcollectionDocs(string $date, string $commentId, array $fields, string $token): void
    {
        try {
            $base     = $this->dlBaseUrl();
            $replyUrl = "{$base}/{$date}/comment/{$commentId}/replies";
            $res      = Http::withToken($token)->timeout(10)->get($replyUrl);
            if (!$res->successful()) return;

            $docs = $res->json('documents') ?? [];
            if (empty($docs)) return;

            $fieldMask = implode('&', array_map(fn($f) => "updateMask.fieldPaths={$f}", array_keys($fields)));
            foreach ($docs as $doc) {
                $docUrl = "https://firestore.googleapis.com/v1/{$doc['name']}?{$fieldMask}";
                Http::withToken($token)->timeout(5)->patch($docUrl, ['fields' => $fields]);
            }
        } catch (\Exception $e) {
            \Log::error('updateAllReplySubcollectionDocs failed', [
                'date' => $date, 'commentId' => $commentId, 'error' => $e->getMessage(),
            ]);
        }
    }

    public function clearCommentReport(string $date, string $commentId): bool
    {
        try {
            $token = GoogleAccessTokenService::generate();
            $url   = $this->dlBaseUrl() . "/{$date}/comment/{$commentId}"
                   . '?updateMask.fieldPaths=isReported&updateMask.fieldPaths=reportedBy';
            $response = Http::withToken($token)->timeout(8)->patch($url, [
                'fields' => [
                    'isReported' => ['booleanValue' => false],
                    'reportedBy' => ['arrayValue'   => (object)[]],
                ],
            ]);
            if (!$response->successful()) {
                return false;
            }

            $reportsUrl = $this->dlBaseUrl() . "/{$date}/comment/{$commentId}/reports";
            $listRes    = Http::withToken($token)->timeout(10)->get($reportsUrl);
            if ($listRes->successful()) {
                foreach ($listRes->json('documents') ?? [] as $doc) {
                    $docName = $doc['name'] ?? '';
                    if ($docName) {
                        $deleteUrl = 'https://firestore.googleapis.com/v1/' . $docName;
                        Http::withToken($token)->timeout(8)->delete($deleteUrl);
                    }
                }
            }

            return true;
        } catch (\Exception $e) {
            \Log::error('clearCommentReport failed', ['date' => $date, 'commentId' => $commentId, 'error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Fetch reply subcollection docs for multiple comments in parallel.
     * Returns: ['commentId' => [...reply doc fields arrays]]
     */
    public function getCommentRepliesBulk(string $date, array $commentIds): array
    {
        if (empty($commentIds)) return [];
        try {
            $token = GoogleAccessTokenService::generate();
            $base  = $this->dlBaseUrl();

            // Phase 1: fetch reply docs for each comment
            $phase1 = Http::pool(function ($p) use ($commentIds, $token, $base, $date) {
                foreach ($commentIds as $cId) {
                    $p->as($cId)->withToken($token)->timeout(15)
                      ->get("{$base}/{$date}/comment/{$cId}/replies");
                }
            });
            $docsByComment = [];
            foreach ($commentIds as $cId) {
                $docsByComment[$cId] = (!($phase1[$cId] instanceof \Throwable) && $phase1[$cId]->successful())
                    ? ($phase1[$cId]->json('documents') ?? []) : [];
            }

            // Phase 2: fetch report counts for every reply in parallel
            $replyMetas = [];
            foreach ($docsByComment as $cId => $docs) {
                foreach ($docs as $doc) {
                    $rId = basename($doc['name'] ?? '');
                    if ($rId) $replyMetas[] = ['commentId' => $cId, 'replyId' => $rId];
                }
            }
            $replyReportCounts = [];
            if (!empty($replyMetas)) {
                $phase2 = Http::pool(function ($p) use ($replyMetas, $token, $base, $date) {
                    foreach ($replyMetas as $rm) {
                        $p->as("rc__{$rm['commentId']}__{$rm['replyId']}")
                          ->withToken($token)->timeout(10)
                          ->get("{$base}/{$date}/comment/{$rm['commentId']}/replies/{$rm['replyId']}/reports");
                    }
                });
                foreach ($phase2 as $key => $resp) {
                    $parts = explode('__', $key, 3);
                    if (count($parts) === 3) {
                        [, $cId, $rId] = $parts;
                        $replyReportCounts["{$cId}__{$rId}"] = (!($resp instanceof \Throwable) && $resp->successful())
                            ? count($resp->json('documents') ?? []) : 0;
                    }
                }
            }

            // Build result: ['commentId' => ['docs' => [...], 'reportCounts' => ['replyId' => int]]]
            $result = [];
            foreach ($docsByComment as $cId => $docs) {
                $perReply = [];
                foreach ($docs as $doc) {
                    $rId = basename($doc['name'] ?? '');
                    if ($rId) $perReply[$rId] = $replyReportCounts["{$cId}__{$rId}"] ?? 0;
                }
                $result[$cId] = ['docs' => $docs, 'reportCounts' => $perReply];
            }
            return $result;
        } catch (\Exception $e) {
            \Log::error('getCommentRepliesBulk failed', ['date' => $date, 'error' => $e->getMessage()]);
            return [];
        }
    }

    public function clearReplyReport(string $date, string $commentId, string $replyId): bool
    {
        try {
            $token = GoogleAccessTokenService::generate();
            $base  = $this->dlBaseUrl();
            $reportsUrl = "{$base}/{$date}/comment/{$commentId}/replies/{$replyId}/reports";

            // List all report docs for this reply
            $listRes = Http::withToken($token)->timeout(10)->get($reportsUrl);
            if ($listRes->successful()) {
                $docs = $listRes->json('documents') ?? [];
                foreach ($docs as $doc) {
                    $docName = $doc['name'] ?? '';
                    if ($docName) {
                        $deleteUrl = 'https://firestore.googleapis.com/v1/' . $docName;
                        Http::withToken($token)->timeout(8)->delete($deleteUrl);
                    }
                }
            }

            // Also patch isReported = false on the reply doc itself (best-effort)
            $replyUrl = "{$base}/{$date}/comment/{$commentId}/replies/{$replyId}"
                      . '?updateMask.fieldPaths=isReported';
            Http::withToken($token)->timeout(8)->patch($replyUrl, [
                'fields' => ['isReported' => ['booleanValue' => false]],
            ]);

            return true;
        } catch (\Exception $e) {
            \Log::error('clearReplyReport failed', [
                'date' => $date, 'commentId' => $commentId, 'replyId' => $replyId,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    public function hideComment(string $date, string $commentId): bool
    {
        try {
            $token = GoogleAccessTokenService::generate();
            $url   = $this->dlBaseUrl() . "/{$date}/comment/{$commentId}";
            $doc   = json_decode(Http::withToken($token)->timeout(8)->get($url)->body());
            if (!$doc) return false;

            $repliesRaw = $doc->fields->repliesList->arrayValue->values ?? [];
            foreach ($repliesRaw as $rv) {
                $rv->mapValue->fields->isHidden = (object)['booleanValue' => true];
            }

            $patchUrl = $url . '?updateMask.fieldPaths=isHidden&updateMask.fieldPaths=repliesList';
            $body     = json_encode([
                'fields' => [
                    'isHidden'    => ['booleanValue' => true],
                    'repliesList' => ['arrayValue' => ['values' => $repliesRaw]],
                ],
            ]);
            $patch = Http::withToken($token)->timeout(8)->withBody($body, 'application/json')->patch($patchUrl);

            // Also update reply subcollection docs (new structure)
            $this->updateAllReplySubcollectionDocs($date, $commentId, ['isHidden' => ['booleanValue' => true]], $token);

            return $patch->successful();
        } catch (\Exception $e) {
            \Log::error('hideComment failed', ['date' => $date, 'commentId' => $commentId, 'error' => $e->getMessage()]);
            return false;
        }
    }

    public function approveComment(string $date, string $commentId): bool
    {
        try {
            $token = GoogleAccessTokenService::generate();
            $url   = $this->dlBaseUrl() . "/{$date}/comment/{$commentId}";
            $doc   = json_decode(Http::withToken($token)->timeout(8)->get($url)->body());
            if (!$doc) return false;

            $repliesRaw = $doc->fields->repliesList->arrayValue->values ?? [];
            foreach ($repliesRaw as $rv) {
                $rv->mapValue->fields->isHidden = (object)['booleanValue' => false];
            }

            $patchUrl = $url . '?updateMask.fieldPaths=isHidden&updateMask.fieldPaths=repliesList';
            $body     = json_encode([
                'fields' => [
                    'isHidden'    => ['booleanValue' => false],
                    'repliesList' => ['arrayValue' => ['values' => $repliesRaw]],
                ],
            ]);
            $patch = Http::withToken($token)->timeout(8)->withBody($body, 'application/json')->patch($patchUrl);

            // Also update reply subcollection docs (new structure)
            $this->updateAllReplySubcollectionDocs($date, $commentId, ['isHidden' => ['booleanValue' => false]], $token);

            return $patch->successful();
        } catch (\Exception $e) {
            \Log::error('approveComment failed', ['date' => $date, 'commentId' => $commentId, 'error' => $e->getMessage()]);
            return false;
        }
    }

    public function markCommentAsSpam(string $date, string $commentId): bool
    {
        try {
            $token = GoogleAccessTokenService::generate();
            $url   = $this->dlBaseUrl() . "/{$date}/comment/{$commentId}";
            $doc   = json_decode(Http::withToken($token)->timeout(8)->get($url)->body());
            if (!$doc) return false;

            $repliesRaw = $doc->fields->repliesList->arrayValue->values ?? [];
            foreach ($repliesRaw as $rv) {
                $rv->mapValue->fields->isHidden = (object)['booleanValue' => true];
            }

            $patchUrl = $url . '?updateMask.fieldPaths=isSpam&updateMask.fieldPaths=isHidden&updateMask.fieldPaths=repliesList';
            $body     = json_encode([
                'fields' => [
                    'isSpam'      => ['booleanValue' => true],
                    'isHidden'    => ['booleanValue' => true],
                    'repliesList' => ['arrayValue' => ['values' => $repliesRaw]],
                ],
            ]);
            $patch = Http::withToken($token)->timeout(8)->withBody($body, 'application/json')->patch($patchUrl);

            // Also update reply subcollection docs (new structure)
            $this->updateAllReplySubcollectionDocs($date, $commentId, ['isHidden' => ['booleanValue' => true]], $token);

            return $patch->successful();
        } catch (\Exception $e) {
            \Log::error('markCommentAsSpam failed', ['date' => $date, 'commentId' => $commentId, 'error' => $e->getMessage()]);
            return false;
        }
    }

    public function unspamComment(string $date, string $commentId): bool
    {
        try {
            $token = GoogleAccessTokenService::generate();
            $url   = $this->dlBaseUrl() . "/{$date}/comment/{$commentId}";
            $doc   = json_decode(Http::withToken($token)->timeout(8)->get($url)->body());
            if (!$doc) return false;

            $repliesRaw = $doc->fields->repliesList->arrayValue->values ?? [];
            foreach ($repliesRaw as $rv) {
                $rv->mapValue->fields->isHidden = (object)['booleanValue' => false];
            }

            $patchUrl = $url . '?updateMask.fieldPaths=isSpam&updateMask.fieldPaths=isHidden&updateMask.fieldPaths=repliesList';
            $body     = json_encode([
                'fields' => [
                    'isSpam'      => ['booleanValue' => false],
                    'isHidden'    => ['booleanValue' => false],
                    'repliesList' => ['arrayValue' => ['values' => $repliesRaw]],
                ],
            ]);
            $patch = Http::withToken($token)->timeout(8)->withBody($body, 'application/json')->patch($patchUrl);

            // Also update reply subcollection docs (new structure)
            $this->updateAllReplySubcollectionDocs($date, $commentId, ['isHidden' => ['booleanValue' => false]], $token);

            return $patch->successful();
        } catch (\Exception $e) {
            \Log::error('unspamComment failed', ['date' => $date, 'commentId' => $commentId, 'error' => $e->getMessage()]);
            return false;
        }
    }

    public function approveProhibitedWord(string $date, string $commentId): bool
    {
        try {
            $token    = GoogleAccessTokenService::generate();
            $url      = $this->dlBaseUrl() . "/{$date}/comment/{$commentId}?updateMask.fieldPaths=isProhibitedWord";
            $response = Http::withToken($token)->timeout(8)->patch($url, [
                'fields' => ['isProhibitedWord' => ['booleanValue' => false]],
            ]);
            return $response->successful();
        } catch (\Exception $e) {
            \Log::error('approveProhibitedWord failed', ['date' => $date, 'commentId' => $commentId, 'error' => $e->getMessage()]);
            return false;
        }
    }

    public function hideReply(string $date, string $commentId, string $replyId): bool
    {
        return $this->patchReplyField($date, $commentId, $replyId, 'isHidden', ['booleanValue' => true]);
    }

    public function approveReply(string $date, string $commentId, string $replyId): bool
    {
        return $this->patchReplyField($date, $commentId, $replyId, 'isHidden', ['booleanValue' => false]);
    }

    public function approveReplyProhibitedWord(string $date, string $commentId, string $replyId): bool
    {
        return $this->patchReplyField($date, $commentId, $replyId, 'isProhibitedWord', ['booleanValue' => false]);
    }

    /**
     * Patch a single field on a reply. Tries subcollection first, falls back to repliesList array.
     */
    private function patchReplyField(string $date, string $commentId, string $replyId, string $field, array $value): bool
    {
        try {
            $token  = GoogleAccessTokenService::generate();
            $base   = $this->dlBaseUrl();
            $subUrl = "{$base}/{$date}/comment/{$commentId}/replies/{$replyId}";

            // Try subcollection first
            $checkRes = Http::withToken($token)->timeout(8)->get($subUrl);
            if ($checkRes->successful() && isset($checkRes->json()['name'])) {
                $patchUrl = $subUrl . "?updateMask.fieldPaths={$field}";
                $res = Http::withToken($token)->timeout(8)->patch($patchUrl, [
                    'fields' => [$field => $value],
                ]);
                return $res->successful();
            }

            // Fall back: old repliesList array
            $url = "{$base}/{$date}/comment/{$commentId}";
            $res = Http::withToken($token)->timeout(8)->get($url);
            if (!$res->successful()) return false;

            $doc        = json_decode($res->body());
            $repliesRaw = $doc->fields->repliesList->arrayValue->values ?? [];
            foreach ($repliesRaw as $rv) {
                $id = $rv->mapValue->fields->id->stringValue ?? '';
                if ($id === $replyId) {
                    $rv->mapValue->fields->{$field} = (object) $value;
                    break;
                }
            }
            $patchUrl = $url . '?updateMask.fieldPaths=repliesList';
            $body     = json_encode(['fields' => ['repliesList' => ['arrayValue' => ['values' => $repliesRaw]]]]);
            $patch    = Http::withToken($token)->timeout(8)->withBody($body, 'application/json')->patch($patchUrl);
            return $patch->successful();
        } catch (\Exception $e) {
            \Log::error('patchReplyField failed', [
                'date' => $date, 'commentId' => $commentId, 'replyId' => $replyId,
                'field' => $field, 'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Get all user Firestore document IDs (basename of document name).
     */
    public function getUserDocIds(): array
    {
        $token = GoogleAccessTokenService::generate();
        $url = "https://firestore.googleapis.com/v1/projects/{$this->projectId}/databases/(default)/documents/users?pageSize=1000";
        $response = Http::withToken($token)->timeout(15)->get($url);
        if (!$response->successful()) return [];
        $ids = [];
        foreach ($response->json('documents') ?? [] as $doc) {
            $ids[] = basename($doc['name']);
        }
        return $ids;
    }

    /**
     * Delete a single user's dailyProgress entry for the given date (YYYY-MM-DD).
     */
    public function deleteUserDailyProgress(string $userDocId, string $date): void
    {
        $token = GoogleAccessTokenService::generate();
        $url = "https://firestore.googleapis.com/v1/projects/{$this->projectId}/databases/(default)/documents/users/{$userDocId}/dailyProgress/{$date}";
        Http::withToken($token)->timeout(5)->delete($url);
    }

    /**
     * Delete the dailyProgress entry for the given date from ALL users (background use).
     * Date must be in YYYY-MM-DD format.
     */
    public function deleteAllUsersDailyProgress(string $date): void
    {
        $userDocIds = $this->getUserDocIds();
        foreach ($userDocIds as $docId) {
            try {
                $this->deleteUserDailyProgress($docId, $date);
            } catch (\Exception $e) {
                // Log but continue with remaining users
                \Log::warning("deleteAllUsersDailyProgress: failed for user {$docId}", ['error' => $e->getMessage()]);
            }
        }
    }

    // ── Jornada Lesson Comments ────────────────────────────────────────────

    public function getJornadaLessonIds(string $jornadaId): array
    {
        try {
            $token = GoogleAccessTokenService::generate();
            $response = Http::withToken($token)->timeout(8)->get($this->jnBaseUrl() . "/{$jornadaId}/lessons");
            if (!$response->successful()) return [];
            $ids = [];
            foreach ($response->json('documents') ?? [] as $doc) {
                $ids[] = basename($doc['name']);
            }
            return $ids;
        } catch (\Exception $e) {
            \Log::error('getJornadaLessonIds failed', ['jornadaId' => $jornadaId, 'error' => $e->getMessage()]);
            return [];
        }
    }

    public function getJornadaLessonComments(string $jornadaId, string $lessonId): array
    {
        try {
            $token = GoogleAccessTokenService::generate();
            $url = $this->jnBaseUrl() . "/{$jornadaId}/lessons/{$lessonId}/lessonsComment";
            $response = Http::withToken($token)->timeout(10)->get($url);
            if (!$response->successful()) return [];
            $comments = [];
            foreach ($response->json('documents') ?? [] as $doc) {
                $id = basename($doc['name']);
                $comments[$id] = $doc['fields'] ?? [];
            }
            return $comments;
        } catch (\Exception $e) {
            \Log::error('getJornadaLessonComments failed', ['jornadaId' => $jornadaId, 'lessonId' => $lessonId, 'error' => $e->getMessage()]);
            return [];
        }
    }

    public function deleteJornadaComment(string $jornadaId, string $lessonId, string $commentId): bool
    {
        try {
            $token = GoogleAccessTokenService::generate();
            $url = $this->jnBaseUrl() . "/{$jornadaId}/lessons/{$lessonId}/lessonsComment/{$commentId}";
            $response = Http::withToken($token)->timeout(8)->delete($url);
            return $response->successful();
        } catch (\Exception $e) {
            \Log::error('deleteJornadaComment failed', ['jornadaId' => $jornadaId, 'lessonId' => $lessonId, 'commentId' => $commentId, 'error' => $e->getMessage()]);
            return false;
        }
    }

    public function hideJornadaComment(string $jornadaId, string $lessonId, string $commentId): bool
    {
        try {
            $token = GoogleAccessTokenService::generate();
            $url = $this->jnBaseUrl() . "/{$jornadaId}/lessons/{$lessonId}/lessonsComment/{$commentId}";
            $res = Http::withToken($token)->timeout(8)->get($url);
            $current = $res->json('fields.isHidden.booleanValue') ?? false;
            $patchUrl = $url . '?updateMask.fieldPaths=isHidden';
            $patch = Http::withToken($token)->timeout(8)->patch($patchUrl, [
                'fields' => ['isHidden' => ['booleanValue' => !$current]],
            ]);
            return $patch->successful();
        } catch (\Exception $e) {
            \Log::error('hideJornadaComment failed', ['jornadaId' => $jornadaId, 'lessonId' => $lessonId, 'commentId' => $commentId, 'error' => $e->getMessage()]);
            return false;
        }
    }

    public function approveJornadaComment(string $jornadaId, string $lessonId, string $commentId): bool
    {
        try {
            $token = GoogleAccessTokenService::generate();
            $url = $this->jnBaseUrl() . "/{$jornadaId}/lessons/{$lessonId}/lessonsComment/{$commentId}";
            $patchUrl = $url . '?updateMask.fieldPaths=isHidden&updateMask.fieldPaths=isProhibitedWord&updateMask.fieldPaths=isReported';
            $patch = Http::withToken($token)->timeout(8)->patch($patchUrl, [
                'fields' => [
                    'isHidden'        => ['booleanValue' => false],
                    'isProhibitedWord'=> ['booleanValue' => false],
                    'isReported'      => ['booleanValue' => false],
                ],
            ]);
            return $patch->successful();
        } catch (\Exception $e) {
            \Log::error('approveJornadaComment failed', ['jornadaId' => $jornadaId, 'lessonId' => $lessonId, 'commentId' => $commentId, 'error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Fetch all lesson comments from all given jornadas in parallel.
     * Returns flat array of ['jornadaId', 'lessonId', 'id', 'fields'] per comment.
     */
    public function getAllJornadaComments(array $jornadaIds): array
    {
        if (empty($jornadaIds)) return [];
        try {
            $token = GoogleAccessTokenService::generate();

            // Step 1: Fetch lesson lists for all jornadas in parallel
            $lessonResponses = Http::pool(function ($pool) use ($token, $jornadaIds) {
                foreach ($jornadaIds as $jId) {
                    $pool->as($jId)->withToken($token)->timeout(8)
                        ->get($this->jnBaseUrl() . "/{$jId}/lessons");
                }
            });

            // Build lesson map: jornadaId => [lessonId, ...]
            $lessonMap = [];
            foreach ($jornadaIds as $jId) {
                $resp = $lessonResponses[$jId] ?? null;
                if ($resp && !($resp instanceof \Throwable) && $resp->successful()) {
                    foreach ($resp->json('documents') ?? [] as $doc) {
                        $lessonMap[$jId][] = basename($doc['name']);
                    }
                }
            }

            // Step 2: Fetch comments for all lessons in parallel
            $commentRequests = [];
            foreach ($lessonMap as $jId => $lessonIds) {
                foreach ($lessonIds as $lId) {
                    $commentRequests[] = ['jornadaId' => $jId, 'lessonId' => $lId];
                }
            }

            if (empty($commentRequests)) return [];

            $commentResponses = Http::pool(function ($pool) use ($token, $commentRequests) {
                foreach ($commentRequests as $i => $info) {
                    $pool->as((string) $i)->withToken($token)->timeout(10)
                        ->get($this->jnBaseUrl() . "/{$info['jornadaId']}/lessons/{$info['lessonId']}/lessonsComment");
                }
            });

            $allComments = [];
            foreach ($commentRequests as $i => $info) {
                $resp = $commentResponses[(string) $i] ?? null;
                if ($resp && !($resp instanceof \Throwable) && $resp->successful()) {
                    foreach ($resp->json('documents') ?? [] as $doc) {
                        $allComments[] = [
                            'jornadaId' => $info['jornadaId'],
                            'lessonId'  => $info['lessonId'],
                            'id'        => basename($doc['name']),
                            'fields'    => $doc['fields'] ?? [],
                        ];
                    }
                }
            }

            return $allComments;
        } catch (\Exception $e) {
            \Log::error('getAllJornadaComments failed', ['error' => $e->getMessage()]);
            return [];
        }
    }

    // ---- Subscription Details ----

    private function subscriptionBaseUrl(): string
    {
        return "https://firestore.googleapis.com/v1/projects/{$this->projectId}/databases/(default)/documents/subscription_details/subscription/translations";
    }

    public function getSubscriptionDetails(): array
    {
        $token = GoogleAccessTokenService::generate();
        $langs = ['pt', 'en', 'es'];
        $responses = Http::pool(function ($pool) use ($token, $langs) {
            foreach ($langs as $lang) {
                $pool->as($lang)->withToken($token)->timeout(8)->get($this->subscriptionBaseUrl() . "/{$lang}");
            }
        });

        $result = [];
        foreach ($langs as $lang) {
            $resp = $responses[$lang] ?? null;
            if ($resp && !($resp instanceof \Throwable) && $resp->successful()) {
                $fields = $resp->json('fields') ?? [];
                $bullets = [];
                foreach ($fields['subscription_bullet_points']['arrayValue']['values'] ?? [] as $v) {
                    $bullets[] = $v['stringValue'] ?? '';
                }
                $result[$lang] = [
                    'title'              => $fields['subscription_title']['stringValue'] ?? '',
                    'subtitle'           => $fields['subscription_subtitle']['stringValue'] ?? '',
                    'button_text'        => $fields['subscription_button_text']['stringValue'] ?? '',
                    'bullets'            => $bullets,
                    'wa_title'       => $fields['whatsapp_title']['stringValue'] ?? '',
                    'wa_subtitle'    => $fields['whatsapp_subtitle']['stringValue'] ?? '',
                    'wa_button_text' => $fields['whatsapp_button_text']['stringValue'] ?? '',
                ];
            } else {
                $result[$lang] = ['title' => '', 'subtitle' => '', 'button_text' => '', 'bullets' => [], 'wa_title' => '', 'wa_subtitle' => '', 'wa_button_text' => ''];
            }
        }
        return $result;
    }

    public function saveSubscriptionTranslation(string $lang, string $title, string $subtitle, string $buttonText, array $bullets, string $waTitle = '', string $waSubtitle = '', string $waButtonText = ''): bool
    {
        $token = GoogleAccessTokenService::generate();
        $bulletValues = array_map(fn($b) => ['stringValue' => $b], array_values(array_filter($bullets, fn($b) => trim($b) !== '')));

        $fields = [
            'subscription_title'         => ['stringValue' => $title],
            'subscription_subtitle'      => ['stringValue' => $subtitle],
            'subscription_button_text'   => ['stringValue' => $buttonText],
            'subscription_bullet_points' => ['arrayValue'  => ['values' => $bulletValues]],
            'whatsapp_title'       => ['stringValue' => $waTitle],
            'whatsapp_subtitle'    => ['stringValue' => $waSubtitle],
            'whatsapp_button_text' => ['stringValue' => $waButtonText],
            'updatedAt'                  => ['timestampValue' => now()->toIso8601String()],
        ];

        // Check if doc exists; if not, add createdAt
        $check = Http::withToken($token)->timeout(6)->get($this->subscriptionBaseUrl() . "/{$lang}");
        if (!$check->successful()) {
            $fields['createdAt'] = ['timestampValue' => now()->toIso8601String()];
        }

        $response = Http::withToken($token)->timeout(10)->patch(
            $this->subscriptionBaseUrl() . "/{$lang}",
            ['fields' => $fields]
        );
        return $response->successful();
    }
}
