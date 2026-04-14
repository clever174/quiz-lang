<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Prompt;
use App\Models\Quiz;
use App\Models\QuizQuestion;
use App\Models\QuizAnswer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\Encoders\WebpEncoder;
use Inertia\Inertia;

class QuizController extends Controller
{
    public function index(Request $request)
    {
        $quizzes = Quiz::query()
            ->when($request->search, fn($q) => $q->where('title', 'like', "%{$request->search}%"))
            ->orderBy($request->sort_field ?? 'created_at', $request->sort_order ?? 'desc')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Admin/Quiz/Index', [
            'quizzes' => $quizzes,
            'filters' => $request->only('search', 'sort_field', 'sort_order'),
        ]);
    }

    public function create()
    {
        $quiz = Quiz::create(['title' => 'Новый квиз']);

        return redirect()->route('admin.quiz.edit', $quiz);
    }

    public function edit(Quiz $quiz)
    {
        $quiz->load('questions.answers');

        return Inertia::render('Admin/Quiz/Edit', [
            'quiz' => $quiz,
            'promptTemplate' => Prompt::get('quiz') ?? '',
        ]);
    }

    public function update(Request $request, Quiz $quiz)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'is_published' => 'boolean',
            'questions' => 'array',
            'questions.*.question_text' => 'nullable|string',
            'questions.*.image' => 'nullable|image|max:2048',
            'questions.*.order' => 'integer',
            'questions.*.answers' => 'array|min:2',
            'questions.*.answers.*.text' => 'required|string|max:255',
            'questions.*.answers.*.is_correct' => 'boolean',
        ]);

        DB::transaction(function () use ($request, $quiz) {
            $quiz->update([
                'title' => $request->title,
                'is_published' => $request->boolean('is_published'),
            ]);

            $keepQuestionIds = [];

            foreach ($request->questions ?? [] as $i => $qData) {
                $question = isset($qData['id'])
                    ? (QuizQuestion::find($qData['id']) ?? new QuizQuestion(['quiz_id' => $quiz->id]))
                    : new QuizQuestion(['quiz_id' => $quiz->id]);

                $question->quiz_id = $quiz->id;
                $question->question_text = $qData['question_text'] ?? null;
                $question->order = $i;

                $newImagePath = $qData['image_path'] ?? null;

                if ($newImagePath) {
                    if ($question->image && $question->image !== $newImagePath) {
                        Storage::disk('public')->delete($question->image);
                    }
                    $question->image = $newImagePath;
                } elseif ($question->image) {
                    Storage::disk('public')->delete($question->image);
                    $question->image = null;
                }

                $question->save();
                $keepQuestionIds[] = $question->id;

                $keepAnswerIds = [];
                foreach ($qData['answers'] ?? [] as $j => $aData) {
                    $answer = isset($aData['id'])
                        ? (QuizAnswer::find($aData['id']) ?? new QuizAnswer(['question_id' => $question->id]))
                        : new QuizAnswer(['question_id' => $question->id]);

                    $answer->question_id = $question->id;
                    $answer->text = $aData['text'];
                    $answer->is_correct = (bool)($aData['is_correct'] ?? false);
                    $answer->order = $j;
                    $answer->save();
                    $keepAnswerIds[] = $answer->id;
                }

                $question->answers()->whereNotIn('id', $keepAnswerIds)->delete();
            }

            $quiz->questions()->whereNotIn('id', $keepQuestionIds)->each(function ($q) {
                if ($q->image) Storage::disk('public')->delete($q->image);
                $q->delete();
            });
        });

        return back()->with('success', 'Квиз сохранён');
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

    public function fetchImage(Request $request)
    {
        $request->validate(['url' => 'required|url|max:2048']);

        try {
            $response = Http::timeout(15)->get($request->url);

            if (!$response->successful()) {
                return response()->json(['error' => 'Не удалось загрузить картинку'], 422);
            }

            $path = $this->processImageFromString($response->body());
            $size = Storage::disk('public')->size($path);

            return response()->json([
                'path' => $path,
                'url'  => Storage::disk('public')->url($path),
                'size' => $size,
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Не удалось загрузить картинку'], 422);
        }
    }

    public function copyImage(Request $request)
    {
        $path = $request->input('path');

        if (!$path || str_starts_with($path, 'http')) {
            return response()->json(['path' => $path, 'url' => $path, 'size' => null]);
        }

        if (!Storage::disk('public')->exists($path)) {
            abort(404);
        }

        $ext = pathinfo($path, PATHINFO_EXTENSION);
        $newPath = 'quiz-images/' . \Illuminate\Support\Str::uuid() . '.' . $ext;
        Storage::disk('public')->copy($path, $newPath);
        $size = Storage::disk('public')->size($newPath);

        return response()->json([
            'path' => $newPath,
            'url'  => Storage::disk('public')->url($newPath),
            'size' => $size,
        ]);
    }

    public function destroy(Quiz $quiz)
    {
        $quiz->questions->each(function ($q) {
            if ($q->image) Storage::disk('public')->delete($q->image);
        });

        $quiz->delete();

        return redirect()->route('admin.quiz.index');
    }

    private function processImageFromString(string $contents): string
    {
        $manager = new ImageManager(new Driver());
        $image = $manager->decode($contents);

        if ($image->width() > 800) {
            $image->scaleDown(width: 800);
        }

        $filename = 'quiz-images/' . \Illuminate\Support\Str::uuid() . '.webp';
        Storage::disk('public')->put($filename, $image->encode(new WebpEncoder(quality: 80)));

        return $filename;
    }

    private function processImage(\Illuminate\Http\UploadedFile $file): string
    {
        $manager = new ImageManager(new Driver());
        $image = $manager->decode($file->getRealPath());

        if ($image->width() > 800) {
            $image->scaleDown(width: 800);
        }

        $filename = 'quiz-images/' . \Illuminate\Support\Str::uuid() . '.webp';
        $encoded = $image->encode(new WebpEncoder(quality: 80));

        Storage::disk('public')->put($filename, $encoded);

        return $filename;
    }
}
