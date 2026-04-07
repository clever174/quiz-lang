<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { ref, computed, nextTick } from 'vue';

const page = usePage();

const props = defineProps({
    quiz: Object,
});

function imgSrc(path) {
    if (!path) return null;
    return path.startsWith('http') ? path : `/storage/${path}`;
}

function shuffle(arr) {
    const a = [...arr];
    for (let i = a.length - 1; i > 0; i--) {
        const j = Math.floor(Math.random() * (i + 1));
        [a[i], a[j]] = [a[j], a[i]];
    }
    return a;
}

const questions = props.quiz.questions.map(q => ({
    ...q,
    answers: shuffle(q.answers),
}));
const total = questions.length;

// State
const step = ref('quiz'); // 'quiz' | 'result'
const current = ref(0);
const selected = ref(new Set()); // Set of selected answer indices
const confirmed = ref(false);
const answers = ref([]);         // { correct: bool }
const actionBtn = ref(null);

const question = computed(() => questions[current.value]);
const progress = computed(() => Math.round((current.value / total) * 100));
const score = computed(() => answers.value.filter(a => a.correct).length);
const isMulti = computed(() => question.value.answers.filter(a => a.is_correct).length > 1);

function choose(aIndex) {
    if (confirmed.value) return;
    const s = new Set(selected.value);
    if (s.has(aIndex)) {
        s.delete(aIndex);
    } else {
        if (!isMulti.value) s.clear();
        s.add(aIndex);
    }
    selected.value = s;
}

function confirm() {
    if (selected.value.size === 0) return;
    confirmed.value = true;

    const correctIndices = new Set(
        question.value.answers.map((a, i) => a.is_correct ? i : null).filter(i => i !== null)
    );
    const isCorrect =
        selected.value.size === correctIndices.size &&
        [...selected.value].every(i => correctIndices.has(i));

    answers.value.push({ correct: isCorrect });

    if (current.value === total - 1) {
        setTimeout(() => { step.value = 'result'; }, 1000);
    } else {
        nextTick(() => {
            if (!actionBtn.value) return;
            const rect = actionBtn.value.getBoundingClientRect();
            const offset = rect.bottom + 20 - window.innerHeight;
            if (offset > 0) window.scrollBy({ top: offset, behavior: 'smooth' });
        });
    }
}

function next() {
    if (current.value < total - 1) {
        current.value++;
        selected.value = new Set();
        confirmed.value = false;
    } else {
        step.value = 'result';
    }
}

function restart() {
    current.value = 0;
    selected.value = new Set();
    confirmed.value = false;
    answers.value = [];
    step.value = 'quiz';
}

function answerClass(aIndex) {
    const answer = question.value.answers[aIndex];
    const isSelected = selected.value.has(aIndex);
    if (!confirmed.value) {
        return isSelected
            ? 'border-blue-500 bg-blue-50 text-blue-800'
            : 'border-gray-200 bg-white hover:border-blue-300 hover:bg-blue-50';
    }
    if (answer.is_correct) return 'border-green-500 bg-green-50 text-green-800';
    if (isSelected && !answer.is_correct) return 'border-red-400 bg-red-50 text-red-700';
    return 'border-gray-200 bg-white opacity-50';
}

const feedbackText = computed(() => {
    if (!confirmed.value) return '';
    if (answers.value.at(-1).correct) return 'Правильно!';
    const selectedTexts = question.value.answers.filter((_, i) => selected.value.has(i)).map(a => a.text).join(', ');
    const correctTexts = question.value.answers.filter(a => a.is_correct).map(a => a.text).join(', ');
    return `Неверно. Вы выбрали: ${selectedTexts || '—'}\nПравильный ответ: ${correctTexts}`;
});

const scoreLabel = computed(() => {
    const pct = score.value / total;
    if (pct === 1) return { text: 'Отлично! Все правильно! 🎉', color: 'text-green-600' };
    if (pct >= 0.7) return { text: 'Хороший результат! 👍', color: 'text-blue-600' };
    if (pct >= 0.4) return { text: 'Неплохо, но есть куда расти 💪', color: 'text-yellow-600' };
    return { text: 'Попробуйте ещё раз 🔄', color: 'text-red-500' };
});
</script>

<template>
    <AppLayout>
        <Head :title="quiz.title" />

        <div>

            <!-- Admin draft warning -->
            <div
                v-if="!quiz.is_published && page.props.auth?.user?.is_admin"
                class="mb-4 flex items-start gap-3 rounded-xl border border-yellow-300 bg-yellow-50 px-4 py-3 text-sm text-yellow-800"
            >
                <i class="pi pi-exclamation-triangle mt-0.5 shrink-0 text-yellow-500" />
                <span>Квиз не опубликован. Перед отправкой ученикам не забудьте <Link :href="route('admin.quiz.edit', quiz.id)" class="font-semibold underline hover:text-yellow-900">опубликовать квиз</Link>.</span>
            </div>

            <!-- QUIZ -->
            <template v-if="step === 'quiz'">
                <!-- Top bar -->
                <div class="mb-6 flex items-center gap-3">
                    <Link :href="route('quiz.index')" class="text-gray-400 hover:text-gray-600">
                        <span class="text-xl">←</span>
                    </Link>
                    <div class="flex-1">
                        <div class="mb-1 flex justify-between text-xs text-gray-400">
                            <span>{{ current + 1 }} / {{ total }}</span>
                            <span>{{ progress }}%</span>
                        </div>
                        <div class="h-2 w-full overflow-hidden rounded-full bg-gray-100">
                            <div
                                class="h-full rounded-full bg-blue-500 transition-all duration-300"
                                :style="{ width: progress + '%' }"
                            />
                        </div>
                    </div>
                </div>

                <!-- Question card -->
                <div class="rounded-3xl bg-white p-6 shadow-sm">
                    <!-- Image -->
                    <div v-if="question.image" class="mb-5">
                        <img
                            :src="imgSrc(question.image)"
                            class="w-full rounded-2xl object-cover"
                            style="max-height: 260px"
                        />
                    </div>

                    <!-- Text -->
                    <p v-if="question.question_text" class="mb-6 text-lg font-semibold leading-snug text-gray-800">
                        {{ question.question_text }}
                    </p>

                    <!-- Multi-select hint -->
                    <p v-if="isMulti && !confirmed" class="mb-3 text-xs text-blue-500">
                        Выберите все правильные варианты
                    </p>

                    <!-- Answers -->
                    <div class="flex flex-col gap-3">
                        <button
                            v-for="(answer, aIndex) in question.answers"
                            :key="answer.id"
                            @click="choose(aIndex)"
                            class="flex w-full items-center gap-3 rounded-2xl border-2 px-4 py-3 text-left text-sm font-medium transition-all duration-150"
                            :class="answerClass(aIndex)"
                        >
                            <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-gray-100 text-xs font-bold text-gray-500">
                                {{ aIndex + 1 }}
                            </span>
                            <span>{{ answer.text }}</span>
                            <span v-if="confirmed && answer.is_correct" class="ml-auto text-green-500">✓</span>
                            <span v-else-if="confirmed && selected.has(aIndex) && !answer.is_correct" class="ml-auto text-red-400">✗</span>
                        </button>
                    </div>

                    <!-- Feedback -->
                    <div v-if="confirmed" class="mt-4 whitespace-pre-line rounded-xl px-4 py-3 text-sm font-medium"
                        :class="answers.at(-1).correct ? 'bg-green-50 text-green-700' : 'bg-red-50 text-red-700'">
                        {{ feedbackText }}
                    </div>
                </div>

                <!-- Actions -->
                <div class="mt-5 pb-5" ref="actionBtn">
                    <button
                        v-if="!confirmed"
                        @click="confirm"
                        :disabled="selected.size === 0"
                        class="w-full rounded-2xl bg-blue-500 py-4 text-base font-semibold text-white shadow transition hover:bg-blue-600 disabled:opacity-40"
                    >
                        Ответить
                    </button>
                    <button
                        v-else
                        @click="next"
                        class="w-full rounded-2xl bg-gray-800 py-4 text-base font-semibold text-white shadow transition hover:bg-gray-900"
                    >
                        {{ current < total - 1 ? 'Следующий вопрос →' : 'Посмотреть результат' }}
                    </button>
                </div>
            </template>

            <!-- RESULT -->
            <template v-else>
                <div class="flex flex-col items-center text-center">
                    <div class="mb-6 flex h-32 w-32 items-center justify-center rounded-full bg-white shadow-sm">
                        <span class="text-5xl">
                            {{ score === total ? '🏆' : score >= total * 0.7 ? '🎯' : score >= total * 0.4 ? '📚' : '🔄' }}
                        </span>
                    </div>

                    <h2 class="mb-1 text-2xl font-bold text-gray-800">Результат</h2>
                    <p class="mb-2 text-5xl font-bold text-blue-500">{{ score }}<span class="text-2xl text-gray-400">/{{ total }}</span></p>
                    <p class="mb-8 text-base font-medium" :class="scoreLabel.color">{{ scoreLabel.text }}</p>

                    <!-- Answer breakdown -->
                    <div class="mb-8 w-full rounded-3xl bg-white p-5 shadow-sm text-left">
                        <p class="mb-3 text-sm font-semibold text-gray-500 uppercase tracking-wide">Разбор ответов</p>
                        <div
                            v-for="(q, i) in questions"
                            :key="i"
                            class="flex items-start gap-3 border-b border-gray-100 py-3 last:border-0"
                        >
                            <span class="mt-0.5 shrink-0 text-lg">
                                {{ answers[i]?.correct ? '✅' : '❌' }}
                            </span>
                            <div class="min-w-0">
                                <p class="text-sm font-medium text-gray-700 truncate">
                                    {{ q.question_text || `Вопрос ${i + 1}` }}
                                </p>
                                <p class="text-xs text-gray-400 mt-0.5">
                                    Правильно: {{ q.answers.filter(a => a.is_correct).map(a => a.text).join(', ') }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="flex w-full flex-col gap-3">
                        <button
                            @click="restart"
                            class="w-full rounded-2xl bg-blue-500 py-4 text-base font-semibold text-white shadow transition hover:bg-blue-600"
                        >
                            Пройти ещё раз
                        </button>
                        <Link
                            :href="route('quiz.index')"
                            class="w-full rounded-2xl border-2 border-gray-200 py-4 text-center text-base font-semibold text-gray-700 transition hover:border-gray-300"
                        >
                            К списку квизов
                        </Link>
                    </div>
                </div>
            </template>

        </div>
    </AppLayout>
</template>
