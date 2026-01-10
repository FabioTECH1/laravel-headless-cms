<script setup lang="ts">
import AdminLayout from '@/Layouts/AdminLayout.vue';
import Input from '@/Components/Input.vue';
import { useForm, Head, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import { route } from '@/route-helper';
import { useTheme } from '@/composables/useTheme';
import { useToast } from '@/composables/useToast';
import ConfirmModal from '@/Components/ConfirmModal.vue';
import { ref } from 'vue';

const toast = useToast();
const confirmingRevocation = ref(false);
const tokenToRevoke = ref<number | null>(null);

defineProps<{
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

const { theme, setTheme } = useTheme();

// Profile form
const profileForm = useForm({
    name: user.value?.name || '',
    email: user.value?.email || '',
});

const updateProfile = () => {
    profileForm.put(route('admin.settings.profile.update'), {
        preserveScroll: true,
        onSuccess: () => {
            toast.success('Profile updated successfully');
        },
        onError: () => {
            toast.error('Please check the form for errors.');
        },
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
        onSuccess: () => {
            passwordForm.reset();
            toast.success('Password updated successfully');
        },
        onError: () => {
            passwordForm.reset('password', 'password_confirmation');
            toast.error('Failed to update password. Please try again.');
        },
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

const confirmRevocation = (id: number) => {
    tokenToRevoke.value = id;
    confirmingRevocation.value = true;
};

const revokeToken = () => {
    if (tokenToRevoke.value) {
        useForm({}).delete(route('admin.settings.tokens.destroy', tokenToRevoke.value), {
            onFinish: () => {
                confirmingRevocation.value = false;
                tokenToRevoke.value = null;
                toast.success('Token revoked successfully');
            }
        });
    }
};

const copyToken = () => {
    if (newToken.value) {
        navigator.clipboard.writeText(newToken.value);
        toast.success('Token copied to clipboard!');
    }
};
</script>

<template>
    <AdminLayout>

        <Head title="Settings" />

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

                <!-- Appearance -->
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-6">Appearance</h2>

                    <div class="flex items-center gap-4">
                        <button @click="setTheme('light')" class="flex items-center gap-2 px-4 py-2 rounded-md border"
                            :class="[
                                theme === 'light'
                                    ? 'bg-indigo-50 border-indigo-500 text-indigo-700 dark:bg-indigo-900/50 dark:border-indigo-500 dark:text-indigo-300'
                                    : 'border-gray-300 text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700'
                            ]">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                                stroke="currentColor" class="w-5 h-5">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M12 3v2.25m6.364.386l-1.591 1.591M21 12h-2.25m-.386 6.364l-1.591-1.591M12 18.75V21m-4.773-4.227l-1.591 1.591M5.25 12H3m4.227-4.773L5.636 5.636M15.75 12a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0z" />
                            </svg>
                            Light
                        </button>

                        <button @click="setTheme('dark')" class="flex items-center gap-2 px-4 py-2 rounded-md border"
                            :class="[
                                theme === 'dark'
                                    ? 'bg-indigo-50 border-indigo-500 text-indigo-700 dark:bg-indigo-900/50 dark:border-indigo-500 dark:text-indigo-300'
                                    : 'border-gray-300 text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700'
                            ]">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                                stroke="currentColor" class="w-5 h-5">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M21.752 15.002A9.718 9.718 0 0118 15.75c-5.385 0-9.75-4.365-9.75-9.75 0-1.33.266-2.597.748-3.752A9.753 9.753 0 003 11.25C3 16.635 7.365 21 12.75 21a9.753 9.753 0 009.002-5.998z" />
                            </svg>
                            Dark
                        </button>

                        <button @click="setTheme('system')" class="flex items-center gap-2 px-4 py-2 rounded-md border"
                            :class="[
                                theme === 'system'
                                    ? 'bg-indigo-50 border-indigo-500 text-indigo-700 dark:bg-indigo-900/50 dark:border-indigo-500 dark:text-indigo-300'
                                    : 'border-gray-300 text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700'
                            ]">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                                stroke="currentColor" class="w-5 h-5">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M9 17.25v1.007a3 3 0 01-.879 2.122L7.5 21h9l-.621-.621A3 3 0 0115 18.257V17.25m6-12V15a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 15V5.25m18 0A2.25 2.25 0 0018.75 3H5.25A2.25 2.25 0 003 5.25m18 0V12a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 12V5.25" />
                            </svg>
                            System
                        </button>
                    </div>
                </div>

                <!-- Profile Settings -->
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-6">Profile Settings</h2>

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
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-6">Change Password</h2>

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
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-6">API Access Tokens</h2>

                    <!-- New Token Alert -->
                    <div v-if="newToken"
                        class="mb-6 p-4 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg">
                        <p class="text-sm text-green-800 dark:text-green-300 mb-2">
                            <strong>Token created successfully!</strong> Make sure to copy it now - you won't be able to
                            see it again.
                        </p>
                        <div class="flex gap-2">
                            <input type="text" :value="newToken" readonly
                                class="flex-1 px-3 py-2 bg-white dark:bg-gray-900 border border-green-300 dark:border-green-700 rounded-md text-sm font-mono dark:text-green-300" />
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
                    <div v-if="tokens.length === 0" class="text-gray-500 dark:text-gray-400 text-sm italic">
                        No active tokens. Create one above to get started.
                    </div>
                    <div v-else class="space-y-3">
                        <div v-for="token in tokens" :key="token.id"
                            class="flex items-center justify-between p-4 border border-gray-200 dark:border-gray-700 rounded-lg">
                            <div>
                                <p class="font-medium text-gray-900 dark:text-white">{{ token.name }}</p>
                                <p class="text-sm text-gray-500 dark:text-gray-400">
                                    Last used: {{ token.last_used_at }} • Created: {{ token.created_at }}
                                </p>
                            </div>
                            <button @click="confirmRevocation(token.id)"
                                class="text-red-600 dark:text-red-400 hover:text-red-800 dark:hover:text-red-300 text-sm font-medium">
                                Revoke
                            </button>
                        </div>
                    </div>
                </div>

                <!-- System Status -->
                <!-- ... -->

                <ConfirmModal :show="confirmingRevocation" title="Revoke API Token"
                    content="Are you sure you want to revoke this API token? This action cannot be undone and any application using this token will lose access."
                    confirm-text="Revoke Token" @close="confirmingRevocation = false" @confirm="revokeToken" />

                <!-- System Status -->
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-6">System Status</h2>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div>
                            <p class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">Laravel Version</p>
                            <p class="text-lg font-semibold text-gray-900 dark:text-white">{{ systemInfo.laravel_version
                            }}</p>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">Environment</p>
                            <div class="flex items-center gap-2">
                                <p class="text-lg font-semibold text-gray-900 dark:text-white capitalize">{{
                                    systemInfo.environment }}
                                </p>
                                <span v-if="systemInfo.environment === 'production'"
                                    class="inline-flex items-center px-2 py-1 text-xs font-medium text-green-700 dark:text-green-300 bg-green-50 dark:bg-green-900/30 rounded-full ring-1 ring-inset ring-green-600/20">
                                    Production
                                </span>
                                <span v-else
                                    class="inline-flex items-center px-2 py-1 text-xs font-medium text-blue-700 dark:text-blue-300 bg-blue-50 dark:bg-blue-900/30 rounded-full ring-1 ring-inset ring-blue-600/20">
                                    Development
                                </span>
                            </div>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">Debug Mode</p>
                            <div class="flex items-center gap-2">
                                <p class="text-lg font-semibold text-gray-900 dark:text-white">{{ systemInfo.debug_mode
                                    ? 'Enabled' :
                                    'Disabled' }}</p>
                                <span v-if="systemInfo.debug_mode && systemInfo.environment === 'production'"
                                    class="inline-flex items-center px-2 py-1 text-xs font-medium text-red-700 dark:text-red-300 bg-red-50 dark:bg-red-900/30 rounded-full ring-1 ring-inset ring-red-600/20">
                                    ⚠️ Warning
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Production Warning -->
                    <div v-if="systemInfo.debug_mode && systemInfo.environment === 'production'"
                        class="mt-4 p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg">
                        <p class="text-sm text-red-800 dark:text-red-300">
                            <strong>⚠️ Security Warning:</strong> Debug mode is enabled in production! This exposes
                            sensitive information and should be disabled immediately by setting <code
                                class="px-1 py-0.5 bg-red-100 dark:bg-red-900/40 rounded">APP_DEBUG=false</code> in your
                            <code class="px-1 py-0.5 bg-red-100 dark:bg-red-900/40 rounded">.env</code> file.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
