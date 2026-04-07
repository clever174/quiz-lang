<?php

namespace Database\Seeders;

use App\Models\Prompt;
use Illuminate\Database\Seeder;

class PromptSeeder extends Seeder
{
    public function run(): void
    {
        Prompt::updateOrCreate(['key' => 'quiz'], [
            'label' => 'Quiz — генерация вопросов',
            'text' => 'Сгенерируй 10 вопросов по теме "{topic}" для квиза.

Верни ТОЛЬКО валидный JSON-массив без пояснений, строго в таком формате:

[
  {
    "question_text": "Текст вопроса?",
    "answers": [
      { "text": "Вариант 1", "is_correct": false },
      { "text": "Вариант 2", "is_correct": true },
      { "text": "Вариант 3", "is_correct": false },
      { "text": "Вариант 4", "is_correct": false }
    ]
  }
]

Требования:
- Ровно 4 варианта ответа в каждом вопросе
- Ровно один правильный ответ (is_correct: true)
- Правильный ответ должен стоять на разных позициях (не всегда второй или первый — чередуй 1-ю, 2-ю, 3-ю и 4-ю позиции)
- Только JSON, никакого текста до или после',
        ]);

        Prompt::updateOrCreate(['key' => 'match'], [
            'label' => 'Match — генерация пар',
            'text' => 'Сгенерируй 8 пар для упражнения на сопоставление по теме "{topic}".

Верни ТОЛЬКО валидный JSON-массив без пояснений, строго в таком формате:

[
  { "item_a": "Элемент A", "item_b": "Соответствие B" }
]

Требования:
- Ровно 2 поля в каждом объекте: item_a и item_b
- Пары должны быть уникальными и логически связанными
- Только JSON, никакого текста до или после',
        ]);
    }
}
