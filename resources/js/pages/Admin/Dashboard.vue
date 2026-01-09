<script setup lang="ts">
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head } from '@inertiajs/vue3';
</script>

<template>
    <Head title="Dashboard" />

    <AdminLayout>
        <div class="py-6">
            <h1 class="text-2xl font-semibold text-gray-900">Dashboard</h1>
            <p class="mt-2 text-gray-600">Welcome to your Headless CMS dashboard.</p>
        </div>
    </AdminLayout>
</template>
