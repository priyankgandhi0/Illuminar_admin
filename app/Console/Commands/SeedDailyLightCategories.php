<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\FirestoreService;
use Illuminate\Support\Facades\Storage;

class SeedDailyLightCategories extends Command
{
    protected $signature = 'seed:daily-light-categories {--fresh : Delete existing categories first}';
    protected $description = 'Seed the 4 default Daily Light categories with icons';

    public function handle(FirestoreService $firestore)
    {
        // Delete existing categories when --fresh flag is used
        if ($this->option('fresh')) {
            $existing = $firestore->getDailyLightCategories();
            foreach ($existing as $id => $fields) {
                $iconKey = $fields['icon']['stringValue'] ?? '';
                if ($iconKey) {
                    try { Storage::disk('r2')->delete($iconKey); } catch (\Exception $e) {}
                }
                $firestore->deleteDailyLightCategory($id);
                $this->warn("Deleted: {$id}");
            }
        }

        $categories = [
            [
                'pt_title' => 'Reflexão Matinal',
                'en_title' => 'Morning Reflection',
                'es_title' => 'Reflexión Matutina',
                'icon' => $this->morningReflectionSvg(),
            ],
            [
                'pt_title' => 'Meditação de Gratidão',
                'en_title' => 'Gratitude Meditation',
                'es_title' => 'Meditación de Gratitud',
                'icon' => $this->gratitudeMeditationSvg(),
            ],
            [
                'pt_title' => 'Prática de Paz Interior',
                'en_title' => 'Inner Peace Practice',
                'es_title' => 'Práctica de Paz Interior',
                'icon' => $this->innerPeaceSvg(),
            ],
            [
                'pt_title' => 'Bênção da Noite',
                'en_title' => 'Evening Blessing',
                'es_title' => 'Bendición Vespertina',
                'icon' => $this->eveningBlessingSvg(),
            ],
        ];

        foreach ($categories as $cat) {
            $path = 'daily-light-categories/' . uniqid() . '.svg';
            Storage::disk('r2')->put($path, $cat['icon'], ['ContentType' => 'image/svg+xml']);

            $fields = [
                'pt_title' => ['stringValue' => $cat['pt_title']],
                'en_title' => ['stringValue' => $cat['en_title']],
                'es_title' => ['stringValue' => $cat['es_title']],
                'icon' => ['stringValue' => $path],
                'createdAt' => ['timestampValue' => now()->toIso8601String()],
                'updatedAt' => ['timestampValue' => now()->toIso8601String()],
            ];

            $result = $firestore->createDailyLightCategory($fields);

            if ($result['success']) {
                $this->info("Created: {$cat['en_title']}");
            } else {
                $this->error("Failed: {$cat['en_title']}");
            }
        }

        $this->info('Done! 4 Daily Light categories seeded.');
    }

    /** Person reading from open book on a podium/lectern */
    private function morningReflectionSvg(): string
    {
        return <<<'SVG'
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 200 200"><g fill="white"><circle cx="100" cy="28" r="20"/><path d="M76 55c0-5 11-10 24-10s24 5 24 10v18H76z"/><path fill-rule="evenodd" d="M56 76c0-3 3-6 6-6h76c3 0 6 3 6 6v68c0 3-3 6-6 6H62c-3 0-6-3-6-6V76z M64 84l34 5v50l-34-5z M136 84l-34 5v50l34-5z"/></g></svg>
SVG;
    }

    /** Person sitting in lotus meditation pose with arms raised outward */
    private function gratitudeMeditationSvg(): string
    {
        return <<<'SVG'
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 200 200"><g fill="white"><circle cx="100" cy="28" r="19"/><path d="M87 50h26v40H87z"/><path d="M87 60l-32-20c-4-2-8 0-8 4v12c0 3 2 5 4 6l36 18z"/><path d="M113 60l32-20c4-2 8 0 8 4v12c0 3-2 5-4 6l-36 18z"/><path d="M46 140c0-30 24-50 54-50s54 20 54 50c0 6-2 11-6 14H52c-4-3-6-8-6-14z"/></g></svg>
SVG;
    }

    /** Two hands pressed together in prayer/namaste with drops above */
    private function innerPeaceSvg(): string
    {
        return <<<'SVG'
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 200 200"><g fill="white"><path d="M100 52L74 82c-6 8-8 18-6 28l4 36c1 8 6 14 14 16l14 4V52z"/><path d="M100 52l26 30c6 8 8 18 6 28l-4 36c-1 8-6 14-14 16l-14 4V52z"/><ellipse cx="100" cy="34" rx="6" ry="10"/><ellipse cx="78" cy="40" rx="5" ry="8" transform="rotate(-15 78 40)"/><ellipse cx="122" cy="40" rx="5" ry="8" transform="rotate(15 122 40)"/></g></svg>
SVG;
    }

    /** Head/face with 3 light bulbs arranged above (illumination/blessing) */
    private function eveningBlessingSvg(): string
    {
        return <<<'SVG'
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 200 200"><g fill="white"><circle cx="100" cy="148" r="28"/><path d="M80 172h40l8 14H72z"/><circle cx="100" cy="48" r="16"/><path d="M92 64h16v10c0 3-3 6-8 6s-8-3-8-6V64z"/><circle cx="50" cy="82" r="14"/><path d="M42 96h16v8c0 3-3 5-8 5s-8-2-8-5V96z"/><circle cx="150" cy="82" r="14"/><path d="M142 96h16v8c0 3-3 5-8 5s-8-2-8-5V96z"/><circle cx="74" cy="60" r="5"/><circle cx="126" cy="60" r="5"/></g></svg>
SVG;
    }
}
