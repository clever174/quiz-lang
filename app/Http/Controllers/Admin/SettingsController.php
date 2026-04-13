<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MatchPair;
use App\Models\Prompt;
use App\Models\QuizQuestion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class SettingsController extends Controller
{
    public function index()
    {
        return Inertia::render('Admin/Settings', [
            'prompts' => Prompt::orderBy('id')->get(['id', 'key', 'label', 'text']),
            'storage' => $this->storageInfo(),
        ]);
    }

    private function storageInfo(): array
    {
        $disk = Storage::disk('public');

        $allFiles = collect($disk->files('quiz-images'))
            ->merge($disk->files('match-images'));

        $usedPaths = collect()
            ->merge(QuizQuestion::whereNotNull('image')->pluck('image'))
            ->merge(MatchPair::whereNotNull('item_a_image')->pluck('item_a_image'))
            ->merge(MatchPair::whereNotNull('item_b_image')->pluck('item_b_image'))
            ->filter(fn($p) => !str_starts_with($p, 'http'))
            ->unique();

        $orphans = $allFiles
            ->filter(fn($f) => !$usedPaths->contains($f))
            ->map(fn($path) => [
                'path' => $path,
                'url'  => $disk->url($path),
                'size' => $disk->size($path),
            ])
            ->values();

        $storagePath = storage_path('app/public');
        $total = disk_total_space($storagePath);
        $free  = disk_free_space($storagePath);

        return [
            'total'   => $total,
            'free'    => $free,
            'used'    => $total - $free,
            'orphans' => $orphans,
        ];
    }

    public function cleanupImages()
    {
        $disk = Storage::disk('public');

        $allFiles = collect($disk->files('quiz-images'))
            ->merge($disk->files('match-images'));

        $usedPaths = collect()
            ->merge(QuizQuestion::whereNotNull('image')->pluck('image'))
            ->merge(MatchPair::whereNotNull('item_a_image')->pluck('item_a_image'))
            ->merge(MatchPair::whereNotNull('item_b_image')->pluck('item_b_image'))
            ->filter(fn($p) => !str_starts_with($p, 'http'))
            ->unique();

        $deleted = 0;
        foreach ($allFiles as $file) {
            if (!$usedPaths->contains($file)) {
                $disk->delete($file);
                $deleted++;
            }
        }

        return response()->json(['deleted' => $deleted]);
    }

    public function updatePrompts(Request $request)
    {
        $request->validate([
            'prompts' => 'required|array',
            'prompts.*.id' => 'required|integer|exists:prompts,id',
            'prompts.*.text' => 'required|string',
        ]);

        foreach ($request->prompts as $item) {
            Prompt::where('id', $item['id'])->update(['text' => $item['text']]);
        }

        return back()->with('success', 'Настройки сохранены');
    }
}
