<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

defineProps<{
    stats: {
        users: number;
        schemas: number;
        media_count: number;
        media_size: string;
        content_breakdown: Array<{
            name: string;
            slug: string;
            count: number;
            url: string;
        }>;
        php_version: string;
        laravel_version: string;
    };
}>();
</script>

<template>

    <Head title="Dashboard" />

    <AdminLayout>
        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
                <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Dashboard</h2>

                <!-- Overview Widgets -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <!-- Users -->
                    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm rounded-lg p-6 flex items-center">
                        <div class="p-3 bg-indigo-100 dark:bg-indigo-900/30 rounded-full mr-4">
                            <svg class="w-8 h-8 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z">
                                </path>
                            </svg>
                        </div>
                        <div>
                            <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Users</div>
                            <div class="text-2xl font-bold text-gray-900 dark:text-white">{{ stats.users }}</div>
                        </div>
                    </div>

                    <!-- Schemas -->
                    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm rounded-lg p-6 flex items-center">
                        <div class="p-3 bg-green-100 dark:bg-green-900/30 rounded-full mr-4">
                            <svg class="w-8 h-8 text-green-600 dark:text-green-400" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4">
                                </path>
                            </svg>
                        </div>
                        <div>
                            <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Content Types</div>
                            <div class="text-2xl font-bold text-gray-900 dark:text-white">{{ stats.schemas }}</div>
                        </div>
                    </div>

                    <!-- Media -->
                    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm rounded-lg p-6 flex items-center">
                        <div class="p-3 bg-blue-100 dark:bg-blue-900/30 rounded-full mr-4">
                            <svg class="w-8 h-8 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z">
                                </path>
                            </svg>
                        </div>
                        <div>
                            <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Media</div>
                            <div class="text-2xl font-bold text-gray-900 dark:text-white">{{ stats.media_size }}</div>
                            <div class="text-xs text-gray-400 dark:text-gray-500">({{ stats.media_count }} files)</div>
                        </div>
                    </div>
                </div>

                <!-- Content Breakdown -->
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm rounded-lg">
                    <div class="p-6 text-gray-900 dark:text-white">
                        <h3 class="text-lg font-medium mb-4">Content Volume</h3>

                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                <thead class="bg-gray-50 dark:bg-gray-700/50">
                                    <tr>
                                        <th scope="col"
                                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                            Content Type</th>
                                        <th scope="col"
                                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                            Total Records</th>
                                        <th scope="col"
                                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                            Action</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                    <tr v-for="type in stats.content_breakdown" :key="type.slug">
                                        <td
                                            class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">
                                            {{
                                                type.name }}</td>
                                        <td
                                            class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                            {{
                                                type.count }}</td>
                                        <td
                                            class="px-6 py-4 whitespace-nowrap text-sm text-indigo-600 dark:text-indigo-400 hover:text-indigo-900 dark:hover:text-indigo-300">
                                            <Link :href="type.url">Manage</Link>
                                        </td>
                                    </tr>
                                    <tr v-if="stats.content_breakdown.length === 0">
                                        <td colspan="3"
                                            class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400 text-center">
                                            No content
                                            types defined yet.</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- System Info -->
                <div class="text-right text-xs text-gray-400 dark:text-gray-600">
                    Laravel v{{ stats.laravel_version }} (PHP v{{ stats.php_version }})
                </div>

            </div>
        </div>
    </AdminLayout>
</template>
