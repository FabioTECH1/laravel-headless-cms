<script setup lang="ts">
import { Head, useForm, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { ref } from 'vue';
import Modal from '@/Components/Modal.vue';
import ConfirmModal from '@/Components/ConfirmModal.vue';
import { useToast } from '@/composables/useToast';

interface Webhook {
    id: string;
    name: string;
    url: string;
    secret: string; // Added secret
    events: string[];
    headers: Record<string, string> | null;
    enabled: boolean;
    created_at: string;
}

interface EventCategory {
    category: string;
    events: Array<{ value: string; label: string }>;
}

defineProps<{
    webhooks: Webhook[];
    availableEvents: EventCategory[];
}>();

const toast = useToast();
const showingModal = ref(false);
const editingWebhook = ref<Webhook | null>(null);
const confirmingDelete = ref(false);
const webhookToDelete = ref<string | null>(null);

const form = useForm({
    name: '',
    url: '',
    events: [] as string[],
    headers: {} as Record<string, string>,
    enabled: true,
});

const openCreateModal = () => {
    editingWebhook.value = null;
    form.reset();
    form.clearErrors();
    showingModal.value = true;
};

const openEditModal = (webhook: Webhook) => {
    editingWebhook.value = webhook;
    form.reset();
    form.clearErrors();
    form.name = webhook.name;
    form.url = webhook.url;
    form.events = [...webhook.events];
    form.headers = webhook.headers || {};
    form.enabled = webhook.enabled;
    showingModal.value = true;
};

const closeModal = () => {
    showingModal.value = false;
    form.reset();
};

const submit = () => {
    if (editingWebhook.value) {
        form.put(`/admin/webhooks/${editingWebhook.value.id}`, {
            onSuccess: () => {
                closeModal();
                toast.success('Webhook updated successfully');
            },
        });
    } else {
        form.post('/admin/webhooks', {
            onSuccess: () => {
                closeModal();
                toast.success('Webhook created successfully');
            },
        });
    }
};

const confirmDelete = (webhookId: string) => {
    webhookToDelete.value = webhookId;
    confirmingDelete.value = true;
};

const deleteWebhook = () => {
    if (webhookToDelete.value) {
        router.delete(`/admin/webhooks/${webhookToDelete.value}`, {
            onSuccess: () => {
                toast.success('Webhook deleted successfully');
                confirmingDelete.value = false;
            },
        });
    }
};

const testWebhook = (webhookId: string) => {
    router.post(`/admin/webhooks/${webhookId}/test`, {}, {
        onSuccess: () => toast.success('Test webhook sent successfully'),
        onError: (errors) => {
            console.error('Webhook test error:', errors);
            toast.error(errors.error || 'Webhook test failed');
        },
    });
};

const toggleEvent = (eventValue: string) => {
    const index = form.events.indexOf(eventValue);
    if (index > -1) {
        form.events.splice(index, 1);
    } else {
        form.events.push(eventValue);
    }
};

const copySecret = () => {
    if (editingWebhook.value?.secret) {
        navigator.clipboard.writeText(editingWebhook.value.secret);
        toast.success('Secret copied to clipboard');
    }
};

const copySecretFromList = (secret: string) => {
    if (secret) {
        navigator.clipboard.writeText(secret);
        toast.success('Secret Key copied to clipboard');
    } else {
        toast.error('No secret key found for this webhook');
    }
};
</script>

<template>

    <Head title="Webhooks" />

    <AdminLayout>
        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
                <!-- Header -->
                <div class="sm:flex sm:items-center justify-between">
                    <div class="sm:flex-auto">
                        <h1 class="text-xl font-semibold text-gray-900 dark:text-white">Webhooks</h1>
                        <p class="mt-2 text-sm text-gray-700 dark:text-gray-300">Manage external notifications for your
                            content events.</p>
                    </div>
                    <div class="mt-4 sm:ml-16 sm:mt-0 sm:flex-none">
                        <button @click="openCreateModal"
                            class="px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-md hover:bg-indigo-700 shadow-sm focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">
                            Create Webhook
                        </button>
                    </div>
                </div>

                <!-- Webhooks Table -->
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm rounded-lg">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-700/50">
                            <tr>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">
                                    Name</th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">
                                    URL</th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">
                                    Events</th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">
                                    Status</th>
                                <th
                                    class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">
                                    Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                            <tr v-for="webhook in webhooks" :key="webhook.id">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ webhook.name }}
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm text-gray-500 dark:text-gray-400 truncate max-w-xs">{{
                                        webhook.url }}
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm text-gray-500 dark:text-gray-400">{{ webhook.events.length }}
                                        events
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span v-if="webhook.enabled"
                                        class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                                        Enabled
                                    </span>
                                    <span v-else
                                        class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300">
                                        Disabled
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <div class="flex justify-end items-center gap-4">
                                        <button @click="copySecretFromList(webhook.secret)" title="Copy Secret Key"
                                            class="text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-200">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                                stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="M15.75 5.25a3 3 0 013 3m3 0a6 6 0 01-7.029 5.912c-.563-.097-1.159.026-1.563.43L10.5 17.25H8.25v2.25H6v2.25H2.25v-2.818c0-.597.237-1.17.659-1.591l6.499-6.499c.404-.404.527-1 .43-1.563A6 6 0 1121.75 8.25z" />
                                            </svg>
                                        </button>
                                        <button @click="testWebhook(webhook.id)"
                                            class="text-blue-600 dark:text-blue-400 hover:text-blue-900 dark:hover:text-blue-300">
                                            Test
                                        </button>
                                        <button @click="openEditModal(webhook)"
                                            class="text-indigo-600 dark:text-indigo-400 hover:text-indigo-900 dark:hover:text-indigo-300">
                                            Edit
                                        </button>
                                        <button @click="confirmDelete(webhook.id)"
                                            class="text-red-600 dark:text-red-400 hover:text-red-900 dark:hover:text-red-300">
                                            Delete
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="webhooks.length === 0">
                                <td colspan="5" class="px-6 py-4 text-center text-sm text-gray-500 dark:text-gray-400">
                                    No webhooks configured. Create one to get started.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Create/Edit Modal -->
        <Modal :show="showingModal" @close="closeModal">
            <div class="p-6">
                <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                    {{ editingWebhook ? 'Edit Webhook' : 'Create Webhook' }}
                </h2>

                <div class="mt-6 space-y-4">
                    <!-- Secret (only defined when editing) -->
                    <div v-if="editingWebhook">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Secret Key
                        </label>
                        <div class="flex shadow-sm rounded-md">
                            <input type="text" readonly :value="editingWebhook.secret"
                                class="flex-1 min-w-0 block w-full px-4 py-2 rounded-none rounded-l-md border border-gray-300 dark:border-gray-600 bg-gray-100 dark:bg-gray-600 text-gray-500 dark:text-gray-300 sm:text-sm" />
                            <button type="button" @click="copySecret"
                                class="inline-flex items-center px-3 rounded-r-md border border-l-0 border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-700 text-gray-500 dark:text-gray-300 sm:text-sm hover:bg-gray-100 dark:hover:bg-gray-600">
                                Copy
                            </button>
                        </div>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                            Use this key to verify the <code>X-Hub-Signature-256</code> header.
                        </p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Name <span class="text-red-500">*</span>
                        </label>
                        <input v-model="form.name" type="text" required
                            class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-white" />
                        <p v-if="form.errors.name" class="mt-1 text-sm text-red-600 dark:text-red-400">
                            {{ form.errors.name }}
                        </p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            URL <span class="text-red-500">*</span>
                        </label>
                        <input v-model="form.url" type="url" required placeholder="https://example.com/webhook"
                            class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-white" />
                        <p v-if="form.errors.url" class="mt-1 text-sm text-red-600 dark:text-red-400">
                            {{ form.errors.url }}
                        </p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Events <span class="text-red-500">*</span>
                        </label>
                        <div v-for="category in availableEvents" :key="category.category" class="mb-4">
                            <div class="font-semibold text-sm text-gray-900 dark:text-white mb-2">{{ category.category
                                }}</div>
                            <div class="grid grid-cols-2 gap-2">
                                <label v-for="event in category.events" :key="event.value" class="flex items-center">
                                    <input type="checkbox" :checked="form.events.includes(event.value)"
                                        @change="toggleEvent(event.value)"
                                        class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500" />
                                    <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">{{ event.label }}</span>
                                </label>
                            </div>
                        </div>
                        <p v-if="form.errors.events" class="mt-1 text-sm text-red-600 dark:text-red-400">
                            {{ form.errors.events }}
                        </p>
                    </div>

                    <div class="flex items-center">
                        <input type="checkbox" v-model="form.enabled"
                            class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500" />
                        <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Enabled</span>
                    </div>
                </div>

                <div class="mt-6 flex justify-end gap-3">
                    <button @click="closeModal"
                        class="px-4 py-2 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-md">
                        Cancel
                    </button>
                    <button @click="submit" :disabled="form.processing"
                        class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 disabled:opacity-50">
                        {{ form.processing ? 'Saving...' : (editingWebhook ? 'Update' : 'Create') }}
                    </button>
                </div>
            </div>
        </Modal>

        <!-- Delete Confirmation -->
        <ConfirmModal :show="confirmingDelete" title="Delete Webhook"
            content="Are you sure you want to delete this webhook? This action cannot be undone."
            confirm-text="Delete Webhook" confirm-type="danger" @close="confirmingDelete = false"
            @confirm="deleteWebhook" />
    </AdminLayout>
</template>
