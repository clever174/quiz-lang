<?php

namespace App\Http\Controllers;

use App\Models\Quiz;
use Inertia\Inertia;

class QuizController extends Controller
{
    public function index()
    {
        $quizzes = Quiz::where('is_published', true)
            ->withCount('questions')
            ->orderByDesc('created_at')
            ->get(['id', 'title', 'created_at']);

        return Inertia::render('Quiz/Index', ['quizzes' => $quizzes]);
    }

    public function show(Quiz $quiz)
    {
        abort_unless($quiz->is_published || auth()->user()?->is_admin, 404);

        $quiz->load('questions.answers');

        return Inertia::render('Quiz/Show', ['quiz' => $quiz]);
    }
}
