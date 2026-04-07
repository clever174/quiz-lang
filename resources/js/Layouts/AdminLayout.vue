<script setup>
import { Link } from '@inertiajs/vue3';
import { useTheme } from '@/composables/useTheme';
import SpaceBackground from '@/Components/SpaceBackground.vue';

// Ширина контейнера админки — меняйте здесь
const container = 'mx-auto max-w-7xl px-4 sm:px-6 lg:px-8';

const { current: theme } = useTheme();

const themeLabels = { light: 'Светлая', dark: 'Тёмная', space: 'Космос' };
</script>

<template>
    <SpaceBackground />
    <Toast />
    <div class="min-h-screen bg-gray-100">
        <nav class="border-b border-gray-100 bg-white">
            <div :class="container">
                <div class="flex h-16 items-center justify-between">
                    <!-- Logo + Nav -->
                    <div class="flex items-center gap-8">
                        <Link :href="route('admin.dashboard')" class="text-lg font-bold text-gray-800">
                            quiz-lang
                        </Link>

                        <div class="hidden sm:flex sm:gap-6">
                            <Link
                                :href="route('admin.quiz.index')"
                                class="text-sm font-medium text-gray-600 hover:text-gray-900"
                                :class="{ 'text-gray-900 underline': route().current('admin.quiz.*') }"
                            >
                                🧠 Quiz
                            </Link>
                            <Link
                                :href="route('admin.match.index')"
                                class="text-sm font-medium text-gray-600 hover:text-gray-900"
                                :class="{ 'text-gray-900 underline': route().current('admin.match.*') }"
                            >
                                🧩 Match
                            </Link>
                            <Link
                                :href="route('admin.settings')"
                                class="text-sm font-medium text-gray-600 hover:text-gray-900"
                                :class="{ 'text-gray-900 underline': route().current('admin.settings') }"
                            >
                                ⚙️ Настройки
                            </Link>
                        </div>
                    </div>

                    <!-- User menu -->
                    <div class="flex items-center gap-4">
                        <select
                            v-model="theme"
                            class="text-sm text-gray-600 bg-transparent border-0 outline-none focus:outline-none focus:ring-0 rounded-full cursor-pointer px-2 py-1"
                        >
                            <option v-for="(label, key) in themeLabels" :key="key" :value="key">{{ label }}</option>
                        </select>
                        <span class="text-sm text-gray-600">{{ $page.props.auth.user.name }}</span>
                        <Link
                            :href="route('logout')"
                            method="post"
                            as="button"
                            class="text-sm text-gray-600 underline hover:text-gray-900"
                        >
                            Выйти
                        </Link>
                    </div>
                </div>
            </div>
        </nav>

        <main :class="[container, 'py-8']">
            <slot />
        </main>
    </div>
</template>
