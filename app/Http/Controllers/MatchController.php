<?php

namespace App\Http\Controllers;

use App\Models\MatchGame;
use Inertia\Inertia;

class MatchController extends Controller
{
    public function index()
    {
        $matches = MatchGame::where('is_published', true)
            ->withCount('pairs')
            ->orderByDesc('created_at')
            ->get(['id', 'title', 'mechanic', 'created_at']);

        return Inertia::render('Match/Index', ['matches' => $matches]);
    }

    public function show(MatchGame $match, \Illuminate\Http\Request $request)
    {
        abort_unless($match->is_published || auth()->user()?->is_admin, 404);

        $match->load('pairs');

        $mechanics = ['match_up', 'matching_pairs', 'true_or_false'];
        $mechanic  = in_array($request->query('mechanic'), $mechanics)
            ? $request->query('mechanic')
            : 'match_up';

        return Inertia::render('Match/Show', [
            'match'    => $match,
            'mechanic' => $mechanic,
        ]);
    }
}
