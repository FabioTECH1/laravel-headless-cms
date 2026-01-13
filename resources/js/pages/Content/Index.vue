<script setup lang="ts">
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { route } from '@/route-helper';
import ConfirmModal from '@/Components/ConfirmModal.vue';
import Pagination from '@/Components/Pagination.vue';
import { ref } from 'vue';
import { usePermissions } from '@/composables/usePermissions';

const { can } = usePermissions();

const props = defineProps<{
    contentType: {
        name: string;
        slug: string;
        fields: Array<{ name: string; type: string }>;
    };
    items: {
        data: Array<{
            id: number;
            created_at: string;
            published_at: string | null;
            [key: string]: any;
        }>;
        links: Array<any>;
    };
}>();

const confirmingDeletion = ref(false);
const itemToDelete = ref<number | null>(null);

const confirmDelete = (id: number) => {
    itemToDelete.value = id;
    confirmingDeletion.value = true;
};

const deleteItem = () => {
    if (itemToDelete.value) {
        router.delete(route('admin.content.destroy', { slug: props.contentType.slug, id: itemToDelete.value }), {
            onFinish: () => {
                confirmingDeletion.value = false;
                itemToDelete.value = null;
            }
        });
    }
};

// Get displayable fields (limit to first 3-4 for table readability)
const displayFields = props.contentType.fields.slice(0, 3);

// Format field value for display
const formatValue = (value: any, type: string) => {
    if (value === null || value === undefined) return '-';

    switch (type) {
        case 'boolean':
            return value ? 'Yes' : 'No';
        case 'datetime':
            return new Date(value).toLocaleDateString();
        case 'longtext':
            const stripped = value.replace(/<[^>]*>?/gm, '');
            return stripped.length > 50 ? stripped.substring(0, 50) + '...' : stripped;
        default:
            return value;
    }
};
</script>

<template>
    <AdminLayout>

        <Head :title="contentType.name" />

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="sm:flex sm:items-center">
                    <div class="sm:flex-auto">
                        <h1 class="text-xl font-semibold text-gray-900 dark:text-white">{{ contentType.name }}</h1>
                    </div>
                    <div class="mt-4 sm:ml-16 sm:mt-0 sm:flex-none flex gap-3">
                        <Link v-if="can('edit-schema')" :href="route('admin.schema.edit', contentType.slug)"
                            class="block rounded-md bg-white px-3 py-2 text-center text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50 dark:bg-gray-700 dark:text-white dark:ring-gray-600 dark:hover:bg-gray-600">
                            Edit Schema
                        </Link>
                        <Link v-if="can('create-content')" :href="route('admin.content.create', contentType.slug)"
                            class="block rounded-md bg-indigo-600 px-3 py-2 text-center text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">
                            Create New
                        </Link>
                    </div>
                </div>

                <div class="mt-8 bg-white dark:bg-gray-800 overflow-hidden shadow-sm rounded-lg overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-700/50">
                            <tr>
                                <th scope="col"
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">
                                    ID</th>
                                <!-- Dynamic field columns -->
                                <th v-for="field in displayFields" :key="field.name" scope="col"
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    {{ field.name.replace(/_/g, ' ') }}</th>
                                <th scope="col"
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">
                                    Created At</th>
                                <th scope="col"
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">
                                    Status
                                </th>
                                <th scope="col"
                                    class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">
                                    Actions
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                            <tr v-if="items.data.length === 0">
                                <td :colspan="displayFields.length + 4"
                                    class="px-6 py-4 text-center text-sm text-gray-500 dark:text-gray-400">No
                                    items
                                    found.</td>
                            </tr>
                            <tr v-for="item in items.data" :key="item.id">
                                <td
                                    class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">
                                    <span :title="String(item.id)">{{ String(item.id).length > 15 ?
                                        String(item.id).substring(0, 15) + '..' : item.id }}</span>
                                </td>
                                <!-- Dynamic field values -->
                                <td v-for="field in displayFields" :key="field.name"
                                    class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400 max-w-[12rem] truncate"
                                    :title="typeof item[field.name] === 'string' ? item[field.name].replace(/<[^>]*>?/gm, '') : ''">
                                    <template v-if="field.type === 'media' && item[field.name]">
                                        <a :href="item[field.name].url" target="_blank"
                                            class="block h-10 w-10 relative">
                                            <img :src="item[field.name].url"
                                                class="h-10 w-10 rounded object-cover border border-gray-200 dark:border-gray-700" />
                                        </a>
                                    </template>
                                    <template v-else>
                                        {{ formatValue(item[field.name], field.type) }}
                                    </template>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                    {{ new
                                        Date(item.created_at).toLocaleDateString() }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm">
                                    <span v-if="item.published_at"
                                        class="inline-flex items-center rounded-md bg-green-50 dark:bg-green-900/30 px-2 py-1 text-xs font-medium text-green-700 dark:text-green-300 ring-1 ring-inset ring-green-600/20">
                                        Published
                                    </span>
                                    <span v-else
                                        class="inline-flex items-center rounded-md bg-yellow-50 dark:bg-yellow-900/30 px-2 py-1 text-xs font-medium text-yellow-800 dark:text-yellow-300 ring-1 ring-inset ring-yellow-600/20">
                                        Draft
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <Link v-if="can('edit-content')"
                                        :href="route('admin.content.edit', { slug: contentType.slug, id: item.id })"
                                        class="text-indigo-600 dark:text-indigo-400 hover:text-indigo-900 dark:hover:text-indigo-300 mr-4">
                                        Edit</Link>
                                    <button v-if="can('delete-content')" @click="confirmDelete(item.id)"
                                        class="text-red-600 dark:text-red-400 hover:text-red-900 dark:hover:text-red-300">Delete</button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    <Pagination class="p-6 border-t border-gray-200 dark:border-gray-700" :links="items.links" />
                </div>
            </div>
        </div>
        <ConfirmModal :show="confirmingDeletion" title="Delete Content Item"
            content="Are you sure you want to delete this item? This action cannot be undone." confirm-text="Delete"
            @close="confirmingDeletion = false" @confirm="deleteItem" />
    </AdminLayout>
</template>
