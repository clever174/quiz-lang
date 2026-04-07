<script setup>
import InputError from '@/Components/InputError.vue';
import { useForm } from '@inertiajs/vue3';
import { nextTick, ref } from 'vue';

const visible = ref(false);
const passwordInput = ref(null);

const form = useForm({ password: '' });

const confirmUserDeletion = () => {
    visible.value = true;
    nextTick(() => passwordInput.value?.$el?.querySelector('input')?.focus());
};

const deleteUser = () => {
    form.delete(route('profile.destroy'), {
        preserveScroll: true,
        onSuccess: () => { visible.value = false; },
        onError: () => passwordInput.value?.$el?.querySelector('input')?.focus(),
        onFinish: () => form.reset(),
    });
};

const closeModal = () => {
    visible.value = false;
    form.clearErrors();
    form.reset();
};
</script>

<template>
    <section class="space-y-6">
        <header>
            <h2 class="text-lg font-medium text-gray-900">Удаление аккаунта</h2>
            <p class="mt-1 text-sm text-gray-600">
                После удаления аккаунта все его данные будут безвозвратно уничтожены. Перед удалением сохраните необходимые данные.
            </p>
        </header>

        <Button severity="danger" label="Удалить аккаунт" @click="confirmUserDeletion" />

        <Dialog v-model:visible="visible" modal header="Удаление аккаунта" :style="{ width: '30rem' }">
            <p class="mb-4 text-sm text-gray-600">
                После удаления все данные будут безвозвратно уничтожены. Введите пароль для подтверждения.
            </p>

            <div class="flex flex-col gap-1">
                <label for="delete_password" class="text-sm font-medium text-gray-700">Пароль</label>
                <Password
                    id="delete_password"
                    ref="passwordInput"
                    v-model="form.password"
                    :feedback="false"
                    toggleMask
                    placeholder="Введите пароль"
                    :invalid="!!form.errors.password"
                    input-class="w-full"
                    class="w-full"
                    @keyup.enter="deleteUser"
                />
                <InputError :message="form.errors.password" />
            </div>

            <template #footer>
                <Button label="Отмена" severity="secondary" @click="closeModal" />
                <Button
                    label="Удалить аккаунт"
                    severity="danger"
                    :loading="form.processing"
                    @click="deleteUser"
                />
            </template>
        </Dialog>
    </section>
</template>
