<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import ToastContainer from '@/Components/ToastContainer.vue';
</script>

<template>
    <div class="h-screen w-full bg-gray-50 dark:bg-gray-900 flex">
        <!-- Sidebar -->
        <aside class="w-64 bg-white dark:bg-gray-800 border-r border-gray-200 dark:border-gray-700 flex flex-col">
            <div class="h-16 flex items-center px-6 border-b border-gray-200 dark:border-gray-700">
                <span class="text-lg font-bold text-gray-900 dark:text-white">Laravel Headless CMS</span>
            </div>

            <nav class="flex-1 px-4 py-4 space-y-1">
                <Link href="/admin/dashboard"
                    class="flex items-center px-2 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 rounded-md hover:bg-gray-100 dark:hover:bg-gray-700 group"
                    :class="{ 'bg-gray-100 dark:bg-gray-700 text-gray-900 dark:text-white': $page.url === '/admin/dashboard' }">
                    Dashboard
                </Link>

                <div class="pt-4 pb-2">
                    <p class="px-2 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                        Content Types
                    </p>
                </div>
                <div v-for="type in $page.props.contentTypes" :key="type.id">
                    <Link :href="route('admin.content.index', type.slug)"
                        class="flex items-center px-2 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 rounded-md hover:bg-gray-100 dark:hover:bg-gray-700 group"
                        :class="{ 'bg-gray-100 dark:bg-gray-700 text-gray-900 dark:text-white': $page.url.startsWith(`/admin/content/${type.slug}`) }">
                        {{ type.name }}
                    </Link>
                </div>

                <div class="pt-4 pb-2">
                    <p class="px-2 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                        System
                    </p>
                </div>

                <Link href="/admin/schema"
                    class="flex items-center px-2 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 rounded-md hover:bg-gray-100 dark:hover:bg-gray-700 group"
                    :class="{ 'bg-gray-100 dark:bg-gray-700 text-gray-900 dark:text-white': $page.url === '/admin/schema' || $page.url === '/admin/schema/create' }">
                    Schema Builder
                </Link>

                <Link href="/admin/settings"
                    class="flex items-center px-2 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 rounded-md hover:bg-gray-100 dark:hover:bg-gray-700 group"
                    :class="{ 'bg-gray-100 dark:bg-gray-700 text-gray-900 dark:text-white': $page.url.startsWith('/admin/settings') }">
                    Settings
                </Link>
            </nav>
        </aside>

        <!-- Main Content -->
        <div class="flex-1 flex flex-col overflow-hidden">
            <!-- Header -->
            <header
                class="h-16 bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 flex items-center justify-end px-6">
                <Link href="/admin/logout" method="post" as="button"
                    class="text-sm font-medium text-gray-700 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white">
                    Logout
                </Link>
            </header>

            <!-- Page Content -->
            <main class="flex-1 overflow-auto p-6">
                <slot />
            </main>
        </div>
    </div>
    <ToastContainer />
</template>
