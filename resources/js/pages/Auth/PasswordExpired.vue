<script setup lang="ts">
import Input from '@/Components/Input.vue';
import { Head, useForm } from '@inertiajs/vue3';

const form = useForm({
    current_password: '',
    password: '',
    password_confirmation: '',
});

const submit = () => {
    form.put('/admin/password/expired', {
        onFinish: () => form.reset('password', 'password_confirmation', 'current_password'),
    });
};
</script>

<template>

    <Head title="Change Password" />

    <div class="min-h-screen bg-gray-50 dark:bg-gray-900 flex flex-col justify-center py-12 sm:px-6 lg:px-8">
        <div class="sm:mx-auto sm:w-full sm:max-w-md">
            <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900 dark:text-white">
                Change Password
            </h2>
            <p class="mt-2 text-center text-sm text-gray-600 dark:text-gray-400">
                You must update your password before continuing.
            </p>
        </div>

        <div class="mt-8 sm:mx-auto sm:w-full sm:max-w-md">
            <div class="bg-white dark:bg-gray-800 py-8 px-4 shadow sm:rounded-lg sm:px-10">
                <form @submit.prevent="submit" class="space-y-6">
                    <div>
                        <Input id="current_password" v-model="form.current_password" type="password"
                            label="Current Password" :error="form.errors.current_password" required />
                    </div>

                    <div>
                        <Input id="password" v-model="form.password" type="password" label="New Password"
                            :error="form.errors.password" required />
                    </div>

                    <div>
                        <Input id="password_confirmation" v-model="form.password_confirmation" type="password"
                            label="Confirm New Password" :error="form.errors.password_confirmation" required />
                    </div>

                    <div>
                        <button type="submit" :disabled="form.processing"
                            class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                            Update Password
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>
