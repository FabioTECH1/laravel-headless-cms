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
        <div class="space-y-6">
            <h2 class="text-2xl font-bold text-gray-900">Dashboard</h2>

            <!-- Overview Widgets -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- Users -->
                <div class="bg-white overflow-hidden shadow-sm rounded-lg p-6 flex items-center">
                    <div class="p-3 bg-indigo-100 rounded-full mr-4">
                        <svg class="w-8 h-8 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                            xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z">
                            </path>
                        </svg>
                    </div>
                    <div>
                        <div class="text-sm font-medium text-gray-500">Users</div>
                        <div class="text-2xl font-bold text-gray-900">{{ stats.users }}</div>
                    </div>
                </div>

                <!-- Schemas -->
                <div class="bg-white overflow-hidden shadow-sm rounded-lg p-6 flex items-center">
                    <div class="p-3 bg-green-100 rounded-full mr-4">
                        <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                            xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4">
                            </path>
                        </svg>
                    </div>
                    <div>
                        <div class="text-sm font-medium text-gray-500">Content Types</div>
                        <div class="text-2xl font-bold text-gray-900">{{ stats.schemas }}</div>
                    </div>
                </div>

                <!-- Media -->
                <div class="bg-white overflow-hidden shadow-sm rounded-lg p-6 flex items-center">
                    <div class="p-3 bg-blue-100 rounded-full mr-4">
                        <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                            xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z">
                            </path>
                        </svg>
                    </div>
                    <div>
                        <div class="text-sm font-medium text-gray-500">Media</div>
                        <div class="text-2xl font-bold text-gray-900">{{ stats.media_size }}</div>
                        <div class="text-xs text-gray-400">({{ stats.media_count }} files)</div>
                    </div>
                </div>
            </div>

            <!-- Content Breakdown -->
            <div class="bg-white overflow-hidden shadow-sm rounded-lg">
                <div class="p-6 text-gray-900">
                    <h3 class="text-lg font-medium mb-4">Content Volume</h3>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col"
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Content Type</th>
                                    <th scope="col"
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Total Records</th>
                                    <th scope="col"
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Action</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr v-for="type in stats.content_breakdown" :key="type.slug">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{
                                        type.name }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ type.count }}</td>
                                    <td
                                        class="px-6 py-4 whitespace-nowrap text-sm text-indigo-600 hover:text-indigo-900">
                                        <Link :href="type.url">Manage</Link>
                                    </td>
                                </tr>
                                <tr v-if="stats.content_breakdown.length === 0">
                                    <td colspan="3"
                                        class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center">No content
                                        types defined yet.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- System Info -->
            <div class="text-right text-xs text-gray-400">
                Laravel v{{ stats.laravel_version }} (PHP v{{ stats.php_version }})
            </div>

        </div>
    </AdminLayout>
</template>
