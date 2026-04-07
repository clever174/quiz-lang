<script setup>
import InputError from '@/Components/InputError.vue';
import { Link, useForm, usePage } from '@inertiajs/vue3';

defineProps({
    mustVerifyEmail: { type: Boolean },
    status: { type: String },
});

const user = usePage().props.auth.user;

const form = useForm({
    name: user.name,
    email: user.email,
});
</script>

<template>
    <section>
        <header>
            <h2 class="text-lg font-medium text-gray-900">Информация профиля</h2>
            <p class="mt-1 text-sm text-gray-600">Обновите имя и адрес электронной почты вашего аккаунта.</p>
        </header>

        <form @submit.prevent="form.patch(route('profile.update'))" class="mt-6 flex flex-col gap-6">
            <div class="flex flex-col gap-1">
                <label for="name" class="text-sm font-medium text-gray-700">Имя</label>
                <InputText
                    id="name"
                    v-model="form.name"
                    type="text"
                    required
                    autofocus
                    autocomplete="name"
                    :invalid="!!form.errors.name"
                    class="w-full"
                />
                <InputError :message="form.errors.name" />
            </div>

            <div class="flex flex-col gap-1">
                <label for="email" class="text-sm font-medium text-gray-700">Email</label>
                <InputText
                    id="email"
                    v-model="form.email"
                    type="email"
                    required
                    autocomplete="username"
                    :invalid="!!form.errors.email"
                    class="w-full"
                />
                <InputError :message="form.errors.email" />
            </div>

            <div v-if="mustVerifyEmail && user.email_verified_at === null">
                <p class="mt-2 text-sm text-gray-800">
                    Ваш email не подтверждён.
                    <Link
                        :href="route('verification.send')"
                        method="post"
                        as="button"
                        class="rounded-md text-sm text-gray-600 underline hover:text-gray-900"
                    >
                        Нажмите здесь, чтобы отправить письмо повторно.
                    </Link>
                </p>
                <div v-show="status === 'verification-link-sent'" class="mt-2 text-sm font-medium text-green-600">
                    Новая ссылка для подтверждения отправлена на ваш email.
                </div>
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
