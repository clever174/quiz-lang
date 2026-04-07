<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import { Head, useForm } from '@inertiajs/vue3';

defineProps({
    status: { type: String },
});

const form = useForm({ email: '' });

const submit = () => {
    form.post(route('password.email'));
};
</script>

<template>
    <GuestLayout>
        <Head title="Восстановление пароля" />

        <div class="mb-4 text-sm text-gray-600">
            Забыли пароль? Введите ваш email и мы отправим вам ссылку для сброса пароля.
        </div>

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

            <div class="flex justify-end">
                <Button type="submit" label="Отправить ссылку" :loading="form.processing" />
            </div>
        </form>
    </GuestLayout>
</template>
