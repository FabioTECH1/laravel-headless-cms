<script setup lang="ts">
import Input from '@/Components/Input.vue';
import { Head, useForm } from '@inertiajs/vue3';

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
});

const submit = () => {
    form.post('/admin/register', {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>

    <Head title="Setup Admin Account" />

    <div class="flex min-h-screen flex-1 flex-col justify-center px-6 py-12 lg:px-8 bg-gray-50 dark:bg-gray-900">
        <div class="sm:mx-auto sm:w-full sm:max-w-sm">
            <h2 class="mt-10 text-center text-2xl font-bold leading-9 tracking-tight text-gray-900 dark:text-white">
                Setup Admin Account
            </h2>
            <p class="mt-2 text-center text-sm text-gray-600 dark:text-gray-400">
                Welcome! Create the first admin user to get started.
            </p>
        </div>

        <div class="mt-10 sm:mx-auto sm:w-full sm:max-w-sm">
            <form class="space-y-6" @submit.prevent="submit">
                <div>
                    <Input id="name" v-model="form.name" type="text" label="Full Name" :error="form.errors.name"
                        required autofocus />
                </div>

                <div>
                    <Input id="email" v-model="form.email" type="email" label="Email address" :error="form.errors.email"
                        required />
                </div>

                <div>
                    <Input id="password" v-model="form.password" type="password" label="Password"
                        :error="form.errors.password" required />
                </div>

                <div>
                    <Input id="password_confirmation" v-model="form.password_confirmation" type="password"
                        label="Confirm Password" required />
                </div>

                <div>
                    <button type="submit" :disabled="form.processing"
                        class="flex w-full justify-center rounded-md bg-indigo-600 px-3 py-1.5 text-sm font-semibold leading-6 text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600 disabled:opacity-50">
                        Create Account
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>
