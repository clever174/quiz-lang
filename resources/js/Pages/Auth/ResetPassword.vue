<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import { Head, useForm } from '@inertiajs/vue3';

const props = defineProps({
    email: { type: String, required: true },
    token: { type: String, required: true },
});

const form = useForm({
    token: props.token,
    email: props.email,
    password: '',
    password_confirmation: '',
});

const submit = () => {
    form.post(route('password.store'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <GuestLayout>
        <Head title="Сброс пароля" />

        <form @submit.prevent="submit" class="flex flex-col gap-4">
            <div class="flex flex-col gap-1">
                <label for="email" class="text-sm font-medium text-gray-700">Email</label>
                <InputText
                    id="email"
                    v-model="form.email"
                    type="email"
                    required
                    autofocus
                    :invalid="!!form.errors.email"
                    class="w-full"
                />
                <InputError :message="form.errors.email" />
            </div>

            <div class="flex flex-col gap-1">
                <label for="password" class="text-sm font-medium text-gray-700">Новый пароль</label>
                <Password
                    id="password"
                    v-model="form.password"
                    required
                    toggleMask
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
                    required
                    :feedback="false"
                    toggleMask
                    :invalid="!!form.errors.password_confirmation"
                    input-class="w-full"
                    class="w-full"
                />
                <InputError :message="form.errors.password_confirmation" />
            </div>

            <div class="flex justify-end">
                <Button type="submit" label="Сбросить пароль" :loading="form.processing" />
            </div>
        </form>
    </GuestLayout>
</template>
