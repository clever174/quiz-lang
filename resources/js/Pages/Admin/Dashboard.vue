<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head } from '@inertiajs/vue3';
</script>

<template>
    <AdminLayout>
        <Head title="Админ — Главная" />

        <h1 class="mb-6 text-2xl font-bold text-gray-800">Панель управления</h1>

        <div class="grid grid-cols-2 gap-6 max-w-lg">
            <a :href="route('admin.quiz.index')" class="flex flex-col items-center justify-center gap-3 rounded-xl bg-blue-500 p-8 text-white shadow hover:bg-blue-600 transition">
                <span class="text-4xl">🧠</span>
                <span class="text-xl font-semibold">Quiz</span>
            </a>
            <a :href="route('admin.match.index')" class="flex flex-col items-center justify-center gap-3 rounded-xl bg-green-500 p-8 text-white shadow hover:bg-green-600 transition">
                <span class="text-4xl">🧩</span>
                <span class="text-xl font-semibold">Match</span>
            </a>
        </div>
    </AdminLayout>
</template>
