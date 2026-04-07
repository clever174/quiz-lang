<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

const props = defineProps({
    quizzes: Array,
});
</script>

<template>
    <AppLayout>
        <Head title="Quiz" />

        <!-- Header -->
        <div class="mb-8 text-center">
            <Link :href="route('home')" class="mb-4 inline-flex items-center gap-2 text-sm text-gray-500 hover:text-gray-700">
                ← Назад
            </Link>
            <h1 class="text-3xl font-bold text-gray-800">🧠 Quiz</h1>
            <p class="mt-2 text-gray-500">Выберите квиз и проверьте свои знания</p>
        </div>

        <!-- Empty state -->
        <div v-if="quizzes.length === 0" class="rounded-2xl bg-white p-12 text-center shadow-sm">
            <p class="text-4xl mb-3">📭</p>
            <p class="text-gray-500">Пока нет опубликованных квизов</p>
        </div>

        <!-- Quiz list -->
        <div v-else class="flex flex-col gap-3">
            <Link
                v-for="quiz in quizzes"
                :key="quiz.id"
                :href="route('quiz.show', quiz.id)"
                class="flex items-center justify-between rounded-2xl bg-white p-5 shadow-sm transition hover:shadow-md hover:-translate-y-0.5 active:scale-[0.99]"
            >
                <div>
                    <p class="font-semibold text-gray-800">{{ quiz.title }}</p>
                    <p class="mt-0.5 text-sm text-gray-400">{{ quiz.questions_count }} вопросов</p>
                </div>
                <span class="text-2xl">→</span>
            </Link>
        </div>
    </AppLayout>
</template>
