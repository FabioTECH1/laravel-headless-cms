<script setup lang="ts">
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { useForm, Head, usePage } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

const props = defineProps<{
    tokens: Array<{
        id: number;
        name: string;
        last_used_at: string;
        created_at: string;
    }>;
}>();

const page = usePage();
const newToken = computed(() => page.props.flash?.token || null);

const form = useForm({
    name: '',
});

const createToken = () => {
    form.post(route('admin.settings.tokens.store'), {
        onSuccess: () => form.reset(),
    });
};

const deleteToken = (id: number) => {
    if (confirm('Are you sure you want to revoke this token?')) {
        useForm({}).delete(route('admin.settings.tokens.destroy', id));
    }
};

const copyToken = () => {
    if (newToken.value) {
        navigator.clipboard.writeText(newToken.value);
        alert('Token copied to clipboard!');
    }
};
</script>

<template>
    <AdminLayout>

        <Head title="API Settings" />

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

                <!-- New Token Alert -->
                <div v-if="newToken" class="bg-green-50 border-l-4 border-green-400 p-4 mb-6 shadow-sm rounded-r-md">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <!-- Heroicon name: solid/check-circle -->
                            <svg class="h-5 w-5 text-green-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"
                                fill="currentColor" aria-hidden="true">
                                <path fill-rule="evenodd"
                                    d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                    clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="ml-3 w-full">
                            <h3 class="text-sm font-medium text-green-800">Token Generated Successfully</h3>
                            <div class="mt-2 text-sm text-green-700">
                                <p class="mb-2">Make sure to copy your new API token now. You won't be able to see it
                                    again!</p>
                                <div class="flex items-center space-x-2">
                                    <code
                                        class="bg-white px-2 py-1 rounded border border-green-200 select-all font-mono text-xs break-all">{{ newToken }}</code>
                                    <button @click="copyToken" type="button"
                                        class="text-green-800 hover:text-green-900 font-medium text-xs underline">
                                        Copy
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <h2 class="text-lg font-medium text-gray-900 mb-4">Create API Token</h2>

                    <form @submit.prevent="createToken" class="flex gap-4 items-end">
                        <div class="flex-grow max-w-md">
                            <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Token Name</label>
                            <input id="name" v-model="form.name" type="text" placeholder="e.g. My Next.js Blog"
                                class="w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                        </div>
                        <button type="submit" :disabled="form.processing"
                            class="bg-indigo-600 text-white px-4 py-2 rounded-md hover:bg-indigo-700 disabled:opacity-50 transition-colors">
                            Generate
                        </button>
                    </form>
                    <p v-if="form.errors.name" class="text-red-600 text-sm mt-1">{{ form.errors.name }}</p>
                </div>

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <h2 class="text-lg font-medium text-gray-900 mb-4">Active API Tokens</h2>

                    <div v-if="tokens.length === 0" class="text-gray-500 text-sm italic">
                        No active tokens found.
                    </div>

                    <div v-else class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr v-for="token in tokens" :key="token.id">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                        {{ token.name }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        Last used {{ token.last_used_at }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        Created {{ token.created_at }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <button @click="deleteToken(token.id)" class="text-red-600 hover:text-red-900">
                                            Revoke
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
