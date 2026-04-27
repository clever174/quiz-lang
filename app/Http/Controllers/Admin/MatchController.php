<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MatchGame;
use App\Models\MatchPair;
use App\Models\Prompt;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\Encoders\WebpEncoder;
use Inertia\Inertia;

class MatchController extends Controller
{
    public function index(Request $request)
    {
        $matches = MatchGame::query()
            ->when($request->search, fn($q) => $q->where('title', 'like', "%{$request->search}%"))
            ->orderBy($request->sort_field ?? 'created_at', $request->sort_order ?? 'desc')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Admin/Match/Index', [
            'matches' => $matches,
            'filters' => $request->only('search', 'sort_field', 'sort_order'),
        ]);
    }

    public function create()
    {
        $match = MatchGame::create(['title' => 'Новый матч', 'mechanic' => 'match_up']);

        return redirect()->route('admin.match.edit', $match);
    }

    public function edit(MatchGame $match)
    {
        $match->load('pairs');

        return Inertia::render('Admin/Match/Edit', [
            'match' => $match,
            'promptTemplate' => Prompt::get('match') ?? '',
        ]);
    }

    public function update(Request $request, MatchGame $match)
    {
        $request->validate([
            'title'        => 'required|string|max:255',
            'is_published' => 'boolean',
            'pairs'    => 'array',
            'pairs.*.item_a'       => 'nullable|string',
            'pairs.*.item_a_image' => 'nullable|string',
            'pairs.*.item_b'       => 'nullable|string',
            'pairs.*.item_b_image' => 'nullable|string',
            'pairs.*.order'        => 'integer',
        ]);

        $match->update([
            'title'        => $request->title,
            'is_published' => $request->boolean('is_published'),
        ]);

        $keepIds = [];

        foreach ($request->pairs ?? [] as $i => $pData) {
            $pair = isset($pData['id'])
                ? MatchPair::find($pData['id'])
                : new MatchPair(['match_id' => $match->id]);

            // Handle item_a image
            $newImageA = $pData['item_a_image'] ?? null;
            if ($newImageA) {
                if ($pair->item_a_image && $pair->item_a_image !== $newImageA) {
                    Storage::disk('public')->delete($pair->item_a_image);
                }
                $pair->item_a_image = $newImageA;
            } elseif ($pair->item_a_image) {
                Storage::disk('public')->delete($pair->item_a_image);
                $pair->item_a_image = null;
            }

            // Handle item_b image
            $newImageB = $pData['item_b_image'] ?? null;
            if ($newImageB) {
                if ($pair->item_b_image && $pair->item_b_image !== $newImageB) {
                    Storage::disk('public')->delete($pair->item_b_image);
                }
                $pair->item_b_image = $newImageB;
            } elseif ($pair->item_b_image) {
                Storage::disk('public')->delete($pair->item_b_image);
                $pair->item_b_image = null;
            }

            $pair->item_a = $pData['item_a'] ?? null;
            $pair->item_b = $pData['item_b'] ?? null;
            $pair->order  = $i;
            $pair->save();

            $keepIds[] = $pair->id;
        }

        $match->pairs()->whereNotIn('id', $keepIds)->each(function ($p) {
            if ($p->item_a_image) Storage::disk('public')->delete($p->item_a_image);
            if ($p->item_b_image) Storage::disk('public')->delete($p->item_b_image);
            $p->delete();
        });

        return back()->with('success', 'Матч сохранён');
    }

    public function uploadImage(Request $request)
    {
        $request->validate(['image' => 'required|image|max:10240']);

        $path = $this->processImage($request->file('image'));
        $size = Storage::disk('public')->size($path);

        return response()->json([
            'path' => $path,
            'url'  => Storage::disk('public')->url($path),
            'size' => $size,
        ]);
    }

    public function destroy(MatchGame $match)
    {
        $match->pairs->each(function ($p) {
            if ($p->item_a_image) Storage::disk('public')->delete($p->item_a_image);
            if ($p->item_b_image) Storage::disk('public')->delete($p->item_b_image);
        });

        $match->delete();

        return redirect()->route('admin.match.index');
    }

    private function processImage(\Illuminate\Http\UploadedFile $file): string
    {
        $manager = new ImageManager(new Driver());
        $image   = $manager->decode($file->getRealPath());

        if ($image->width() > 800) {
            $image->scaleDown(width: 800);
        }

        $filename = 'match-images/' . \Illuminate\Support\Str::uuid() . '.webp';
        $encoded  = $image->encode(new WebpEncoder(quality: 80));

        if (!Storage::disk('public')->put($filename, $encoded)) {
            throw new \RuntimeException('Failed to write image to storage (disk may be full)');
        }

        return $filename;
    }
}
