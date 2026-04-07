<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Prompt;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SettingsController extends Controller
{
    public function index()
    {
        return Inertia::render('Admin/Settings', [
            'prompts' => Prompt::orderBy('id')->get(['id', 'key', 'label', 'text']),
        ]);
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
