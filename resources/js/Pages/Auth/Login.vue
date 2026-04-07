<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

defineProps({
    canResetPassword: { type: Boolean },
    status: { type: String },
});

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <GuestLayout>
        <Head title="Вход" />

        <div v-if="status" class="mb-4 text-sm font-medium text-green-600">
            {{ status }}
        </div>

        <form @submit.prevent="submit" class="flex flex-col gap-4">
            <div class="flex flex-col gap-1">
                <label for="email" class="text-sm font-medium text-gray-700">Email</label>
                <InputText
                    id="email"
                    v-model="form.email"
                    type="email"
                    required
                    autofocus
                    autocomplete="username"
                    :invalid="!!form.errors.email"
                    class="w-full"
                />
                <InputError :message="form.errors.email" />
            </div>

            <div class="flex flex-col gap-1">
                <label for="password" class="text-sm font-medium text-gray-700">Пароль</label>
                <Password
                    id="password"
                    v-model="form.password"
                    required
                    :feedback="false"
                    toggleMask
                    :invalid="!!form.errors.password"
                    input-class="w-full"
                    class="w-full"
                />
                <InputError :message="form.errors.password" />
            </div>

            <div class="flex items-center gap-2">
                <Checkbox v-model="form.remember" inputId="remember" binary />
                <label for="remember" class="text-sm text-gray-600">Запомнить меня</label>
            </div>

            <div class="flex items-center justify-end gap-4">
                <Link
                    v-if="canResetPassword"
                    :href="route('password.request')"
                    class="text-sm text-gray-600 underline hover:text-gray-900"
                >
                    Забыли пароль?
                </Link>
                <Button
                    type="submit"
                    label="Войти"
                    :loading="form.processing"
                />
            </div>
        </form>
    </GuestLayout>
</template>
