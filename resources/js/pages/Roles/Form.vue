<script setup lang="ts">
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';

interface Permission {
    id: string;
    name: string;
}

interface Role {
    id?: string;
    name: string;
    permissions: Permission[];
}

const props = defineProps<{
    role?: Role;
    permissions: Record<string, Permission[]>;
}>();

const form = useForm({
    name: props.role?.name || '',
    permissions: props.role?.permissions.map(p => p.name) || [],
});

const isEditing = computed(() => !!props.role);

const submit = () => {
    if (isEditing.value) {
        form.put(`/admin/roles/${props.role!.id}`);
    } else {
        form.post('/admin/roles');
    }
};

const togglePermission = (permissionName: string) => {
    const index = form.permissions.indexOf(permissionName);
    if (index > -1) {
        form.permissions.splice(index, 1);
    } else {
        form.permissions.push(permissionName);
    }
};

const toggleCategory = (category: string) => {
    const categoryPermissions = props.permissions[category].map(p => p.name);
    const allSelected = categoryPermissions.every(p => form.permissions.includes(p));

    if (allSelected) {
        // Deselect all
        form.permissions = form.permissions.filter(p => !categoryPermissions.includes(p));
    } else {
        // Select all
        categoryPermissions.forEach(p => {
            if (!form.permissions.includes(p)) {
                form.permissions.push(p);
            }
        });
    }
};

const isCategorySelected = (category: string) => {
    const categoryPermissions = props.permissions[category].map(p => p.name);
    return categoryPermissions.every(p => form.permissions.includes(p));
};
</script>

<template>
    <AdminLayout>

        <Head :title="isEditing ? 'Edit Role' : 'Create Role'" />

        <div class="py-12">
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-6">
                    <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-6">
                        {{ isEditing ? 'Edit Role' : 'Create New Role' }}
                    </h2>

                    <form @submit.prevent="submit">
                        <!-- Role Name -->
                        <div class="mb-6">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Role Name
                            </label>
                            <input v-model="form.name" type="text" required
                                class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
                                placeholder="e.g., content-manager" />
                            <p v-if="form.errors.name" class="mt-1 text-sm text-red-600 dark:text-red-400">
                                {{ form.errors.name }}
                            </p>
                        </div>

                        <!-- Permissions -->
                        <div class="mb-6">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-4">
                                Permissions
                            </label>

                            <div v-for="(perms, category) in permissions" :key="category" class="mb-4">
                                <div class="bg-gray-50 dark:bg-gray-900 rounded-lg p-4">
                                    <div class="flex items-center mb-3">
                                        <input :id="`category-${category}`" type="checkbox"
                                            :checked="isCategorySelected(category)" @change="toggleCategory(category)"
                                            class="h-4 w-4 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500" />
                                        <label :for="`category-${category}`"
                                            class="ml-2 text-sm font-semibold text-gray-900 dark:text-white capitalize">
                                            {{ category }}
                                        </label>
                                    </div>

                                    <div class="grid grid-cols-2 md:grid-cols-3 gap-2 ml-6">
                                        <div v-for="permission in perms" :key="permission.id" class="flex items-center">
                                            <input :id="permission.name" type="checkbox"
                                                :checked="form.permissions.includes(permission.name)"
                                                @change="togglePermission(permission.name)"
                                                class="h-4 w-4 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500" />
                                            <label :for="permission.name"
                                                class="ml-2 text-sm text-gray-700 dark:text-gray-300">
                                                {{ permission.name }}
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <p v-if="form.errors.permissions" class="mt-1 text-sm text-red-600 dark:text-red-400">
                                {{ form.errors.permissions }}
                            </p>
                        </div>

                        <!-- Actions -->
                        <div class="flex justify-end gap-3">
                            <a href="/admin/roles"
                                class="px-4 py-2 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-md transition-colors">
                                Cancel
                            </a>
                            <button type="submit" :disabled="form.processing"
                                class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 transition-colors disabled:opacity-50">
                                {{ form.processing ? 'Saving...' : (isEditing ? 'Update Role' : 'Create Role') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
