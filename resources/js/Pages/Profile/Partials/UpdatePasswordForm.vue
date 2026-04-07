<script setup>
import InputError from '@/Components/InputError.vue';
import { useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const passwordInput = ref(null);
const currentPasswordInput = ref(null);

const form = useForm({
    current_password: '',
    password: '',
    password_confirmation: '',
});

const updatePassword = () => {
    form.put(route('password.update'), {
        preserveScroll: true,
        onSuccess: () => form.reset(),
        onError: () => {
            if (form.errors.password) {
                form.reset('password', 'password_confirmation');
                passwordInput.value.$el.querySelector('input').focus();
            }
            if (form.errors.current_password) {
                form.reset('current_password');
                currentPasswordInput.value.$el.querySelector('input').focus();
            }
        },
    });
};
</script>

<template>
    <section>
        <header>
            <h2 class="text-lg font-medium text-gray-900">Изменение пароля</h2>
            <p class="mt-1 text-sm text-gray-600">Используйте длинный случайный пароль для защиты аккаунта.</p>
        </header>

        <form @submit.prevent="updatePassword" class="mt-6 flex flex-col gap-6">
            <div class="flex flex-col gap-1">
                <label for="current_password" class="text-sm font-medium text-gray-700">Текущий пароль</label>
                <Password
                    id="current_password"
                    ref="currentPasswordInput"
                    v-model="form.current_password"
                    :feedback="false"
                    toggleMask
                    autocomplete="current-password"
                    :invalid="!!form.errors.current_password"
                    input-class="w-full"
                    class="w-full"
                />
                <InputError :message="form.errors.current_password" />
            </div>

            <div class="flex flex-col gap-1">
                <label for="password" class="text-sm font-medium text-gray-700">Новый пароль</label>
                <Password
                    id="password"
                    ref="passwordInput"
                    v-model="form.password"
                    toggleMask
                    autocomplete="new-password"
                    :invalid="!!form.errors.password"
                    input-class="w-full"
                    class="w-full"
                />
                <InputError :message="form.errors.password" />
            </div>

            <div class="flex flex-col gap-1">
                <label for="password_confirmation" class="text-sm font-medium text-gray-700">Подтвердите пароль</label>
                <Password
                    id="password_confirmation"
                    v-model="form.password_confirmation"
                    :feedback="false"
                    toggleMask
                    autocomplete="new-password"
                    :invalid="!!form.errors.password_confirmation"
                    input-class="w-full"
                    class="w-full"
                />
                <InputError :message="form.errors.password_confirmation" />
            </div>

            <div class="flex items-center gap-4">
                <Button type="submit" label="Сохранить" :loading="form.processing" />
                <Transition
                    enter-active-class="transition ease-in-out"
                    enter-from-class="opacity-0"
                    leave-active-class="transition ease-in-out"
                    leave-to-class="opacity-0"
                >
                    <p v-if="form.recentlySuccessful" class="text-sm text-gray-600">Сохранено.</p>
                </Transition>
            </div>
        </form>
    </section>
</template>
