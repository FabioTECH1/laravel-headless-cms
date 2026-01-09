<script setup lang="ts">
import AdminLayout from '@/Layouts/AdminLayout.vue';
import Input from '@/Components/Input.vue';
import { useForm, Head, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import { route } from '@/route-helper';

const props = defineProps<{
    tokens: Array<{
        id: number;
        name: string;
        last_used_at: string;
        created_at: string;
    }>;
    systemInfo: {
        laravel_version: string;
        environment: string;
        debug_mode: boolean;
    };
}>();

const page = usePage();
const user = computed(() => page.props.auth?.user);
const newToken = computed(() => page.props.flash?.token || null);

// Profile form
const profileForm = useForm({
    name: user.value?.name || '',
    email: user.value?.email || '',
});

const updateProfile = () => {
    profileForm.put(route('admin.settings.profile.update'), {
        preserveScroll: true,
    });
};

// Password form
const passwordForm = useForm({
    current_password: '',
    password: '',
    password_confirmation: '',
});

const updatePassword = () => {
    passwordForm.put(route('admin.settings.password.update'), {
        preserveScroll: true,
        onSuccess: () => passwordForm.reset(),
    });
};

// Token form
const tokenForm = useForm({
    name: '',
});

const createToken = () => {
    tokenForm.post(route('admin.settings.tokens.store'), {
        onSuccess: () => tokenForm.reset(),
    });
};

const deleteToken = (id: number) => {
    if (confirm('Are you sure you want to revoke this token?')) {
        useForm({}).delete(route('admin.settings.tokens.destroy', id));
    }
};

const copyToken = () => {
    if (newToken.value) {
        navigator.clipboard.writeText(newToken.value);
        alert('Token copied to clipboard!');
    }
};
</script>

<template>
    <AdminLayout>

        <Head title="Settings" />

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

                <!-- Profile Settings -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <h2 class="text-lg font-semibold text-gray-900 mb-6">Profile Settings</h2>

                    <form @submit.prevent="updateProfile" class="space-y-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <Input id="profile-name" v-model="profileForm.name" label="Name"
                                :error="profileForm.errors.name" required />
                            <Input id="profile-email" v-model="profileForm.email" type="email" label="Email"
                                :error="profileForm.errors.email" required />
                        </div>
                        <div class="flex justify-end">
                            <button type="submit" :disabled="profileForm.processing"
                                class="bg-indigo-600 text-white px-4 py-2 rounded-md hover:bg-indigo-700 disabled:opacity-50 transition-colors">
                                Update Profile
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Change Password -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <h2 class="text-lg font-semibold text-gray-900 mb-6">Change Password</h2>

                    <form @submit.prevent="updatePassword" class="space-y-6">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <Input id="current-password" v-model="passwordForm.current_password" type="password"
                                label="Current Password" :error="passwordForm.errors.current_password" required />
                            <Input id="new-password" v-model="passwordForm.password" type="password"
                                label="New Password" :error="passwordForm.errors.password" required />
                            <Input id="confirm-password" v-model="passwordForm.password_confirmation" type="password"
                                label="Confirm Password" required />
                        </div>
                        <div class="flex justify-end">
                            <button type="submit" :disabled="passwordForm.processing"
                                class="bg-indigo-600 text-white px-4 py-2 rounded-md hover:bg-indigo-700 disabled:opacity-50 transition-colors">
                                Update Password
                            </button>
                        </div>
                    </form>
                </div>

                <!-- API Tokens -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <h2 class="text-lg font-semibold text-gray-900 mb-6">API Access Tokens</h2>

                    <!-- New Token Alert -->
                    <div v-if="newToken" class="mb-6 p-4 bg-green-50 border border-green-200 rounded-lg">
                        <p class="text-sm text-green-800 mb-2">
                            <strong>Token created successfully!</strong> Make sure to copy it now - you won't be able to
                            see it again.
                        </p>
                        <div class="flex gap-2">
                            <input type="text" :value="newToken" readonly
                                class="flex-1 px-3 py-2 bg-white border border-green-300 rounded-md text-sm font-mono" />
                            <button @click="copyToken"
                                class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 text-sm">
                                Copy
                            </button>
                        </div>
                    </div>

                    <!-- Create Token Form -->
                    <form @submit.prevent="createToken" class="space-y-4 mb-6">
                        <Input id="token-name" v-model="tokenForm.name" label="Token Name"
                            placeholder="e.g. My Next.js Blog" :error="tokenForm.errors.name" />
                        <div class="flex justify-end">
                            <button type="submit" :disabled="tokenForm.processing"
                                class="bg-indigo-600 text-white px-4 py-2 rounded-md hover:bg-indigo-700 disabled:opacity-50 transition-colors">
                                Generate
                            </button>
                        </div>
                    </form>

                    <!-- Active Tokens List -->
                    <div v-if="tokens.length === 0" class="text-gray-500 text-sm italic">
                        No active tokens. Create one above to get started.
                    </div>
                    <div v-else class="space-y-3">
                        <div v-for="token in tokens" :key="token.id"
                            class="flex items-center justify-between p-4 border border-gray-200 rounded-lg">
                            <div>
                                <p class="font-medium text-gray-900">{{ token.name }}</p>
                                <p class="text-sm text-gray-500">
                                    Last used: {{ token.last_used_at }} • Created: {{ token.created_at }}
                                </p>
                            </div>
                            <button @click="deleteToken(token.id)"
                                class="text-red-600 hover:text-red-800 text-sm font-medium">
                                Revoke
                            </button>
                        </div>
                    </div>
                </div>

                <!-- System Status -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <h2 class="text-lg font-semibold text-gray-900 mb-6">System Status</h2>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div>
                            <p class="text-sm font-medium text-gray-500 mb-1">Laravel Version</p>
                            <p class="text-lg font-semibold text-gray-900">{{ systemInfo.laravel_version }}</p>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-500 mb-1">Environment</p>
                            <div class="flex items-center gap-2">
                                <p class="text-lg font-semibold text-gray-900 capitalize">{{ systemInfo.environment }}
                                </p>
                                <span v-if="systemInfo.environment === 'production'"
                                    class="inline-flex items-center px-2 py-1 text-xs font-medium text-green-700 bg-green-50 rounded-full ring-1 ring-inset ring-green-600/20">
                                    Production
                                </span>
                                <span v-else
                                    class="inline-flex items-center px-2 py-1 text-xs font-medium text-blue-700 bg-blue-50 rounded-full ring-1 ring-inset ring-blue-600/20">
                                    Development
                                </span>
                            </div>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-500 mb-1">Debug Mode</p>
                            <div class="flex items-center gap-2">
                                <p class="text-lg font-semibold text-gray-900">{{ systemInfo.debug_mode ? 'Enabled' :
                                    'Disabled' }}</p>
                                <span v-if="systemInfo.debug_mode && systemInfo.environment === 'production'"
                                    class="inline-flex items-center px-2 py-1 text-xs font-medium text-red-700 bg-red-50 rounded-full ring-1 ring-inset ring-red-600/20">
                                    ⚠️ Warning
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Production Warning -->
                    <div v-if="systemInfo.debug_mode && systemInfo.environment === 'production'"
                        class="mt-4 p-4 bg-red-50 border border-red-200 rounded-lg">
                        <p class="text-sm text-red-800">
                            <strong>⚠️ Security Warning:</strong> Debug mode is enabled in production! This exposes
                            sensitive information and should be disabled immediately by setting <code
                                class="px-1 py-0.5 bg-red-100 rounded">APP_DEBUG=false</code> in your <code
                                class="px-1 py-0.5 bg-red-100 rounded">.env</code> file.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
