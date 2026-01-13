```html
<script setup lang="ts">
import { Head, useForm, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { ref, watch } from 'vue';
import Input from '@/Components/Input.vue';
import Modal from '@/Components/Modal.vue';
import ConfirmModal from '@/Components/ConfirmModal.vue';
import { route } from '@/route-helper';
import { useToast } from '@/composables/useToast';
import { usePermissions } from '@/composables/usePermissions';

const { can } = usePermissions();

const props = defineProps<{
    users: {
        data: Array<any>;
        links: Array<any>;
    };
    roles: Array<{ id: string; name: string }>;
    filters: {
        search: string;
    };
}>();

const toast = useToast();
const search = ref(props.filters.search || '');
const showingUserModal = ref(false);
const editingUser = ref<any>(null);

// Confirmation Modal State
const confirmingAction = ref(false);
const actionTitle = ref('');
const actionContent = ref('');
const actionType = ref<'danger' | 'warning' | 'primary'>('danger');
const actionCallback = ref<() => void>(() => { });
const actionConfirmText = ref('Confirm');

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    role: '',
});

const searchUsers = () => {
    router.get(route('admin.users.index'), { search: search.value }, { preserveState: true, replace: true });
};

let timeout: ReturnType<typeof setTimeout>;

watch(search, () => {
    clearTimeout(timeout);
    timeout = setTimeout(() => {
        searchUsers();
    }, 300);
});

const openCreateModal = () => {
    editingUser.value = null;
    form.reset();
    form.clearErrors();
    showingUserModal.value = true;
};

const openEditModal = (user: any) => {
    editingUser.value = user;
    form.reset();
    form.clearErrors();
    form.name = user.name;
    form.email = user.email;
    form.role = user.roles && user.roles.length > 0 ? user.roles[0].name : '';
    showingUserModal.value = true;
};

const closeModal = () => {
    showingUserModal.value = false;
    form.reset();
};

const submit = () => {
    if (editingUser.value) {
        form.put(route('admin.users.update', editingUser.value.id), {
            onSuccess: () => {
                closeModal();
                toast.success('User updated successfully');
            },
        });
    } else {
        form.post(route('admin.users.store'), {
            onSuccess: () => {
                closeModal();
                toast.success('User created successfully');
            },
        });
    }
};

const confirmDelete = (user: any) => {
    actionTitle.value = 'Delete User';
    actionContent.value = `Are you sure you want to delete ${user.name}? This action cannot be undone.`;
    actionType.value = 'danger';
    actionConfirmText.value = 'Delete User';
    actionCallback.value = () => {
        router.delete(route('admin.users.destroy', user.id), {
            onSuccess: () => toast.success('User deleted successfully'),
        });
    };
    confirmingAction.value = true;
};

const confirmSuspend = (user: any) => {
    actionTitle.value = 'Suspend User';
    actionContent.value = `Are you sure you want to suspend ${user.name}? They will no longer be able to log in.`;
    actionType.value = 'warning';
    actionConfirmText.value = 'Suspend User';
    actionCallback.value = () => {
        router.put(route('admin.users.suspend', user.id), {}, {
            onSuccess: () => toast.success('User suspended successfully'),
        });
    };
    confirmingAction.value = true;
};

const confirmUnsuspend = (user: any) => {
    actionTitle.value = 'Unsuspend User';
    actionContent.value = `Are you sure you want to restore access for ${user.name}?`;
    actionType.value = 'primary';
    actionConfirmText.value = 'Restore User';
    actionCallback.value = () => {
        router.put(route('admin.users.unsuspend', user.id), {}, {
            onSuccess: () => toast.success('User access restored'),
        });
    };
    confirmingAction.value = true;
};

const executeAction = () => {
    actionCallback.value();
    confirmingAction.value = false;
};
</script>

<template>

    <Head title="Users" />

    <AdminLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">User Management</h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

                <!-- Toolbar -->
                <div class="flex justify-between items-center">
                    <div class="flex items-center space-x-2">
                        <div class="relative">
                            <input type="search" v-model="search" @keyup.enter="searchUsers"
                                placeholder="Search users..." name="admin_user_search" id="admin_user_search"
                                autocomplete="new-password" readonly onfocus="this.removeAttribute('readonly');"
                                class="w-64 px-4 py-2 text-sm text-gray-900 dark:text-gray-100 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500" />
                        </div>
                        <button @click="searchUsers"
                            class="px-3 py-2 text-sm font-medium text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-md hover:bg-gray-50 dark:hover:bg-gray-700">
                            Search
                        </button>
                    </div>
                    <button v-if="can('create-users')" @click="openCreateModal"
                        class="px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        Create User
                    </button>
                </div>

                <!-- Users Table -->
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm rounded-lg">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-700/50">
                            <tr>
                                <th scope="col"
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    Name</th>
                                <th scope="col"
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    Email</th>
                                <th scope="col"
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    Role</th>
                                <th scope="col"
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    Status</th>
                                <th scope="col"
                                    class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                            <tr v-for="user in users.data" :key="user.id">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ user.name }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-500 dark:text-gray-400">{{ user.email }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span v-if="user.roles && user.roles.length > 0"
                                        class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-indigo-100 text-indigo-800 dark:bg-indigo-900 dark:text-indigo-200 capitalize">
                                        {{ user.roles[0].name.replace('-', ' ') }}
                                    </span>
                                    <span v-else
                                        class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300">
                                        No Role
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span v-if="user.suspended_at"
                                        class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200">Suspended</span>
                                    <span v-else
                                        class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">Active</span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <div class="flex justify-end items-center gap-4">
                                        <button v-if="can('edit-users')" @click="openEditModal(user)"
                                            class="text-indigo-600 dark:text-indigo-400 hover:text-indigo-900 dark:hover:text-indigo-300 transition-colors">Edit</button>

                                        <template v-if="user.id !== $page.props.auth.user.id">
                                            <button v-if="can('edit-users') && !user.suspended_at"
                                                @click="confirmSuspend(user)"
                                                class="text-amber-600 dark:text-amber-400 hover:text-amber-900 dark:hover:text-amber-300 transition-colors">Suspend</button>
                                            <button v-if="can('edit-users') && user.suspended_at"
                                                @click="confirmUnsuspend(user)"
                                                class="text-green-600 dark:text-green-400 hover:text-green-900 dark:hover:text-green-300 transition-colors">Unsuspend</button>

                                            <button v-if="can('delete-users')" @click="confirmDelete(user)"
                                                class="text-red-600 dark:text-red-400 hover:text-red-900 dark:hover:text-red-300 transition-colors">Delete</button>
                                        </template>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="users.data.length === 0">
                                <td colspan="5"
                                    class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400 text-center">
                                    No users found.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Create/Edit Modal -->
        <Modal :show="showingUserModal" @close="closeModal">
            <div class="p-6">
                <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                    {{ editingUser ? 'Edit User' : 'Create User' }}
                </h2>

                <div class="mt-6 space-y-4">
                    <Input id="name" v-model="form.name" label="Name" :error="form.errors.name" required autofocus />

                    <Input id="email" type="email" v-model="form.email" label="Email" :error="form.errors.email"
                        required />

                    <Input id="password" type="password" v-model="form.password" label="Password"
                        :placeholder="editingUser ? 'Leave blank to keep' : ''" :required="!editingUser"
                        :error="form.errors.password" />

                    <Input v-if="!editingUser || form.password" id="password_confirmation" type="password"
                        v-model="form.password_confirmation" label="Confirm Password" />

                    <div class="block">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Role <span class="text-red-500">*</span>
                        </label>
                        <select v-model="form.role" required
                            :disabled="editingUser && editingUser.id === $page.props.auth.user.id"
                            class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-white disabled:opacity-50 disabled:cursor-not-allowed">
                            <option value="" disabled>Select a role</option>
                            <option v-for="role in roles" :key="role.id" :value="role.name" class="capitalize">
                                {{ role.name.replace('-', ' ') }}
                            </option>
                        </select>
                        <p v-if="form.errors.role" class="mt-1 text-sm text-red-600 dark:text-red-400">
                            {{ form.errors.role }}
                        </p>
                    </div>
                </div>

                <div class="mt-6 flex justify-end">
                    <button @click="closeModal"
                        class="px-4 py-2 bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 border border-gray-300 dark:border-gray-600 rounded-md hover:bg-gray-50 dark:hover:bg-gray-700 text-sm font-medium">
                        Cancel
                    </button>
                    <button @click="submit" :disabled="form.processing"
                        class="ms-3 px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-md hover:bg-indigo-700 disabled:opacity-50">
                        {{ editingUser ? 'Update' : 'Create' }}
                    </button>
                </div>
            </div>
        </Modal>

        <!-- Confirmation Modal -->
        <ConfirmModal :show="confirmingAction" :title="actionTitle" :content="actionContent"
            :confirm-text="actionConfirmText" :confirm-type="actionType" @close="confirmingAction = false"
            @confirm="executeAction" />
    </AdminLayout>
</template>
```
