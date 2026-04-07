<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import { useToast } from 'primevue/usetoast';
import axios from 'axios';
import { VueDraggable } from 'vue-draggable-plus';

const props = defineProps({
    quiz: Object,
    promptTemplate: String,
});

const toast = useToast();

const title = ref(props.quiz.title);
const showPrompt = ref(false);
const promptTopic = ref('');

const promptText = computed(() => {
    const topic = promptTopic.value.trim() || 'Сделай 10 вопросов по теме Present Perfect';
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

const isPublished = ref(props.quiz.is_published);
const questions = ref(
    props.quiz.questions.map(q => ({
        id: q.id,
        question_text: q.question_text ?? '',
        image_path: q.image ?? null,
        image_url: q.image ? `/storage/${q.image}` : null,
        image_size: null,
        image_uploading: false,
        answers: q.answers.length === 4
            ? q.answers.map(a => ({ id: a.id, text: a.text, is_correct: a.is_correct }))
            : Array.from({ length: 4 }, () => ({ text: '', is_correct: false })),
    }))
);

function addQuestion() {
    questions.value.push({
        id: null,
        question_text: '',
        image_path: null,
        image_url: null,
        image_size: null,
        image_uploading: false,
        answers: Array.from({ length: 4 }, () => ({ text: '', is_correct: false })),
    });
}

function removeQuestion(index) {
    questions.value.splice(index, 1);
}

function setCorrect(qIndex, aIndex) {
    questions.value[qIndex].answers.forEach((a, i) => {
        a.is_correct = i === aIndex;
    });
}

async function onImageChange(qIndex, event) {
    const file = event.target.files[0];
    if (!file) return;

    const q = questions.value[qIndex];
    q.image_uploading = true;
    q.image_url = URL.createObjectURL(file);
    q.image_size = null;

    try {
        const formData = new FormData();
        formData.append('image', file);

        const { data } = await axios.post(route('admin.quiz.upload-image'), formData);

        q.image_path = data.path;
        q.image_url = data.url;
        q.image_size = data.size;
    } catch (e) {
        toast.add({ severity: 'error', summary: 'Ошибка загрузки', life: 3000 });
        q.image_url = null;
        q.image_path = null;
    } finally {
        q.image_uploading = false;
    }
}

function removeImage(qIndex) {
    const q = questions.value[qIndex];
    q.image_path = null;
    q.image_url = null;
    q.image_size = null;
}

function formatSize(bytes) {
    if (bytes < 1024) return bytes + ' Б';
    if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' КБ';
    return (bytes / 1024 / 1024).toFixed(2) + ' МБ';
}

const jsonText = ref('');
const showJsonInput = ref(false);

function parseAndImport(jsonString, errorLabel = 'Ошибка') {
    try {
        const data = JSON.parse(jsonString);

        if (!Array.isArray(data) || data.length === 0) {
            toast.add({ severity: 'error', summary: 'Неверный формат JSON', life: 3000 });
            return false;
        }

        let added = 0;
        for (const item of data) {
            const answers = (item.answers ?? []).slice(0, 4).map(a => ({
                id: null,
                text: String(a.text ?? ''),
                is_correct: Boolean(a.is_correct),
            }));

            while (answers.length < 4) {
                answers.push({ id: null, text: '', is_correct: false });
            }

            if (!answers.some(a => a.is_correct)) {
                answers[0].is_correct = true;
            }

            questions.value.push({
                id: null,
                question_text: String(item.question_text ?? ''),
                image_path: null,
                image_url: null,
                image_size: null,
                image_uploading: false,
                answers,
            });
            added++;
        }

        toast.add({
            severity: 'success',
            summary: `Добавлено ${added} ${added === 1 ? 'вопрос' : added < 5 ? 'вопроса' : 'вопросов'}`,
            detail: 'Не забудьте сохранить квиз',
            life: 4000,
        });
        return true;
    } catch {
        toast.add({ severity: 'error', summary: errorLabel, life: 3000 });
        return false;
    }
}

function importJson(event) {
    const file = event.target.files[0];
    if (!file) return;
    event.target.value = '';

    const reader = new FileReader();
    reader.onload = (e) => parseAndImport(e.target.result, 'Ошибка чтения файла');
    reader.readAsText(file);
}

function importFromText() {
    const ok = parseAndImport(jsonText.value.trim(), 'Неверный JSON');
    if (ok) {
        jsonText.value = '';
        showJsonInput.value = false;
    }
}

function save() {
    const formData = new FormData();
    formData.append('_method', 'PUT');
    formData.append('title', title.value);
    formData.append('is_published', isPublished.value ? '1' : '0');

    questions.value.forEach((q, i) => {
        if (q.id) formData.append(`questions[${i}][id]`, q.id);
        formData.append(`questions[${i}][question_text]`, q.question_text ?? '');
        formData.append(`questions[${i}][order]`, i);
        if (q.image_path) formData.append(`questions[${i}][image_path]`, q.image_path);
        q.answers.forEach((a, j) => {
            if (a.id) formData.append(`questions[${i}][answers][${j}][id]`, a.id);
            formData.append(`questions[${i}][answers][${j}][text]`, a.text);
            formData.append(`questions[${i}][answers][${j}][is_correct]`, a.is_correct ? '1' : '0');
        });
    });

    router.post(route('admin.quiz.update', props.quiz.id), formData, {
        forceFormData: true,
        onSuccess: () => toast.add({ severity: 'success', summary: 'Сохранено', life: 2000 }),
    });
}
</script>

<template>
    <AdminLayout>
        <Head :title="`Квиз — ${quiz.title}`" />
        <Toast />

        <div class="mb-6 flex items-center gap-4">
            <Button icon="pi pi-arrow-left" severity="secondary" text @click="router.get(route('admin.quiz.index'))" />
            <h1 class="text-2xl font-bold text-gray-800">Редактор квиза</h1>
        </div>

        <!-- Quiz title + published -->
        <div class="mb-8 flex max-w-2xl flex-col gap-4">
            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700">Название квиза</label>
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
                <Button label="Сохранить квиз" icon="pi pi-save" @click="save" />
            </div>

            <!-- JSON text input -->
            <div v-if="showJsonInput" class="flex flex-col gap-2">
                <Textarea
                    v-model="jsonText"
                    rows="6"
                    class="w-full font-mono text-xs"
                    placeholder='[{"question_text": "...", "answers": [...]}]'
                    autofocus
                />
                <div class="flex gap-2">
                    <Button label="Добавить вопросы" icon="pi pi-check" size="small" @click="importFromText" />
                    <Button label="Отмена" severity="secondary" size="small" text @click="showJsonInput = false; jsonText = ''" />
                </div>
            </div>

            <!-- AI Prompt helper -->
            <div class="rounded-xl border border-blue-100 bg-blue-50">
                <button
                    class="flex w-full items-center justify-between px-4 py-3 text-sm font-medium text-blue-700"
                    @click="showPrompt = !showPrompt"
                >
                    <span class="flex items-center gap-2">
                        <i class="pi pi-sparkles" />
                        Как сгенерировать вопросы через нейросеть?
                    </span>
                    <i class="pi text-xs transition-transform duration-200" :class="showPrompt ? 'pi-chevron-up' : 'pi-chevron-down'" />
                </button>

                <div v-if="showPrompt" class="border-t border-blue-100 px-4 pb-4 pt-3">
                    <p class="mb-3 text-xs text-blue-600">
                        Введите ваш запрос, скопируйте промпт и вставьте в ChatGPT / Claude / любую другую нейросеть.
                        Полученный JSON загрузите кнопкой «Вставить JSON».
                    </p>

                    <div class="mb-3">
                        <label class="mb-1 block text-xs font-medium text-blue-700">Ваш запрос для нейросети</label>
                        <Textarea
                            v-model="promptTopic"
                            placeholder="Например: Сделай 10 вопросов по теме Present Perfect..."
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

        <!-- Questions -->
        <div class="flex flex-col gap-6">
            <div
                v-for="(q, qIndex) in questions"
                :key="qIndex"
                class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm"
            >
                <div class="mb-4 flex items-center justify-between">
                    <span class="font-semibold text-gray-700">Вопрос {{ qIndex + 1 }}</span>
                    <Button icon="pi pi-trash" severity="danger" text size="small" @click="removeQuestion(qIndex)" />
                </div>

                <!-- Question text -->
                <div class="mb-4">
                    <label class="mb-1 block text-sm text-gray-600">Текст вопроса (необязательно)</label>
                    <Textarea
                        v-model="q.question_text"
                        rows="2"
                        class="w-full"
                        placeholder="Текст вопроса или оставьте пустым, если используется картинка"
                    />
                </div>

                <!-- Image -->
                <div class="mb-4">
                    <label class="mb-2 block text-sm text-gray-600">Картинка (необязательно)</label>

                    <!-- Preview -->
                    <div v-if="q.image_url" class="flex items-start gap-3">
                        <div class="relative">
                            <img :src="q.image_url" class="h-32 rounded-lg object-cover" :class="{ 'opacity-50': q.image_uploading }" />

                            <!-- Uploading overlay -->
                            <div v-if="q.image_uploading" class="absolute inset-0 flex items-center justify-center rounded-lg bg-black/30">
                                <i class="pi pi-spin pi-spinner text-2xl text-white" />
                            </div>
                        </div>

                        <div class="flex flex-col gap-2">
                            <!-- Size badge -->
                            <div v-if="q.image_uploading" class="flex items-center gap-1.5 text-xs text-gray-400">
                                <i class="pi pi-spin pi-spinner text-xs" />
                                Обработка...
                            </div>
                            <div v-else-if="q.image_size !== null" class="flex items-center gap-1.5 rounded-full bg-green-50 px-3 py-1 text-xs font-medium text-green-700">
                                <i class="pi pi-check-circle" />
                                WebP · {{ formatSize(q.image_size) }}
                            </div>

                            <Button icon="pi pi-times" label="Удалить" severity="danger" text size="small" @click="removeImage(qIndex)" />
                        </div>
                    </div>

                    <!-- Upload button -->
                    <div v-else>
                        <label
                            :for="`image_upload_${qIndex}`"
                            class="inline-flex cursor-pointer items-center gap-2 rounded-lg border border-dashed border-gray-300 bg-gray-50 px-4 py-3 text-sm text-gray-600 transition hover:border-blue-400 hover:bg-blue-50 hover:text-blue-600"
                        >
                            <i class="pi pi-image" />
                            Загрузить картинку
                        </label>
                        <input
                            :id="`image_upload_${qIndex}`"
                            type="file"
                            accept="image/*"
                            class="hidden"
                            @change="onImageChange(qIndex, $event)"
                        />
                    </div>
                </div>

                <!-- Answers -->
                <div>
                    <label class="mb-2 block text-sm text-gray-600">Варианты ответа (отметьте правильный, перетащите для сортировки)</label>
                    <VueDraggable
                        v-model="q.answers"
                        handle=".drag-handle"
                        animation="150"
                        class="flex flex-col gap-2"
                    >
                        <div
                            v-for="(answer, aIndex) in q.answers"
                            :key="answer.id ?? `new-${aIndex}`"
                            class="flex items-center gap-3 rounded-lg p-2 transition"
                            :class="answer.is_correct ? 'bg-green-50 ring-1 ring-green-300' : 'bg-gray-50'"
                        >
                            <i class="drag-handle pi pi-bars cursor-grab text-gray-300 hover:text-gray-500 active:cursor-grabbing" />
                            <span class="w-5 text-center text-xs font-bold text-gray-400">
                                {{ String.fromCharCode(65 + aIndex) }}
                            </span>
                            <RadioButton
                                :inputId="`q${qIndex}_a${aIndex}`"
                                :name="`q${qIndex}_correct`"
                                :value="aIndex"
                                :model-value="q.answers.findIndex(a => a.is_correct)"
                                @change="setCorrect(qIndex, aIndex)"
                            />
                            <InputText v-model="answer.text" :placeholder="`Вариант ${aIndex + 1}`" class="flex-1" />
                            <span v-if="answer.is_correct" class="w-20 text-right text-xs font-medium text-green-600">✓ правильный</span>
                            <span v-else class="w-20" />
                        </div>
                    </VueDraggable>
                </div>
            </div>
        </div>

        <!-- Actions -->
        <div class="mt-6 flex gap-3">
            <Button label="Добавить вопрос" icon="pi pi-plus" severity="secondary" @click="addQuestion" />
            <Button label="Сохранить квиз" icon="pi pi-save" @click="save" />
        </div>
    </AdminLayout>
</template>
