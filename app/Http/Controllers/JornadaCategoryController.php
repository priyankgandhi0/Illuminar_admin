<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\FirestoreService;

class JornadaCategoryController extends Controller
{
    protected $firestore;

    public function __construct(FirestoreService $firestore)
    {
        $this->firestore = $firestore;
    }

    public function index()
    {
        $items = $this->firestore->getJornadaCategories();

        $categories = [];
        foreach ($items as $id => $fields) {
            $languages = [];
            if (!empty($fields['pt_title']['stringValue'])) $languages[] = 'pt';
            if (!empty($fields['en_title']['stringValue'])) $languages[] = 'en';
            if (!empty($fields['es_title']['stringValue'])) $languages[] = 'es';

            $categories[] = [
                'id' => $id,
                'title' => $fields['pt_title']['stringValue'] ?? '',
                'languages' => $languages,
                'order' => (int) ($fields['order']['integerValue'] ?? 9999),
                'createdAt' => $fields['createdAt']['timestampValue'] ?? '',
            ];
        }

        usort($categories, function ($a, $b) {
            if ($a['order'] !== $b['order']) return $a['order'] <=> $b['order'];
            return strcmp($b['createdAt'], $a['createdAt']);
        });

        return view('pages.jornada-categories.index', compact('categories'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'title_pt' => 'required|string|max:255',
            'title_en' => 'required|string|max:255',
            'title_es' => 'required|string|max:255',
        ], [
            'title_pt.required' => __('common.required_field'),
            'title_en.required' => __('common.required_field'),
            'title_es.required' => __('common.required_field'),
        ]);

        try {
            $existing = $this->firestore->getJornadaCategories();
            $nextOrder = count($existing) + 1;

            $fields = [
                'pt_title' => ['stringValue' => (string) $request->input('title_pt', '')],
                'en_title' => ['stringValue' => (string) $request->input('title_en', '')],
                'es_title' => ['stringValue' => (string) $request->input('title_es', '')],
                'order' => ['integerValue' => $nextOrder],
                'createdAt' => ['timestampValue' => now()->toIso8601String()],
                'updatedAt' => ['timestampValue' => now()->toIso8601String()],
            ];

            $result = $this->firestore->createJornadaCategory($fields);

            if (!$result['success']) {
                return response()->json(['success' => false, 'message' => __('jornada_categories.failed_create')], 500);
            }

            return response()->json(['success' => true, 'message' => __('jornada_categories.created_success')]);
        } catch (\Exception $e) {
            \Log::error('JornadaCategory store exception', ['message' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => __('common.error') . ': ' . $e->getMessage()], 500);
        }
    }

    public function edit(string $id)
    {
        $fields = $this->firestore->getJornadaCategory($id);

        if (!$fields) {
            return response()->json(['success' => false, 'message' => __('jornada_categories.not_found')], 404);
        }

        $category = [
            'id' => $id,
            'pt_title' => $fields['pt_title']['stringValue'] ?? '',
            'en_title' => $fields['en_title']['stringValue'] ?? '',
            'es_title' => $fields['es_title']['stringValue'] ?? '',
        ];

        return response()->json(['success' => true, 'category' => $category]);
    }

    public function update(Request $request, string $id)
    {
        $request->validate([
            'title_pt' => 'required|string|max:255',
            'title_en' => 'required|string|max:255',
            'title_es' => 'required|string|max:255',
        ], [
            'title_pt.required' => __('common.required_field'),
            'title_en.required' => __('common.required_field'),
            'title_es.required' => __('common.required_field'),
        ]);

        try {
            $fields = [
                'pt_title' => ['stringValue' => (string) $request->input('title_pt', '')],
                'en_title' => ['stringValue' => (string) $request->input('title_en', '')],
                'es_title' => ['stringValue' => (string) $request->input('title_es', '')],
                'updatedAt' => ['timestampValue' => now()->toIso8601String()],
            ];

            $success = $this->firestore->updateJornadaCategory($id, $fields);

            if (!$success) {
                return response()->json(['success' => false, 'message' => __('jornada_categories.failed_update')], 500);
            }

            return response()->json(['success' => true, 'message' => __('jornada_categories.updated_success')]);
        } catch (\Exception $e) {
            \Log::error('JornadaCategory update exception', ['message' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => __('common.error') . ': ' . $e->getMessage()], 500);
        }
    }

    public function reorder(Request $request)
    {
        try {
            $items = $request->input('items', []);
            foreach ($items as $item) {
                $this->firestore->updateCategoryOrder($item['id'], (int) $item['order']);
            }
            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            \Log::error('JornadaCategory reorder exception', ['message' => $e->getMessage()]);
            return response()->json(['success' => false], 500);
        }
    }

    public function checkUsage(string $id)
    {
        $inUse = $this->firestore->categoryHasJornadas($id);
        return response()->json(['in_use' => $inUse]);
    }

    public function destroy(string $id)
    {
        try {
            // Check if category has jornadas
            if ($this->firestore->categoryHasJornadas($id)) {
                return back()->with('error', __('jornada_categories.has_jornadas'));
            }

            $success = $this->firestore->deleteJornadaCategory($id);

            if ($success) {
                return redirect()->route('jornada-categories.index')->with('success', __('jornada_categories.deleted_success'));
            }

            return back()->with('error', __('jornada_categories.failed_delete'));
        } catch (\Exception $e) {
            \Log::error('JornadaCategory delete exception', ['message' => $e->getMessage()]);
            return back()->with('error', __('common.error') . ': ' . $e->getMessage());
        }
    }

}
