<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import { useToast } from 'primevue/usetoast';
import axios from 'axios';
import { VueDraggable } from 'vue-draggable-plus';

const props = defineProps({
    match: Object,
    promptTemplate: String,
});

const toast = useToast();

const title = ref(props.match.title);
const isPublished = ref(props.match.is_published);
const showPrompt = ref(false);
const promptTopic = ref('');

const promptText = computed(() => {
    const topic = promptTopic.value.trim() || 'Сгенерируй 8 пар для упражнения на сопоставление по теме Present Perfect';
    return (props.promptTemplate || '').replace('{topic}', topic);
});

function copyPrompt() {
    if (navigator.clipboard) {
        navigator.clipboard.writeText(promptText.value)
            .then(() => toast.add({ severity: 'success', summary: 'Скопировано в буфер', life: 2000 }))
            .catch(() => copyFallback());
    } else {
        copyFallback();
    }
}

function copyFallback() {
    const el = document.createElement('textarea');
    el.value = promptText.value;
    el.style.position = 'fixed';
    el.style.opacity = '0';
    document.body.appendChild(el);
    el.select();
    document.execCommand('copy');
    document.body.removeChild(el);
    toast.add({ severity: 'success', summary: 'Скопировано в буфер', life: 2000 });
}

const pairs = ref(
    props.match.pairs.map(p => ({
        id: p.id,
        item_a: p.item_a ?? '',
        item_a_image: p.item_a_image ?? null,
        item_a_image_url: resolveUrl(p.item_a_image),
        item_a_image_size: null,
        item_a_uploading: false,
        item_a_url_input: '',
        item_b: p.item_b ?? '',
        item_b_image: p.item_b_image ?? null,
        item_b_image_url: resolveUrl(p.item_b_image),
        item_b_image_size: null,
        item_b_uploading: false,
        item_b_url_input: '',
    }))
);

function addPair() {
    pairs.value.push({
        id: null,
        item_a: '', item_a_image: null, item_a_image_url: null, item_a_image_size: null, item_a_uploading: false, item_a_url_input: '',
        item_b: '', item_b_image: null, item_b_image_url: null, item_b_image_size: null, item_b_uploading: false, item_b_url_input: '',
    });
}

function removePair(index) {
    pairs.value.splice(index, 1);
}

async function onImageChange(pIndex, side, event) {
    const file = event.target.files[0];
    if (!file) return;

    const p = pairs.value[pIndex];
    const uploading = `item_${side}_uploading`;
    const urlKey    = `item_${side}_image_url`;
    const sizeKey   = `item_${side}_image_size`;
    const pathKey   = `item_${side}_image`;

    p[uploading] = true;
    p[urlKey] = URL.createObjectURL(file);
    p[sizeKey] = null;

    try {
        const formData = new FormData();
        formData.append('image', file);
        const { data } = await axios.post(route('admin.match.upload-image'), formData);
        p[pathKey] = data.path;
        p[urlKey]  = data.url;
        p[sizeKey] = data.size;
    } catch {
        toast.add({ severity: 'error', summary: 'Ошибка загрузки', life: 3000 });
        p[urlKey]  = null;
        p[pathKey] = null;
    } finally {
        p[uploading] = false;
    }
}

function resolveUrl(path) {
    if (!path) return null;
    return path.startsWith('http') ? path : `/storage/${path}`;
}

function removeImage(pIndex, side) {
    const p = pairs.value[pIndex];
    p[`item_${side}_image`]      = null;
    p[`item_${side}_image_url`]  = null;
    p[`item_${side}_image_size`] = null;
    p[`item_${side}_url_input`]  = '';
}

function setImageFromUrl(pIndex, side) {
    const p = pairs.value[pIndex];
    const url = p[`item_${side}_url_input`].trim();
    if (!url) return;
    p[`item_${side}_image`]      = url;
    p[`item_${side}_image_url`]  = url;
    p[`item_${side}_image_size`] = null;
    p[`item_${side}_url_input`]  = '';
}

function formatSize(bytes) {
    if (bytes < 1024) return bytes + ' Б';
    if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' КБ';
    return (bytes / 1024 / 1024).toFixed(2) + ' МБ';
}

const jsonText = ref('');
const showJsonInput = ref(false);

function parseAndImport(jsonString) {
    try {
        const data = JSON.parse(jsonString);

        if (!Array.isArray(data) || data.length === 0) {
            toast.add({ severity: 'error', summary: 'Неверный формат JSON', life: 3000 });
            return false;
        }

        let added = 0;
        for (const item of data) {
            pairs.value.push({
                id: null,
                item_a: String(item.item_a ?? ''),
                item_a_image: null, item_a_image_url: null, item_a_image_size: null, item_a_uploading: false,
                item_b: String(item.item_b ?? ''),
                item_b_image: null, item_b_image_url: null, item_b_image_size: null, item_b_uploading: false,
            });
            added++;
        }

        toast.add({
            severity: 'success',
            summary: `Добавлено ${added} ${added === 1 ? 'пара' : added < 5 ? 'пары' : 'пар'}`,
            detail: 'Не забудьте сохранить матч',
            life: 4000,
        });
        return true;
    } catch {
        toast.add({ severity: 'error', summary: 'Неверный JSON', life: 3000 });
        return false;
    }
}

function importFromText() {
    const ok = parseAndImport(jsonText.value.trim());
    if (ok) {
        jsonText.value = '';
        showJsonInput.value = false;
    }
}

function save() {
    const data = {
        _method: 'PUT',
        title: title.value,
        is_published: isPublished.value,
        pairs: pairs.value.map((p, i) => ({
            ...(p.id ? { id: p.id } : {}),
            item_a:       p.item_a,
            item_a_image: p.item_a_image,
            item_b:       p.item_b,
            item_b_image: p.item_b_image,
            order: i,
        })),
    };

    router.post(route('admin.match.update', props.match.id), data, {
        onSuccess: () => toast.add({ severity: 'success', summary: 'Сохранено', life: 2000 }),
    });
}
</script>

<template>
    <AdminLayout>
        <Head :title="`Match — ${match.title}`" />
        <Toast />

        <div class="mb-6 flex items-center gap-4">
            <Button icon="pi pi-arrow-left" severity="secondary" text @click="router.get(route('admin.match.index'))" />
            <h1 class="text-2xl font-bold text-gray-800">Редактор матча</h1>
        </div>

        <!-- Title + mechanic + published -->
        <div class="mb-8 flex max-w-2xl flex-col gap-4">
            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700">Название</label>
                <InputText v-model="title" class="w-full" placeholder="Введите название" />
            </div>

            <div class="flex items-center justify-between gap-3">
                <div class="flex items-center gap-3">
                    <ToggleSwitch v-model="isPublished" inputId="is_published" />
                    <label for="is_published" class="cursor-pointer text-sm font-medium text-gray-700">Опубликован</label>
                    <Tag v-if="isPublished" value="Публичный" severity="success" />
                    <Tag v-else value="Черновик" severity="secondary" />
                </div>
                <label
                    class="inline-flex cursor-pointer items-center gap-2 rounded-lg border border-dashed border-gray-300 px-3 py-2 text-sm text-gray-500 transition hover:border-blue-400 hover:bg-blue-50 hover:text-blue-600"
                    @click="showJsonInput = !showJsonInput"
                >
                    <i class="pi pi-code text-sm" />
                    Вставить JSON
                </label>
                <Button label="Сохранить матч" icon="pi pi-save" @click="save" />
            </div>

            <!-- JSON text input -->
            <div v-if="showJsonInput" class="flex flex-col gap-2">
                <Textarea
                    v-model="jsonText"
                    rows="6"
                    class="w-full font-mono text-xs"
                    placeholder='[{"item_a": "cat", "item_b": "кошка"}]'
                    autofocus
                />
                <div class="flex gap-2">
                    <Button label="Добавить пары" icon="pi pi-check" size="small" @click="importFromText" />
                    <Button label="Отмена" severity="secondary" size="small" text @click="showJsonInput = false; jsonText = ''" />
                </div>
            </div>

            <!-- AI Prompt -->
            <div class="rounded-xl border border-blue-100 bg-blue-50">
                <button
                    class="flex w-full items-center justify-between px-4 py-3 text-sm font-medium text-blue-700"
                    @click="showPrompt = !showPrompt"
                >
                    <span class="flex items-center gap-2">
                        <i class="pi pi-sparkles" />
                        Сгенерировать пары через нейросеть
                    </span>
                    <i class="pi text-xs transition-transform duration-200" :class="showPrompt ? 'pi-chevron-up' : 'pi-chevron-down'" />
                </button>

                <div v-if="showPrompt" class="border-t border-blue-100 px-4 pb-4 pt-3">
                    <p class="mb-3 text-xs text-blue-600">
                        Введите ваш запрос, скопируйте промпт и вставьте в ChatGPT / Claude.
                    </p>
                    <div class="mb-3">
                        <label class="mb-1 block text-xs font-medium text-blue-700">Ваш запрос для нейросети</label>
                        <Textarea
                            v-model="promptTopic"
                            placeholder="Например: Сгенерируй 8 пар для упражнения на сопоставление по теме Present Perfect..."
                            rows="3"
                            class="w-full text-sm"
                        />
                    </div>
                    <div class="relative">
                        <pre class="max-h-40 overflow-y-auto whitespace-pre-wrap rounded-lg bg-white p-3 text-xs text-gray-700 ring-1 ring-blue-200">{{ promptText }}</pre>
                        <button
                            @click="copyPrompt"
                            class="absolute right-2 top-2 flex items-center gap-1 rounded-md bg-blue-500 px-2 py-1 text-xs font-medium text-white transition hover:bg-blue-600"
                        >
                            <i class="pi pi-copy text-xs" />
                            Копировать
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Pairs -->
        <div class="mb-2 flex items-center justify-between">
            <h2 class="text-lg font-semibold text-gray-700">Пары</h2>
            <span class="text-sm text-gray-400">{{ pairs.length }} {{ pairs.length === 1 ? 'пара' : pairs.length < 5 ? 'пары' : 'пар' }}</span>
        </div>

        <!-- Column headers -->
        <div v-if="pairs.length" class="mb-2 grid grid-cols-[32px_1fr_40px_1fr_40px] gap-3 px-2 text-xs font-semibold uppercase tracking-wide text-gray-400">
            <div></div>
            <div>A</div>
            <div></div>
            <div>B</div>
            <div></div>
        </div>

        <VueDraggable v-model="pairs" handle=".drag-handle" animation="150" class="flex flex-col gap-3">
            <div
                v-for="(pair, pIndex) in pairs"
                :key="pair.id ?? `new-${pIndex}`"
                class="grid grid-cols-[32px_1fr_40px_1fr_40px] items-start gap-3 rounded-xl border border-gray-200 bg-white p-4 shadow-sm"
            >
                <!-- Drag handle -->
                <div class="flex h-full items-center justify-center">
                    <i class="drag-handle pi pi-bars cursor-grab text-gray-300 hover:text-gray-500 active:cursor-grabbing" />
                </div>

                <!-- Item A -->
                <div class="flex flex-col gap-2">
                    <InputText v-model="pair.item_a" placeholder="Текст A" class="w-full text-sm" />

                    <div v-if="pair.item_a_image_url" class="flex items-start gap-2">
                        <div class="relative shrink-0">
                            <img :src="pair.item_a_image_url" class="h-20 rounded-lg object-cover" :class="{ 'opacity-50': pair.item_a_uploading }" />
                            <div v-if="pair.item_a_uploading" class="absolute inset-0 flex items-center justify-center rounded-lg bg-black/30">
                                <i class="pi pi-spin pi-spinner text-white" />
                            </div>
                        </div>
                        <div class="flex flex-col gap-1">
                            <div v-if="pair.item_a_uploading" class="text-xs text-gray-400"><i class="pi pi-spin pi-spinner text-xs" /> Обработка...</div>
                            <div v-else-if="pair.item_a_image_size !== null" class="rounded-full bg-green-50 px-2 py-0.5 text-xs font-medium text-green-700">
                                WebP · {{ formatSize(pair.item_a_image_size) }}
                            </div>
                            <Button icon="pi pi-times" label="Удалить" severity="danger" text size="small" @click="removeImage(pIndex, 'a')" />
                        </div>
                    </div>
                    <div v-else class="flex flex-col gap-1.5">
                        <label
                            :for="`img_a_${pIndex}`"
                            class="inline-flex cursor-pointer items-center gap-1 rounded-lg border border-dashed border-gray-300 bg-gray-50 px-3 py-1.5 text-xs text-gray-500 transition hover:border-blue-400 hover:bg-blue-50 hover:text-blue-600"
                        >
                            <i class="pi pi-image text-xs" /> Файл
                        </label>
                        <input :id="`img_a_${pIndex}`" type="file" accept="image/*" class="hidden" @change="onImageChange(pIndex, 'a', $event)" />
                        <div class="flex items-center gap-1">
                            <InputText v-model="pair.item_a_url_input" placeholder="URL картинки..." class="flex-1 text-xs" @keydown.enter="setImageFromUrl(pIndex, 'a')" />
                            <Button icon="pi pi-check" size="small" severity="secondary" :disabled="!pair.item_a_url_input.trim()" @click="setImageFromUrl(pIndex, 'a')" />
                        </div>
                    </div>
                </div>

                <!-- Divider -->
                <div class="flex h-full items-center justify-center text-gray-300">
                    <i class="pi pi-arrows-h" />
                </div>

                <!-- Item B -->
                <div class="flex flex-col gap-2">
                    <InputText v-model="pair.item_b" placeholder="Текст B" class="w-full text-sm" />

                    <div v-if="pair.item_b_image_url" class="flex items-start gap-2">
                        <div class="relative shrink-0">
                            <img :src="pair.item_b_image_url" class="h-20 rounded-lg object-cover" :class="{ 'opacity-50': pair.item_b_uploading }" />
                            <div v-if="pair.item_b_uploading" class="absolute inset-0 flex items-center justify-center rounded-lg bg-black/30">
                                <i class="pi pi-spin pi-spinner text-white" />
                            </div>
                        </div>
                        <div class="flex flex-col gap-1">
                            <div v-if="pair.item_b_uploading" class="text-xs text-gray-400"><i class="pi pi-spin pi-spinner text-xs" /> Обработка...</div>
                            <div v-else-if="pair.item_b_image_size !== null" class="rounded-full bg-green-50 px-2 py-0.5 text-xs font-medium text-green-700">
                                WebP · {{ formatSize(pair.item_b_image_size) }}
                            </div>
                            <Button icon="pi pi-times" label="Удалить" severity="danger" text size="small" @click="removeImage(pIndex, 'b')" />
                        </div>
                    </div>
                    <div v-else class="flex flex-col gap-1.5">
                        <label
                            :for="`img_b_${pIndex}`"
                            class="inline-flex cursor-pointer items-center gap-1 rounded-lg border border-dashed border-gray-300 bg-gray-50 px-3 py-1.5 text-xs text-gray-500 transition hover:border-blue-400 hover:bg-blue-50 hover:text-blue-600"
                        >
                            <i class="pi pi-image text-xs" /> Файл
                        </label>
                        <input :id="`img_b_${pIndex}`" type="file" accept="image/*" class="hidden" @change="onImageChange(pIndex, 'b', $event)" />
                        <div class="flex items-center gap-1">
                            <InputText v-model="pair.item_b_url_input" placeholder="URL картинки..." class="flex-1 text-xs" @keydown.enter="setImageFromUrl(pIndex, 'b')" />
                            <Button icon="pi pi-check" size="small" severity="secondary" :disabled="!pair.item_b_url_input.trim()" @click="setImageFromUrl(pIndex, 'b')" />
                        </div>
                    </div>
                </div>

                <!-- Delete -->
                <div class="flex h-full items-center justify-center">
                    <Button icon="pi pi-trash" severity="danger" text size="small" @click="removePair(pIndex)" />
                </div>
            </div>
        </VueDraggable>

        <!-- Actions -->
        <div class="mt-6 flex gap-3">
            <Button label="Добавить пару" icon="pi pi-plus" severity="secondary" @click="addPair" />
            <Button label="Сохранить матч" icon="pi pi-save" @click="save" />
        </div>
    </AdminLayout>
</template>
