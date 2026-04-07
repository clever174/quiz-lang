<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

const props = defineProps({
    matches: Array,
});

const mechanicLabels = {
    match_up: 'Match Up',
    matching_pairs: 'Matching Pairs',
};

const mechanicIcons = {
    match_up: 'pi-arrows-h',
    matching_pairs: 'pi-th-large',
};
</script>

<template>
    <AppLayout>
        <Head title="Match" />

        <h1 class="mb-2 text-2xl font-bold text-gray-800">🧩 Match</h1>
        <p class="mb-8 text-sm text-gray-500">Выберите упражнение</p>

        <div v-if="matches.length === 0" class="rounded-2xl border border-dashed border-gray-200 py-16 text-center text-gray-400">
            Упражнения пока не добавлены
        </div>

        <div v-else class="flex flex-col gap-3">
            <Link
                v-for="m in matches"
                :key="m.id"
                :href="route('match.show', m.id)"
                class="flex items-center gap-4 rounded-2xl border border-gray-200 bg-white px-5 py-4 shadow-sm transition hover:border-blue-300 hover:shadow-md"
            >
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-blue-50">
                    <i :class="`pi ${mechanicIcons[m.mechanic]} text-blue-500`" />
                </div>
                <div class="min-w-0 flex-1">
                    <p class="truncate font-semibold text-gray-800">{{ m.title }}</p>
                    <p class="text-xs text-gray-400">{{ mechanicLabels[m.mechanic] }} · {{ m.pairs_count }} {{ m.pairs_count === 1 ? 'пара' : m.pairs_count < 5 ? 'пары' : 'пар' }}</p>
                </div>
                <i class="pi pi-chevron-right text-gray-300" />
            </Link>
        </div>
    </AppLayout>
</template>
