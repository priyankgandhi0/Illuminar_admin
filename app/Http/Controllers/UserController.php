<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\FirestoreService;
use App\Services\FileStorageService;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class UserController extends Controller
{
    protected $firestore;
    protected $fileStorage;

    public function __construct(
        FirestoreService $firestore,
        FileStorageService $fileStorage
    ){
        $this->firestore = $firestore;
        $this->fileStorage = $fileStorage;
    }

    public function index()
    {
        $firestoreUsers = $this->firestore->getFirestoreUsersMap();

        $users = [];
        foreach ($firestoreUsers as $uid => $fields) {
            $users[] = [
                'uid'            => $fields['uid']['stringValue'] ?? $uid,
                'name'           => $fields['name']['stringValue'] ?? '',
                'email'          => $fields['email']['stringValue'] ?? '',
                'loginType'      => $fields['loginType']['stringValue'] ?? '',
                'isActive'       => $fields['isActive']['booleanValue'] ?? false,
                'is_subscribed'  => $fields['is_subscribed']['booleanValue'] ?? false,
                'profileImage'   => $fields['profileImage']['stringValue'] ?? '',
                'createdAt'      => $fields['createdAt']['timestampValue'] ?? null,
                'currentStreak'  => isset($fields['currentStreak']['integerValue']) ? (int) $fields['currentStreak']['integerValue'] : 0,
                'lifetimeStreak' => isset($fields['lifetimeStreak']['integerValue']) ? (int) $fields['lifetimeStreak']['integerValue'] : 0,
                'lastLoginAt'    => $fields['lastLoginAt']['timestampValue'] ?? null,
                'updatedAt'      => $fields['updatedAt']['timestampValue'] ?? null,
                'timezone'       => $fields['timezone']['stringValue'] ?? '',
                'activeLanguage' => $fields['activeLanguage']['stringValue'] ?? '',
                'isBlocked'      => $fields['isBlocked']['booleanValue'] ?? false,
            ];
        }

        return view('pages.users.index', compact('users'));
    }

    public function show(string $uid)
    {
        $fields = $this->firestore->getFirestoreUser($uid);

        if (!$fields) {
            return response()->json(['error' => 'User not found'], 404);
        }

        $user = [
            'uid'           => $fields['uid']['stringValue'] ?? $uid,
            'name'          => $fields['name']['stringValue'] ?? '',
            'email'         => $fields['email']['stringValue'] ?? '',
            'loginType'     => $fields['loginType']['stringValue'] ?? '',
            'isActive'      => $fields['isActive']['booleanValue'] ?? false,
            'is_subscribed' => $fields['is_subscribed']['booleanValue'] ?? false,
            'profileImage'  => $fields['profileImage']['stringValue'] ?? '',
            'createdAt'     => $fields['createdAt']['timestampValue'] ?? null,
            'currentStreak' => isset($fields['currentStreak']['integerValue']) ? (int) $fields['currentStreak']['integerValue'] : 0,
            'lifetimeStreak'=> isset($fields['lifetimeStreak']['integerValue']) ? (int) $fields['lifetimeStreak']['integerValue'] : 0,
            'lastLoginAt'   => $fields['lastLoginAt']['timestampValue'] ?? null,
            'updatedAt'     => $fields['updatedAt']['timestampValue'] ?? null,
            'fcmToken'       => $fields['fcmToken']['stringValue'] ?? '',
            'timezone'       => $fields['timezone']['stringValue'] ?? '',
            'activeLanguage' => $fields['activeLanguage']['stringValue'] ?? '',
            'isBlocked'      => $fields['isBlocked']['booleanValue'] ?? false,
        ];

        return response()->json($user);
    }

    public function toggleActive(string $uid, Request $request)
    {
        $isActive = filter_var($request->input('isActive'), FILTER_VALIDATE_BOOLEAN);

        $success = $this->firestore->toggleUserActive($uid, $isActive);

        if ($success) {
            $message = $isActive ? __('users.activated_success') : __('users.deactivated_success');
            return response()->json(['success' => true, 'message' => $message]);
        }

        return response()->json(['success' => false, 'error' => __('users.failed_update_status')], 500);
    }

    public function destroy(string $uid)
    {
        // Delete from Firestore DB
        $dbDeleted = $this->firestore->hardDeleteUser($uid);

        // Delete from Firebase Auth
        $authDeleted = $this->firestore->deleteFirebaseAuthUser($uid);

        if ($dbDeleted && $authDeleted) {
            cache()->forget('firebase_auth_users');
            return response()->json(['success' => true, 'message' => __('users.deleted_success')]);
        }

        if ($dbDeleted) {
            cache()->forget('firebase_auth_users');
            return response()->json(['success' => true, 'message' => __('users.deleted_partial')]);
        }

        return response()->json(['success' => false, 'error' => __('users.failed_delete')], 500);
    }

    public function updateFields(string $uid, Request $request)
    {
        $request->validate([
            'timezone'       => 'nullable|string|max:50|timezone:all',
            'activeLanguage' => 'nullable|string|in:pt,en,es',
        ]);

        $fields = [
            'timezone'       => ['stringValue' => $request->input('timezone', '')],
            'activeLanguage' => ['stringValue' => $request->input('activeLanguage', '')],
        ];

        $success = $this->firestore->updateUserFields($uid, $fields);

        if ($success) {
            return response()->json(['success' => true, 'message' => __('users.preferences_updated')]);
        }

        return response()->json(['success' => false, 'error' => __('users.failed_update_preferences')], 500);
    }

    public function store(Request $request)
    {
        $request->validate([
            'userName' => 'required',
            'image' => 'required|image'
        ]);

        $fileMeta = $this->fileStorage->uploadUserImage(
            $request->file('image')
        );

        $userResponse = $this->firestore->createUser([
            'userName' => $request->userName
        ]);

        $userId = basename($userResponse['name']);

        $this->firestore->createUserFile([
            'user_id' => $userId,
            'storage_key' => $fileMeta['storage_key'],
            'file_name' => $fileMeta['file_name'],
            'file_type' => $fileMeta['file_type'],
            'file_size' => $fileMeta['file_size']
        ]);

        return redirect()->back()->with('success', __('users.user_added'));
    }

}
