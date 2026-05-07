<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\FirestoreService;

class DashboardController extends Controller
{
    public function index(FirestoreService $firestore)
    {
        $userCount = count($firestore->getFirestoreUsersMap());

        return view('layouts.partials.dashboard', compact('userCount'));
    }
}
