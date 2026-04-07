<script setup>
// Ширина контейнера публичной части — меняйте здесь
defineProps({
    maxWidth: { type: String, default: 'max-w-4xl' },
});
</script>

<template>
    <div class="min-h-screen bg-gray-50">
        <main class="mx-auto px-4 py-10" :class="maxWidth">
            <slot />
        </main>
    </div>
</template>
