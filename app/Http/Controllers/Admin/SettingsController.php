<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Prompt;
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
        $disk = Storage::disk('images');
        $storagePath = $disk->path('');
        $usedBytes = 0;

        if (is_dir($storagePath)) {
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($storagePath, \RecursiveDirectoryIterator::SKIP_DOTS)
            );
            foreach ($iterator as $file) {
                if ($file->isFile()) $usedBytes += $file->getSize();
            }
        }

        return ['used' => $usedBytes];
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
