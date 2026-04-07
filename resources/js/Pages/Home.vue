<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
</script>

<template>
    <AppLayout>
        <Head title="Главная" />

        <div class="flex min-h-screen items-center justify-center">
            <div class="flex flex-col items-center gap-8">
                <h1 class="text-3xl font-bold text-gray-800">Выберите тип упражнений</h1>

                <div class="flex gap-6">
                    <Link :href="route('quiz.index')">
                        <div class="flex h-48 w-56 cursor-pointer flex-col items-center justify-center gap-4 rounded-2xl bg-blue-500 text-white shadow-lg transition hover:bg-blue-600 hover:shadow-xl">
                            <span class="text-5xl">🧠</span>
                            <span class="text-2xl font-semibold">Quiz</span>
                            <span class="text-sm opacity-80">Выбор ответа</span>
                        </div>
                    </Link>

                    <Link :href="route('match.index')">
                        <div class="flex h-48 w-56 cursor-pointer flex-col items-center justify-center gap-4 rounded-2xl bg-green-500 text-white shadow-lg transition hover:bg-green-600 hover:shadow-xl">
                            <span class="text-5xl">🧩</span>
                            <span class="text-2xl font-semibold">Match</span>
                            <span class="text-sm opacity-80">Сопоставление</span>
                        </div>
                    </Link>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
