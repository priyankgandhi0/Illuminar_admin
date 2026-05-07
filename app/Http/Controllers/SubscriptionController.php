<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\FirestoreService;

class SubscriptionController extends Controller
{
    protected FirestoreService $firestore;

    public function __construct(FirestoreService $firestore)
    {
        $this->firestore = $firestore;
    }

    public function index()
    {
        $details = $this->firestore->getSubscriptionDetails();
        return view('pages.subscription.index', compact('details'));
    }

    public function save(Request $request)
    {
        $lang         = $request->input('lang');
        $title        = $request->input('title', '');
        $subtitle     = $request->input('subtitle', '');
        $buttonText   = $request->input('button_text', '');
        $bullets      = $request->input('bullets', []);
        $waTitle      = $request->input('wa_title', '');
        $waSubtitle   = $request->input('wa_subtitle', '');
        $waButtonText = $request->input('wa_button_text', '');

        if (!in_array($lang, ['pt', 'en', 'es'])) {
            return response()->json(['success' => false, 'message' => 'Invalid language.']);
        }

        $ok = $this->firestore->saveSubscriptionTranslation($lang, $title, $subtitle, $buttonText, $bullets, $waTitle, $waSubtitle, $waButtonText);

        if ($ok) {
            return response()->json(['success' => true, 'message' => __('subscription.saved_success')]);
        }
        return response()->json(['success' => false, 'message' => __('common.something_wrong')]);
    }
}
