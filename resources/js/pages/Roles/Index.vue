<script setup lang="ts">
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import ConfirmModal from '@/Components/ConfirmModal.vue';
import { useToast } from '@/composables/useToast';

interface Role {
    id: string;
    name: string;
    permissions_count: number;
    users_count: number;
}

defineProps<{
    roles: Role[];
}>();

const toast = useToast();
const showDeleteConfirm = ref(false);
const roleToDelete = ref<string | null>(null);

const deleteRole = (roleId: string) => {
    roleToDelete.value = roleId;
    showDeleteConfirm.value = true;
};

const confirmDelete = () => {
    if (!roleToDelete.value) return;

    router.delete(`/admin/roles/${roleToDelete.value}`, {
        onSuccess: () => {
            toast.success('Role deleted successfully');
        },
        onError: () => {
            toast.error('Failed to delete role');
        }
    });
};
</script>

<template>
    <AdminLayout>

        <Head title="Roles & Permissions" />

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <!-- Header -->
                <!-- Header -->
                <div class="sm:flex sm:items-center justify-between mb-6">
                    <div class="sm:flex-auto">
                        <h1 class="text-xl font-semibold text-gray-900 dark:text-white">Roles & Permissions</h1>
                        <p class="mt-2 text-sm text-gray-700 dark:text-gray-300">Manage user roles and their access
                            levels.</p>
                    </div>
                    <div class="mt-4 sm:ml-16 sm:mt-0 sm:flex-none">
                        <Link href="/admin/roles/create"
                            class="inline-flex items-center justify-center rounded-md border border-transparent bg-indigo-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 sm:w-auto">
                            Create Role
                        </Link>
                    </div>
                </div>

                <!-- Roles Table -->
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm rounded-lg">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-700/50">
                            <tr>
                                <th scope="col"
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">
                                    Role Name
                                </th>
                                <th scope="col"
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">
                                    Permissions
                                </th>
                                <th scope="col"
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">
                                    Users
                                </th>
                                <th scope="col"
                                    class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">
                                    Actions
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                            <tr v-for="role in roles" :key="role.id">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900 dark:text-white capitalize">
                                        {{ role.name.replace('-', ' ') }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span
                                        class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-indigo-100 dark:bg-indigo-900 text-indigo-800 dark:text-indigo-200">
                                        {{ role.permissions_count }} permissions
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                    {{ role.users_count }} users
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <Link :href="`/admin/roles/${role.id}/edit`"
                                        class="text-indigo-600 dark:text-indigo-400 hover:text-indigo-900 dark:hover:text-indigo-300 mr-4">
                                        Edit
                                    </Link>
                                    <button v-if="role.name !== 'super-admin'" @click="deleteRole(role.id)"
                                        class="text-red-600 dark:text-red-400 hover:text-red-900 dark:hover:text-red-300">
                                        Delete
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Delete Confirmation Modal -->
                <ConfirmModal :show="showDeleteConfirm" title="Delete Role"
                    content="Are you sure you want to delete this role? This will not affect users already assigned to this role, but they may lose permissions."
                    confirm-text="Delete Role" @close="showDeleteConfirm = false" @confirm="confirmDelete" />
            </div>
        </div>
    </AdminLayout>
</template>
