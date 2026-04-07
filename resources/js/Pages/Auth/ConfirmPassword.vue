<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import { Head, useForm } from '@inertiajs/vue3';

const form = useForm({ password: '' });

const submit = () => {
    form.post(route('password.confirm'), {
        onFinish: () => form.reset(),
    });
};
</script>

<template>
    <GuestLayout>
        <Head title="Подтверждение пароля" />

        <div class="mb-4 text-sm text-gray-600">
            Это защищённая область приложения. Пожалуйста, подтвердите пароль перед продолжением.
        </div>

        <form @submit.prevent="submit" class="flex flex-col gap-4">
            <div class="flex flex-col gap-1">
                <label for="password" class="text-sm font-medium text-gray-700">Пароль</label>
                <Password
                    id="password"
                    v-model="form.password"
                    required
                    :feedback="false"
                    toggleMask
                    autofocus
                    :invalid="!!form.errors.password"
                    input-class="w-full"
                    class="w-full"
                />
                <InputError :message="form.errors.password" />
            </div>

            <div class="flex justify-end">
                <Button type="submit" label="Подтвердить" :loading="form.processing" />
            </div>
        </form>
    </GuestLayout>
</template>
