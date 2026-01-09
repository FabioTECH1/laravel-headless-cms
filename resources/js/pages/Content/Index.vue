<script setup lang="ts">
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { route } from '@/route-helper';

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

const deleteItem = (id: number) => {
    if (confirm('Are you sure you want to delete this item?')) {
        router.delete(route('admin.content.destroy', { slug: props.contentType.slug, id }));
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
            return value.length > 50 ? value.substring(0, 50) + '...' : value;
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
                    <div class="mt-4 sm:ml-16 sm:mt-0 sm:flex-none">
                        <Link :href="route('admin.content.create', contentType.slug)"
                            class="block rounded-md bg-indigo-600 px-3 py-2 text-center text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">
                            Create New
                        </Link>
                    </div>
                </div>

                <div class="mt-8 flow-root">
                    <div class="-mx-4 -my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">
                        <div class="inline-block min-w-full py-2 align-middle sm:px-6 lg:px-8">
                            <div class="overflow-hidden shadow ring-1 ring-black ring-opacity-5 sm:rounded-lg">
                                <table class="min-w-full divide-y divide-gray-300 dark:divide-gray-700">
                                    <thead class="bg-gray-50 dark:bg-gray-700">
                                        <tr>
                                            <th scope="col"
                                                class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-gray-900 dark:text-gray-200 sm:pl-6">
                                                ID</th>
                                            <!-- Dynamic field columns -->
                                            <th v-for="field in displayFields" :key="field.name" scope="col"
                                                class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900 dark:text-gray-200 capitalize">
                                                {{ field.name.replace(/_/g, ' ') }}</th>
                                            <th scope="col"
                                                class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900 dark:text-gray-200">
                                                Created At</th>
                                            <th scope="col"
                                                class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900 dark:text-gray-200">
                                                Status
                                            </th>
                                            <th scope="col" class="relative py-3.5 pl-3 pr-4 sm:pr-6">
                                                <span class="sr-only">Actions</span>
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody
                                        class="divide-y divide-gray-200 dark:divide-gray-700 bg-white dark:bg-gray-800">
                                        <tr v-if="items.data.length === 0">
                                            <td :colspan="displayFields.length + 4"
                                                class="py-4 text-center text-sm text-gray-500 dark:text-gray-400">No
                                                items
                                                found.</td>
                                        </tr>
                                        <tr v-for="item in items.data" :key="item.id">
                                            <td
                                                class="whitespace-nowrap py-4 pl-4 pr-3 text-sm font-medium text-gray-900 dark:text-white sm:pl-6">
                                                {{ item.id }}</td>
                                            <!-- Dynamic field values -->
                                            <td v-for="field in displayFields" :key="field.name"
                                                class="px-3 py-4 text-sm text-gray-900 dark:text-gray-300 max-w-xs truncate">
                                                {{ formatValue(item[field.name], field.type) }}</td>
                                            <td
                                                class="whitespace-nowrap px-3 py-4 text-sm text-gray-500 dark:text-gray-400">
                                                {{ new
                                                    Date(item.created_at).toLocaleDateString() }}</td>
                                            <td class="whitespace-nowrap px-3 py-4 text-sm">
                                                <span v-if="item.published_at"
                                                    class="inline-flex items-center rounded-md bg-green-50 dark:bg-green-900/30 px-2 py-1 text-xs font-medium text-green-700 dark:text-green-300 ring-1 ring-inset ring-green-600/20">
                                                    Published
                                                </span>
                                                <span v-else
                                                    class="inline-flex items-center rounded-md bg-yellow-50 dark:bg-yellow-900/30 px-2 py-1 text-xs font-medium text-yellow-800 dark:text-yellow-300 ring-1 ring-inset ring-yellow-600/20">
                                                    Draft
                                                </span>
                                            </td>
                                            <td
                                                class="relative whitespace-nowrap py-4 pl-3 pr-4 text-right text-sm font-medium sm:pr-6">
                                                <Link
                                                    :href="route('admin.content.edit', { slug: contentType.slug, id: item.id })"
                                                    class="text-indigo-600 dark:text-indigo-400 hover:text-indigo-900 dark:hover:text-indigo-300 mr-4">
                                                    Edit</Link>
                                                <button @click="deleteItem(item.id)"
                                                    class="text-red-600 dark:text-red-400 hover:text-red-900 dark:hover:text-red-300">Delete</button>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
