<script setup lang="ts">
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { route } from '@/route-helper';
import ConfirmModal from '@/Components/ConfirmModal.vue';
import { ref, computed } from 'vue';
import { usePermissions } from '@/composables/usePermissions';

const { can } = usePermissions();

const props = defineProps<{
    types: Array<{
        id: number;
        name: string;
        slug: string;
        created_at: string;
        is_component: boolean;
    }>;
}>();

const confirmingDeletion = ref(false);
const typeToDelete = ref<{ name: string; slug: string } | null>(null);

const currentTab = ref<'types' | 'components'>('types');

const filteredTypes = computed(() => {
    return props.types.filter(type => {
        if (currentTab.value === 'components') return type.is_component;
        return !type.is_component;
    });
});

const confirmDelete = (type: { name: string; slug: string }) => {
    typeToDelete.value = type;
    confirmingDeletion.value = true;
};

const deleteType = () => {
    if (typeToDelete.value) {
        router.delete(route('admin.schema.destroy', typeToDelete.value.slug), {
            onFinish: () => {
                confirmingDeletion.value = false;
                typeToDelete.value = null;
            }
        });
    }
};
</script>

<template>
    <AdminLayout>

        <Head :title="currentTab === 'components' ? 'Components' : 'Content Types'" />

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="sm:flex sm:items-center">
                    <div class="sm:flex-auto">
                        <h1 class="text-xl font-semibold text-gray-900 dark:text-white">{{ currentTab === 'components' ?
                            'Components' : 'Content Types' }}</h1>
                        <p class="mt-2 text-sm text-gray-700 dark:text-gray-300">A list of all the {{ currentTab ===
                            'components' ? 'reusable components' : 'content types' }} defined in your CMS.</p>
                    </div>
                    <div class="mt-4 sm:ml-16 sm:mt-0 sm:flex-none">
                        <div class="flex gap-2">
                            <Link v-if="can('create-schema')"
                                :href="route('admin.schema.create', { is_component: currentTab === 'components' ? 1 : 0 })"
                                class="block rounded-md bg-indigo-600 px-3 py-2 text-center text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">
                                Create {{ currentTab === 'components' ? 'Component' : 'Content Type' }}
                            </Link>
                        </div>
                    </div>
                </div>

                <div class="mt-4 border-b border-gray-200 dark:border-gray-700">
                    <nav class="-mb-px flex space-x-8" aria-label="Tabs">
                        <button @click="currentTab = 'types'"
                            :class="[currentTab === 'types' ? 'border-indigo-500 text-indigo-600 dark:text-indigo-400' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300', 'whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm']">
                            Content Types
                        </button>
                        <button @click="currentTab = 'components'"
                            :class="[currentTab === 'components' ? 'border-indigo-500 text-indigo-600 dark:text-indigo-400' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300', 'whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm']">
                            Components
                        </button>
                    </nav>
                </div>

                <div class="mt-8 bg-white dark:bg-gray-800 overflow-hidden shadow-sm rounded-lg">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-700/50">
                            <tr>
                                <th scope="col"
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">
                                    Name</th>
                                <th scope="col"
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">
                                    Slug
                                </th>
                                <th scope="col"
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">
                                    Created At</th>
                                <th scope="col"
                                    class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">
                                    Actions
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                            <tr v-if="filteredTypes.length === 0">
                                <td colspan="4" class="px-6 py-4 text-center text-sm text-gray-500 dark:text-gray-400">
                                    No {{ currentTab === 'components' ? 'components' : 'content types' }} found.
                                </td>
                            </tr>
                            <tr v-for="type in filteredTypes" :key="type.id">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ type.name }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-500 dark:text-gray-400">{{ type.slug }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-500 dark:text-gray-400">{{ new
                                        Date(type.created_at).toLocaleDateString() }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <div class="flex justify-end items-center gap-4">
                                        <Link v-if="can('edit-schema')" :href="route('admin.schema.edit', type.slug)"
                                            class="text-indigo-600 dark:text-indigo-400 hover:text-indigo-900 dark:hover:text-indigo-300">
                                            Edit<span class="sr-only">, {{ type.name }}</span>
                                        </Link>
                                        <button v-if="can('delete-schema')" @click="confirmDelete(type)"
                                            class="text-red-600 dark:text-red-400 hover:text-red-900 dark:hover:text-red-300">
                                            Delete<span class="sr-only">, {{ type.name }}</span>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <ConfirmModal :show="confirmingDeletion"
            :title="'Delete ' + (currentTab === 'components' ? 'Component' : 'Content Type')"
            :content="`Are you sure you want to delete this ${currentTab === 'components' ? 'component' : 'content type'}? This action cannot be undone and will PERMANENTLY DELETE all content entries associated with this type.`"
            :confirm-text="'Delete ' + (currentTab === 'components' ? 'Component' : 'Type')" confirm-type="danger"
            @close="confirmingDeletion = false" @confirm="deleteType" />
    </AdminLayout>
</template>
