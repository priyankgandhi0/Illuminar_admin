<?php
namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

class FileStorageService
{
   public function uploadUserImage($file)
    {
        $filename = time().'_'.$file->getClientOriginalName();

        Storage::disk('r2')->putFileAs(
            "users",
            $file,
            $filename
        );

        return [
            'storage_key' => "users/$filename",
            'file_name' => $file->getClientOriginalName(),
            'file_type' => $file->getMimeType(),
            'file_size' => $file->getSize()
        ];
    }

    public function uploadDailyLightFile($file, string $date, string $lang, int $stepIndex): array
    {
        $ext = strtolower($file->getClientOriginalExtension());
        $randomName = Str::random(32) . '.' . $ext;
        $key = "daily-light/{$date}/{$lang}/{$randomName}";

        Storage::disk('r2')->putFileAs(
            "daily-light/{$date}/{$lang}",
            $file,
            $randomName
        );

        return ['storage_key' => $key];
    }

    public function uploadCategoryIcon($file, string $type): array
    {
        $randomName = Str::random(32) . '.png';
        $filename = "{$type}_{$randomName}";
        $key = "category-icons/{$filename}";

        Storage::disk('r2')->putFileAs('category-icons', $file, $filename);

        return ['storage_key' => $key];
    }

    public function deleteCategoryIcon(string $storageKey): void
    {
        try {
            Storage::disk('r2')->delete($storageKey);
        } catch (\Exception $e) {}
    }

    public function uploadJornadaFile($file, string $jornadaId, string $lang, ?string $lessonId = null): array
    {
        $ext = strtolower($file->getClientOriginalExtension());
        $randomName = Str::random(32) . '.' . $ext;

        if ($lessonId) {
            $dir = "jornadas/{$jornadaId}/lessons/{$lang}";
        } else {
            $dir = "jornadas/{$jornadaId}/{$lang}";
        }

        $key = "{$dir}/{$randomName}";

        Storage::disk('r2')->putFileAs($dir, $file, $randomName);

        return ['storage_key' => $key];
    }
}
