<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <div class="h-screen w-full bg-gray-50 flex">
        <!-- Sidebar -->
        <aside class="w-64 bg-white border-r border-gray-200 flex flex-col">
            <div class="h-16 flex items-center px-6 border-b border-gray-200">
                <span class="text-lg font-bold text-gray-900">Headless CMS</span>
            </div>

            <nav class="flex-1 px-4 py-4 space-y-1">
                <Link
                    href="/admin/dashboard"
                    class="flex items-center px-2 py-2 text-sm font-medium text-gray-700 rounded-md hover:bg-gray-100 group"
                    :class="{ 'bg-gray-100 text-gray-900': $page.url === '/admin/dashboard' }"
                >
                    Dashboard
                </Link>

                <div class="pt-4 pb-2">
                    <p class="px-2 text-xs font-semibold text-gray-500 uppercase tracking-wider">
                        Content Types
                    </p>
                </div>
                <!-- Dynamic Content Types will go here -->

                <div class="pt-4 pb-2">
                    <p class="px-2 text-xs font-semibold text-gray-500 uppercase tracking-wider">
                        System
                    </p>
                </div>

                <Link
                    href="/admin/schema"
                    class="flex items-center px-2 py-2 text-sm font-medium text-gray-700 rounded-md hover:bg-gray-100 group"
                    :class="{ 'bg-gray-100 text-gray-900': $page.url.startsWith('/admin/schema') }"
                >
                    Schema Builder
                </Link>

                <Link
                    href="/admin/settings"
                    class="flex items-center px-2 py-2 text-sm font-medium text-gray-700 rounded-md hover:bg-gray-100 group"
                    :class="{ 'bg-gray-100 text-gray-900': $page.url.startsWith('/admin/settings') }"
                >
                    Settings
                </Link>
            </nav>
        </aside>

        <!-- Main Content -->
        <div class="flex-1 flex flex-col overflow-hidden">
            <!-- Header -->
            <header class="h-16 bg-white border-b border-gray-200 flex items-center justify-end px-6">
                <Link
                    href="/admin/logout"
                    method="post"
                    as="button"
                    class="text-sm font-medium text-gray-700 hover:text-gray-900"
                >
                    Logout
                </Link>
            </header>

            <!-- Page Content -->
            <main class="flex-1 overflow-auto p-6">
                <slot />
            </main>
        </div>
    </div>
</template>
