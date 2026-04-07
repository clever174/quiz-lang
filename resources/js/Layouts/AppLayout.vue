<script setup>
import { useTheme } from '@/composables/useTheme';
import SpaceBackground from '@/Components/SpaceBackground.vue';

// Ширина контейнера публичной части — меняйте здесь
defineProps({
    maxWidth: { type: String, default: 'max-w-4xl' },
});

const { current: theme } = useTheme();
const themeLabels = { light: 'Светлая', dark: 'Тёмная', space: 'Космос' };
</script>

<template>
    <SpaceBackground />
    <div class="min-h-screen bg-gray-50">
        <nav class="border-b border-gray-100 bg-white">
            <div class="mx-auto max-w-4xl px-4 flex h-12 items-center justify-end">
                <select
                    v-model="theme"
                    class="text-sm text-gray-500 bg-transparent border-0 outline-none cursor-pointer"
                >
                    <option v-for="(label, key) in themeLabels" :key="key" :value="key">{{ label }}</option>
                </select>
            </div>
        </nav>
        <main class="mx-auto px-4 py-10" :class="maxWidth">
            <slot />
        </main>
    </div>
</template>
