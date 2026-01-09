<script setup lang="ts">
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';

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

// Find the first text field to use as the main identifier/label
const titleField = props.contentType.fields?.find(f => f.type === 'text')?.name || 'id';
</script>

<template>
    <AdminLayout>

        <Head :title="contentType.name" />

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="sm:flex sm:items-center">
                    <div class="sm:flex-auto">
                        <h1 class="text-xl font-semibold text-gray-900">{{ contentType.name }}</h1>
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
                                <table class="min-w-full divide-y divide-gray-300">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th scope="col"
                                                class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-gray-900 sm:pl-6">
                                                ID</th>
                                            <th scope="col"
                                                class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900 capitalize">
                                                {{ titleField.toString().replace(/_/g, ' ') }}</th>
                                            <th scope="col"
                                                class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">
                                                Created At</th>
                                            <th scope="col"
                                                class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Status
                                            </th>
                                            <th scope="col" class="relative py-3.5 pl-3 pr-4 sm:pr-6">
                                                <span class="sr-only">Actions</span>
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-200 bg-white">
                                        <tr v-if="items.data.length === 0">
                                            <td colspan="5" class="py-4 text-center text-sm text-gray-500">No items
                                                found.</td>
                                        </tr>
                                        <tr v-for="item in items.data" :key="item.id">
                                            <td
                                                class="whitespace-nowrap py-4 pl-4 pr-3 text-sm font-medium text-gray-900 sm:pl-6">
                                                {{ item.id }}</td>
                                            <td
                                                class="whitespace-nowrap px-3 py-4 text-sm text-gray-900 truncate max-w-xs">
                                                {{ item[titleField] }}</td>
                                            <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">{{ new
                                                Date(item.created_at).toLocaleDateString() }}</td>
                                            <td class="whitespace-nowrap px-3 py-4 text-sm">
                                                <span v-if="item.published_at"
                                                    class="inline-flex items-center rounded-md bg-green-50 px-2 py-1 text-xs font-medium text-green-700 ring-1 ring-inset ring-green-600/20">
                                                    Published
                                                </span>
                                                <span v-else
                                                    class="inline-flex items-center rounded-md bg-yellow-50 px-2 py-1 text-xs font-medium text-yellow-800 ring-1 ring-inset ring-yellow-600/20">
                                                    Draft
                                                </span>
                                            </td>
                                            <td
                                                class="relative whitespace-nowrap py-4 pl-3 pr-4 text-right text-sm font-medium sm:pr-6">
                                                <Link
                                                    :href="route('admin.content.edit', { slug: contentType.slug, id: item.id })"
                                                    class="text-indigo-600 hover:text-indigo-900 mr-4">Edit</Link>
                                                <button @click="deleteItem(item.id)"
                                                    class="text-red-600 hover:text-red-900">Delete</button>
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
