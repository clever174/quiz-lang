<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';

const props = defineProps({
    quizzes: Object,
    filters: Object,
});

const search = ref(props.filters.search ?? '');
const loading = ref(false);

let searchTimeout = null;
watch(search, (val) => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => reload({ search: val, page: 1 }), 400);
});

function reload(params = {}) {
    loading.value = true;
    router.get(route('admin.quiz.index'), { ...props.filters, ...params }, {
        preserveState: true,
        preserveScroll: true,
        onFinish: () => loading.value = false,
    });
}

function onPage(event) {
    reload({ page: event.page + 1 });
}

function onSort(event) {
    reload({
        sort_field: event.sortField,
        sort_order: event.sortOrder === 1 ? 'asc' : 'desc',
        page: 1,
    });
}

function createQuiz() {
    router.post(route('admin.quiz.create'));
}

function editQuiz(id) {
    router.get(route('admin.quiz.edit', id));
}

function deleteQuiz(id) {
    if (confirm('Удалить квиз?')) {
        router.delete(route('admin.quiz.destroy', id));
    }
}
</script>

<template>
    <AdminLayout>
        <Head title="Админ — Quiz" />

        <div class="mb-6 flex items-center justify-between">
            <h1 class="text-2xl font-bold text-gray-800">🧠 Quiz</h1>
            <Button label="Добавить квиз" icon="pi pi-plus" @click="createQuiz" />
        </div>

        <div class="mb-4">
            <IconField>
                <InputIcon class="pi pi-search" />
                <InputText v-model="search" placeholder="Поиск по названию..." class="w-80" />
            </IconField>
        </div>

        <DataTable
            :value="quizzes.data"
            :lazy="true"
            :loading="loading"
            :total-records="quizzes.total"
            :rows="quizzes.per_page"
            :first="(quizzes.current_page - 1) * quizzes.per_page"
            paginator
            :rows-per-page-options="[20]"
            sort-mode="single"
            @page="onPage"
            @sort="onSort"
            striped-rows
            class="w-full"
        >
            <Column field="id" header="ID" sortable style="width: 80px" />
            <Column field="title" header="Название" sortable>
                <template #body="{ data }">
                    <a :href="route('quiz.show', data.id)" target="_blank" class="text-blue-500 hover:text-blue-600">
                        {{ data.title }}
                    </a>
                </template>
            </Column>
            <Column field="is_published" header="Опубликован" sortable style="width: 150px; text-align: center" header-class="text-center">
                <template #body="{ data }">
                    <div class="flex justify-center">
                        <Tag v-if="data.is_published" value="Да" severity="success" />
                        <Tag v-else value="Нет" severity="secondary" />
                    </div>
                </template>
            </Column>
            <Column field="created_at" header="Создан" sortable style="width: 180px">
                <template #body="{ data }">
                    {{ new Date(data.created_at).toLocaleDateString('ru-RU') }}
                </template>
            </Column>
            <Column header="Действия" style="width: 140px">
                <template #body="{ data }">
                    <div class="flex gap-2">
                        <Button
                            icon="pi pi-pencil"
                            size="small"
                            @click="editQuiz(data.id)"
                        />
                        <Button
                            icon="pi pi-trash"
                            size="small"
                            severity="danger"
                            @click="deleteQuiz(data.id)"
                        />
                    </div>
                </template>
            </Column>

            <template #empty>
                <div class="py-8 text-center text-gray-400">Квизы не найдены</div>
            </template>
        </DataTable>
    </AdminLayout>
</template>
