<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import { useToast } from 'primevue/usetoast';

const props = defineProps({
    prompts: Array,
    storage: Object,
});

const toast = useToast();

const prompts = ref(props.prompts.map(p => ({ ...p })));

function save() {
    router.post(route('admin.settings.prompts'), { prompts: prompts.value }, {
        onSuccess: () => toast.add({ severity: 'success', summary: 'Сохранено', life: 2000 }),
    });
}

function formatBytes(bytes) {
    if (bytes >= 1024 ** 3) return (bytes / 1024 ** 3).toFixed(1) + ' ГБ';
    if (bytes >= 1024 ** 2) return (bytes / 1024 ** 2).toFixed(1) + ' МБ';
    if (bytes >= 1024)      return (bytes / 1024).toFixed(1) + ' КБ';
    return bytes + ' Б';
}
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
                <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm mb-4 flex items-center gap-3">
                    <i class="pi pi-database text-gray-400" />
                    <div>
                        <div class="text-sm font-medium text-gray-700">{{ formatBytes(storage.used) }}</div>
                        <div class="text-xs text-gray-400">занято файлами приложения</div>
                    </div>
                </div>

            </div>
        </div>
    </AdminLayout>
</template>
