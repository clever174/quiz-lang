<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import { useToast } from 'primevue/usetoast';
import axios from 'axios';

const props = defineProps({
    prompts: Array,
    storage: Object,
});

const toast = useToast();

const prompts = ref(props.prompts.map(p => ({ ...p })));
const storage = ref(props.storage);

function save() {
    router.post(route('admin.settings.prompts'), { prompts: prompts.value }, {
        onSuccess: () => toast.add({ severity: 'success', summary: 'Сохранено', life: 2000 }),
    });
}

async function cleanupImages() {
    await axios.post(route('admin.settings.cleanup-images'));
    storage.value.orphans = [];
    toast.add({ severity: 'success', summary: 'Мусор удалён', life: 2000 });
}

function formatBytes(bytes) {
    if (bytes >= 1024 ** 3) return (bytes / 1024 ** 3).toFixed(1) + ' ГБ';
    if (bytes >= 1024 ** 2) return (bytes / 1024 ** 2).toFixed(1) + ' МБ';
    if (bytes >= 1024)      return (bytes / 1024).toFixed(1) + ' КБ';
    return bytes + ' Б';
}

const usedPercent = computed(() =>
    storage.value.total ? Math.round(storage.value.used / storage.value.total * 100) : 0
);

const orphansTotalSize = computed(() =>
    storage.value.orphans.reduce((sum, f) => sum + f.size, 0)
);
</script>

<template>
    <AdminLayout>
        <Head title="Настройки" />

        <div class="mb-6 flex items-center justify-between">
            <h1 class="text-2xl font-bold text-gray-800">⚙️ Настройки</h1>
            <Button label="Сохранить" icon="pi pi-save" @click="save" />
        </div>

        <div class="flex gap-8 items-start">
            <!-- Left: Prompts -->
            <div class="min-w-0 flex-1">
                <div class="mb-2 flex items-center gap-2">
                    <i class="pi pi-sparkles text-blue-500" />
                    <h2 class="text-lg font-semibold text-gray-700">Промпты для нейросети</h2>
                </div>
                <p class="mb-6 text-sm text-gray-500">
                    Используйте <code class="rounded bg-gray-100 px-1 py-0.5 font-mono text-xs">{topic}</code> — это место будет заменено на тему, которую введёт администратор в редакторе.
                </p>

                <div class="flex flex-col gap-6">
                    <div
                        v-for="prompt in prompts"
                        :key="prompt.id"
                        class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm"
                    >
                        <div class="mb-3 flex items-center gap-2">
                            <Tag :value="prompt.key.toUpperCase()" severity="secondary" />
                            <span class="font-medium text-gray-700">{{ prompt.label }}</span>
                        </div>
                        <Textarea
                            v-model="prompt.text"
                            rows="12"
                            class="w-full font-mono text-sm"
                        />
                    </div>
                </div>

                <div class="mt-6">
                    <Button label="Сохранить" icon="pi pi-save" @click="save" />
                </div>
            </div>

            <!-- Right: Storage -->
            <div class="w-80 shrink-0">
                <div class="mb-2 flex items-center gap-2">
                    <i class="pi pi-server text-gray-500" />
                    <h2 class="text-lg font-semibold text-gray-700">Хранилище</h2>
                </div>

                <!-- Disk usage -->
                <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm mb-4">
                    <div class="mb-3 flex justify-between text-sm text-gray-600">
                        <span>{{ formatBytes(storage.used) }} занято</span>
                        <span class="text-gray-400">{{ formatBytes(storage.free) }} свободно</span>
                    </div>
                    <div class="h-2 w-full overflow-hidden rounded-full bg-gray-100">
                        <div
                            class="h-full rounded-full transition-all"
                            :class="usedPercent > 85 ? 'bg-red-400' : usedPercent > 60 ? 'bg-yellow-400' : 'bg-blue-400'"
                            :style="{ width: usedPercent + '%' }"
                        />
                    </div>
                    <div class="mt-2 text-right text-xs text-gray-400">
                        {{ usedPercent }}% из {{ formatBytes(storage.total) }}
                    </div>
                </div>

                <!-- Orphans -->
                <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                    <div class="mb-3 flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <i class="pi pi-trash text-gray-400" />
                            <span class="font-medium text-gray-700">Мусор</span>
                            <span
                                class="rounded-full px-2 py-0.5 text-xs font-medium"
                                :class="storage.orphans.length ? 'bg-red-100 text-red-600' : 'bg-gray-100 text-gray-400'"
                            >
                                {{ storage.orphans.length }}
                            </span>
                        </div>
                        <Button
                            v-if="storage.orphans.length"
                            label="Удалить все"
                            icon="pi pi-trash"
                            severity="danger"
                            size="small"
                            text
                            @click="cleanupImages"
                        />
                    </div>

                    <div v-if="!storage.orphans.length" class="text-sm text-gray-400">
                        Неиспользуемых файлов нет
                    </div>

                    <div v-else>
                        <p class="mb-3 text-xs text-gray-400">
                            {{ orphansTotalSize ? formatBytes(orphansTotalSize) + ' можно освободить' : '' }}
                        </p>
                        <div class="flex flex-col gap-2 max-h-96 overflow-y-auto">
                            <div
                                v-for="file in storage.orphans"
                                :key="file.path"
                                class="flex items-center gap-2 rounded-lg bg-gray-50 p-2"
                            >
                                <img :src="file.url" class="h-10 w-10 shrink-0 rounded object-cover" />
                                <div class="min-w-0 flex-1">
                                    <div class="truncate text-xs text-gray-600">{{ file.path.split('/').pop() }}</div>
                                    <div class="text-xs text-gray-400">{{ formatBytes(file.size) }}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
