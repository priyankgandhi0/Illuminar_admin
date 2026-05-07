<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class NotificationService
{
    /**
     * Send FCM notification to all active users (reads tokens from devices subcollection),
     * and save a notification document in each user's notifications subcollection.
     */
    public function sendToAllUsers(string $title, string $body, string $senderId = '', string $notificationType = 'daily_light'): array
    {
        $token = GoogleAccessTokenService::generate();
        $projectId = config('firebase.project_id');

        $users = $this->getAllActiveUsers($token, $projectId);

        if (empty($users)) {
            Log::info('FCM: No active users found');
            return ['sent' => 0, 'failed' => 0, 'total' => 0, 'errors' => []];
        }

        // Fetch all active device tokens across all users in one query
        $devicesByUid = $this->fetchAllActiveDeviceTokens($token, $projectId);

        $sent = 0;
        $failed = 0;
        $errors = [];
        $fcmUrl = "https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send";
        $firestoreBase = "https://firestore.googleapis.com/v1/projects/{$projectId}/databases/(default)/documents";
        $now = now()->toIso8601String();

        foreach ($users as $user) {
            $uid = $user['uid'];
            $devices = $devicesByUid[$uid] ?? [];

            // Send FCM push notification to each active device
            foreach ($devices as $device) {
                $fcmToken = $device['token'];
                $devicePath = $device['path'];

                try {
                    $response = Http::withToken($token)
                        ->timeout(10)
                        ->post($fcmUrl, [
                            'message' => [
                                'token' => $fcmToken,
                                'notification' => [
                                    'title' => $title,
                                    'body' => $body,
                                ],
                            ],
                        ]);

                    if ($response->successful()) {
                        $sent++;
                    } else {
                        $failed++;
                        $errorBody = $response->json();
                        $errorCode = $errorBody['error']['code'] ?? $response->status();
                        $errorStatus = $errorBody['error']['status'] ?? 'UNKNOWN';
                        $errorMessage = $errorBody['error']['message'] ?? $response->body();

                        $reason = "{$errorStatus} ({$errorCode})";
                        $errors[$reason] = ($errors[$reason] ?? 0) + 1;

                        Log::warning('FCM send failed', [
                            'token' => substr($fcmToken, 0, 20) . '...',
                            'status' => $response->status(),
                            'errorCode' => $errorCode,
                            'errorStatus' => $errorStatus,
                            'errorMessage' => $errorMessage,
                        ]);

                        // Clear stale token from device document
                        if ($response->status() === 404 || $errorStatus === 'NOT_FOUND') {
                            $this->clearDeviceFcmToken($token, $devicePath);
                        }
                    }
                } catch (\Exception $e) {
                    $failed++;
                    $reason = 'EXCEPTION: ' . $e->getMessage();
                    $errors[$reason] = ($errors[$reason] ?? 0) + 1;
                    Log::error('FCM send exception', ['error' => $e->getMessage()]);
                }
            }

            // Save notification document in user's notifications subcollection (once per user)
            try {
                $notifUrl = "{$firestoreBase}/users/{$uid}/notifications";
                $notifResponse = Http::withToken($token)->timeout(10)->post($notifUrl, [
                    'fields' => [
                        'notificationTitle' => ['stringValue' => $title],
                        'notificationBody'  => ['stringValue' => $body],
                        'notificationPayload' => ['mapValue' => [
                            'fields' => [
                                'type' => ['stringValue' => $notificationType],
                                'uid'  => ['stringValue' => $uid],
                            ]
                        ]],
                        'senderId'   => ['stringValue' => $senderId],
                        'senderType' => ['stringValue' => 'admin'],
                        'receiverId' => ['stringValue' => $uid],
                        'isRead'     => ['booleanValue' => false],
                        'createdAt'  => ['timestampValue' => $now],
                    ]
                ]);

                if ($notifResponse->successful()) {
                    $notifDocId = basename($notifResponse->json('name'));
                    Http::withToken($token)->timeout(5)->patch(
                        "{$notifUrl}/{$notifDocId}?updateMask.fieldPaths=notificationId",
                        ['fields' => ['notificationId' => ['stringValue' => $notifDocId]]]
                    );
                } else {
                    Log::warning('Failed to save notification doc', [
                        'uid' => $uid,
                        'status' => $notifResponse->status(),
                    ]);
                }
            } catch (\Exception $e) {
                Log::error('Notification doc save exception', ['uid' => $uid, 'error' => $e->getMessage()]);
            }
        }

        Log::info('FCM notification summary', [
            'title' => $title,
            'total' => count($users),
            'sent' => $sent,
            'failed' => $failed,
            'errors' => $errors,
        ]);

        return ['sent' => $sent, 'failed' => $failed, 'total' => count($users), 'errors' => $errors];
    }

    /**
     * Send FCM notification to a pre-filtered list of users.
     */
    public function sendToSpecificUsers(array $users, string $title, string $body, string $senderId = '', string $notificationType = 'daily_light'): array
    {
        if (empty($users)) {
            return ['sent' => 0, 'failed' => 0, 'total' => 0, 'errors' => []];
        }

        $token = GoogleAccessTokenService::generate();
        $projectId = config('firebase.project_id');

        $sent = 0;
        $failed = 0;
        $errors = [];
        $fcmUrl = "https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send";
        $firestoreBase = "https://firestore.googleapis.com/v1/projects/{$projectId}/databases/(default)/documents";
        $now = now()->toIso8601String();

        foreach ($users as $user) {
            $uid = $user['uid'];

            // Fetch active device tokens for this user from devices subcollection
            $devices = $this->fetchUserDeviceTokens($token, $projectId, $uid);

            // Send FCM push notification to each device
            foreach ($devices as $device) {
                $fcmToken = $device['token'];
                $devicePath = $device['path'];

                try {
                    $response = Http::withToken($token)
                        ->timeout(10)
                        ->post($fcmUrl, [
                            'message' => [
                                'token' => $fcmToken,
                                'notification' => [
                                    'title' => $title,
                                    'body' => $body,
                                ],
                            ],
                        ]);

                    if ($response->successful()) {
                        $sent++;
                    } else {
                        $failed++;
                        $errorBody = $response->json();
                        $errorCode = $errorBody['error']['code'] ?? $response->status();
                        $errorStatus = $errorBody['error']['status'] ?? 'UNKNOWN';
                        $errorMessage = $errorBody['error']['message'] ?? $response->body();

                        $reason = "{$errorStatus} ({$errorCode})";
                        $errors[$reason] = ($errors[$reason] ?? 0) + 1;

                        Log::warning('FCM send failed', [
                            'token' => substr($fcmToken, 0, 20) . '...',
                            'status' => $response->status(),
                            'errorCode' => $errorCode,
                            'errorStatus' => $errorStatus,
                            'errorMessage' => $errorMessage,
                        ]);

                        // Clear stale token from device document
                        if ($response->status() === 404 || $errorStatus === 'NOT_FOUND') {
                            $this->clearDeviceFcmToken($token, $devicePath);
                        }
                    }
                } catch (\Exception $e) {
                    $failed++;
                    $reason = 'EXCEPTION: ' . $e->getMessage();
                    $errors[$reason] = ($errors[$reason] ?? 0) + 1;
                    Log::error('FCM send exception', ['error' => $e->getMessage()]);
                }
            }

            // Save notification document in user's notifications subcollection (once per user)
            try {
                $notifUrl = "{$firestoreBase}/users/{$uid}/notifications";
                $notifResponse = Http::withToken($token)->timeout(10)->post($notifUrl, [
                    'fields' => [
                        'notificationTitle' => ['stringValue' => $title],
                        'notificationBody'  => ['stringValue' => $body],
                        'notificationPayload' => ['mapValue' => [
                            'fields' => [
                                'type' => ['stringValue' => $notificationType],
                                'uid'  => ['stringValue' => $uid],
                            ]
                        ]],
                        'senderId'   => ['stringValue' => $senderId],
                        'senderType' => ['stringValue' => 'admin'],
                        'receiverId' => ['stringValue' => $uid],
                        'isRead'     => ['booleanValue' => false],
                        'createdAt'  => ['timestampValue' => $now],
                    ]
                ]);

                if ($notifResponse->successful()) {
                    $notifDocId = basename($notifResponse->json('name'));
                    Http::withToken($token)->timeout(5)->patch(
                        "{$notifUrl}/{$notifDocId}?updateMask.fieldPaths=notificationId",
                        ['fields' => ['notificationId' => ['stringValue' => $notifDocId]]]
                    );
                } else {
                    Log::warning('Failed to save notification doc', [
                        'uid' => $uid,
                        'status' => $notifResponse->status(),
                    ]);
                }
            } catch (\Exception $e) {
                Log::error('Notification doc save exception', ['uid' => $uid, 'error' => $e->getMessage()]);
            }
        }

        Log::info('FCM notification summary', [
            'title' => $title,
            'total' => count($users),
            'sent' => $sent,
            'failed' => $failed,
            'errors' => $errors,
        ]);

        return ['sent' => $sent, 'failed' => $failed, 'total' => count($users), 'errors' => $errors];
    }

    /**
     * Fetch all active users with their preferences (timezone, activeLanguage).
     */
    public function getAllActiveUsersWithPreferences(): array
    {
        $token = GoogleAccessTokenService::generate();
        $projectId = config('firebase.project_id');

        return $this->fetchActiveUsers($token, $projectId, true);
    }

    /**
     * Fetch all active (non-deleted) users from Firestore.
     */
    private function getAllActiveUsers(string $token, string $projectId): array
    {
        return $this->fetchActiveUsers($token, $projectId, false);
    }

    private function fetchActiveUsers(string $token, string $projectId, bool $withPreferences): array
    {
        $url = "https://firestore.googleapis.com/v1/projects/{$projectId}/databases/(default)/documents/users";

        $users = [];
        $nextPageToken = null;

        do {
            $params = ['pageSize' => 300];
            if ($nextPageToken) {
                $params['pageToken'] = $nextPageToken;
            }

            $response = Http::withToken($token)->timeout(15)->get($url, $params);

            if (!$response->successful()) {
                Log::error('Failed to fetch users for FCM', ['status' => $response->status()]);
                break;
            }

            $data = $response->json();

            foreach ($data['documents'] ?? [] as $doc) {
                $f = $doc['fields'] ?? [];

                // Skip soft-deleted users
                if (($f['isDeleted']['booleanValue'] ?? false) === true) continue;

                // Skip inactive users
                if (($f['isActive']['booleanValue'] ?? true) === false) continue;

                $uid = $f['uid']['stringValue'] ?? basename($doc['name']);

                $user = ['uid' => $uid];

                if ($withPreferences) {
                    $user['timezone'] = $f['timezone']['stringValue'] ?? '';
                    $user['activeLanguage'] = $f['activeLanguage']['stringValue'] ?? '';
                }

                $users[] = $user;
            }

            $nextPageToken = $data['nextPageToken'] ?? null;

        } while ($nextPageToken);

        return $users;
    }

    /**
     * Fetch all active device FCM tokens across ALL users using a collection group query.
     * Returns: ['uid' => [['token' => '...', 'path' => '...'], ...], ...]
     */
    private function fetchAllActiveDeviceTokens(string $token, string $projectId): array
    {
        $url = "https://firestore.googleapis.com/v1/projects/{$projectId}/databases/(default)/documents:runQuery";

        try {
            $response = Http::withToken($token)->timeout(30)->post($url, [
                'structuredQuery' => [
                    'from' => [['collectionId' => 'devices', 'allDescendants' => true]],
                    'where' => [
                        'fieldFilter' => [
                            'field' => ['fieldPath' => 'isActive'],
                            'op' => 'EQUAL',
                            'value' => ['booleanValue' => true],
                        ]
                    ]
                ]
            ]);

            if (!$response->successful()) {
                Log::error('Failed to fetch device tokens via collection group', ['status' => $response->status()]);
                return [];
            }

            $devicesByUid = [];
            foreach ($response->json() as $result) {
                $doc = $result['document'] ?? null;
                if (!$doc) continue;

                $name = $doc['name'];
                // Extract uid from path: .../documents/users/{uid}/devices/{deviceId}
                if (!preg_match('#/users/([^/]+)/devices/#', $name, $m)) continue;
                $uid = $m[1];

                $f = $doc['fields'] ?? [];
                $fcmToken = $f['fcmToken']['stringValue'] ?? '';
                if (empty($fcmToken)) continue;

                $devicesByUid[$uid][] = [
                    'token' => $fcmToken,
                    'path'  => $name,
                ];
            }

            return $devicesByUid;
        } catch (\Exception $e) {
            Log::error('fetchAllActiveDeviceTokens exception', ['error' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * Fetch active device FCM tokens for a single user from their devices subcollection.
     * Returns: [['token' => '...', 'path' => '...'], ...]
     */
    private function fetchUserDeviceTokens(string $token, string $projectId, string $uid): array
    {
        $url = "https://firestore.googleapis.com/v1/projects/{$projectId}/databases/(default)/documents/users/{$uid}/devices";

        try {
            $response = Http::withToken($token)->timeout(10)->get($url, ['pageSize' => 20]);
            if (!$response->successful()) return [];

            $devices = [];
            foreach ($response->json()['documents'] ?? [] as $doc) {
                $f = $doc['fields'] ?? [];

                // Skip inactive devices
                if (($f['isActive']['booleanValue'] ?? true) === false) continue;

                // Skip devices that were force-logged-out (exceeded 2-device limit)
                if (($f['forceLoggedOut']['booleanValue'] ?? false) === true) continue;

                $fcmToken = $f['fcmToken']['stringValue'] ?? '';
                if (empty($fcmToken)) continue;

                $lastActive = $f['lastActive']['timestampValue']
                    ?? $f['lastActive']['stringValue']
                    ?? '';

                $devices[] = [
                    'token'      => $fcmToken,
                    'path'       => $doc['name'],
                    'lastActive' => $lastActive,
                ];
            }

            // Sort by most recently active, keep only the 2 newest (matches the 2-device login limit)
            usort($devices, fn($a, $b) => strcmp($b['lastActive'], $a['lastActive']));
            return array_slice($devices, 0, 2);

        } catch (\Exception $e) {
            Log::warning('fetchUserDeviceTokens exception', ['uid' => $uid, 'error' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * Clear an invalid/expired FCM token from a device document.
     */
    private function clearDeviceFcmToken(string $token, string $deviceDocPath): void
    {
        try {
            // deviceDocPath is the full Firestore resource name, e.g.:
            // projects/{proj}/databases/(default)/documents/users/{uid}/devices/{deviceId}
            $url = "https://firestore.googleapis.com/v1/{$deviceDocPath}?updateMask.fieldPaths=fcmToken";
            Http::withToken($token)->timeout(5)->patch($url, [
                'fields' => ['fcmToken' => ['stringValue' => '']]
            ]);
            Log::info('Cleared stale FCM token from device', ['device' => basename($deviceDocPath)]);
        } catch (\Exception $e) {
            Log::warning('Failed to clear device FCM token', ['error' => $e->getMessage()]);
        }
    }
}
