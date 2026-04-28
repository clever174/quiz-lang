<?php

namespace App\Console\Commands;

use App\Models\MatchPair;
use App\Models\QuizQuestion;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\Encoders\WebpEncoder;

class MigrateExternalImages extends Command
{
    protected $signature = 'images:migrate-external';
    protected $description = 'Download external image URLs, process and store them locally';

    public function handle(): int
    {
        ini_set('memory_limit', '512M');
        $questions = QuizQuestion::where('image', 'like', 'http%')->get();
        $pairs = MatchPair::where('item_a_image', 'like', 'http%')
            ->orWhere('item_b_image', 'like', 'http%')
            ->get();

        $total = $questions->count() + $pairs->sum(fn($p) =>
            (str_starts_with($p->item_a_image ?? '', 'http') ? 1 : 0) +
            (str_starts_with($p->item_b_image ?? '', 'http') ? 1 : 0)
        );

        if ($total === 0) {
            $this->info('No external images found.');
            return 0;
        }

        $this->info("Found {$total} external image(s). Processing...");
        $bar = $this->output->createProgressBar($total);
        $bar->start();

        $ok = 0;
        $fail = 0;

        foreach ($questions as $question) {
            [$path, $success] = $this->downloadAndProcess($question->image);
            if ($success) {
                $question->update(['image' => $path]);
                $ok++;
            } else {
                $this->newLine();
                $this->warn("Failed: {$question->image}");
                $fail++;
            }
            $bar->advance();
        }

        foreach ($pairs as $pair) {
            if (str_starts_with($pair->item_a_image ?? '', 'http')) {
                [$path, $success] = $this->downloadAndProcess($pair->item_a_image);
                if ($success) {
                    $pair->item_a_image = $path;
                    $ok++;
                } else {
                    $this->newLine();
                    $this->warn("Failed: {$pair->item_a_image}");
                    $fail++;
                }
                $bar->advance();
            }

            if (str_starts_with($pair->item_b_image ?? '', 'http')) {
                [$path, $success] = $this->downloadAndProcess($pair->item_b_image);
                if ($success) {
                    $pair->item_b_image = $path;
                    $ok++;
                } else {
                    $this->newLine();
                    $this->warn("Failed: {$pair->item_b_image}");
                    $fail++;
                }
                $bar->advance();
            }

            $pair->save();
        }

        $bar->finish();
        $this->newLine();
        $this->info("Done. Success: {$ok}, Failed: {$fail}");

        return 0;
    }

    private function downloadAndProcess(string $url): array
    {
        try {
            $response = Http::timeout(15)->get($url);

            if (!$response->successful()) {
                return [null, false];
            }

            $manager = new ImageManager(new Driver());
            $image = $manager->decode($response->body());

            if ($image->width() > 800) {
                $image->scaleDown(width: 800);
            }

            $path = 'quiz-images/' . Str::uuid() . '.webp';
            $encoded = $image->encode(new WebpEncoder(quality: 80));
            Storage::disk('images')->put($path, $encoded);

            unset($image, $encoded);

            return [$path, true];
        } catch (\Exception) {
            return [null, false];
        }
    }
}
