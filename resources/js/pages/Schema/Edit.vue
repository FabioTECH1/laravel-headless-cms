<script setup lang="ts">
import AdminLayout from '@/Layouts/AdminLayout.vue';
import Input from '@/Components/Input.vue';
import Select from '@/Components/Select.vue';
import { useForm, Head } from '@inertiajs/vue3';
import { route } from '@/route-helper';

const props = defineProps<{
    contentType: {
        id: number;
        name: string;
        slug: string;
        is_public: boolean;
        has_ownership: boolean;
        is_component: boolean;
        is_localized: boolean;
        fields: Array<{
            id: number;
            name: string;
            type: string;
            settings: { required: boolean; unique: boolean; related_content_type_id?: number | null; allowed_component_ids?: number[]; options?: string[]; multiple?: boolean };
        }>;
    };
    existingTypes: Array<{ id: number; name: string; is_component: boolean }>;
}>();

const form = useForm({
    is_public: props.contentType.is_public,
    has_ownership: props.contentType.has_ownership,
    is_component: props.contentType.is_component,
    is_localized: props.contentType.is_localized,
    fields: [] as Array<{ name: string; type: string; settings: { required: boolean; unique: boolean; related_content_type_id?: number | null; allowed_component_ids?: number[]; options?: string[]; multiple?: boolean } }>
});

const fieldTypeOptions = [
    { value: 'text', label: 'Text' },
    { value: 'longtext', label: 'Long Text' },
    { value: 'integer', label: 'Integer' },
    { value: 'boolean', label: 'Boolean' },
    { value: 'datetime', label: 'Datetime' },
    { value: 'media', label: 'Media' },
    { value: 'relation', label: 'Relation' },
    { value: 'component', label: 'Component' },
    { value: 'dynamic_zone', label: 'Dynamic Zone' },
    { value: 'json', label: 'JSON' },
    { value: 'enum', label: 'Enum (Select)' },
    { value: 'email', label: 'Email' },
];

const addField = () => {
    form.fields.push({ name: '', type: 'text', settings: { required: false, unique: false, related_content_type_id: null, allowed_component_ids: [], options: [], multiple: false } });
};

const removeField = (index: number) => {
    form.fields.splice(index, 1);
};

const submit = () => {
    form.put(route('admin.schema.update', props.contentType.slug));
};
</script>

<template>
    <AdminLayout>

        <Head :title="`Edit ${contentType.name}`" />

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg max-w-2xl mx-auto p-6">
                    <h2 class="text-2xl font-bold mb-6 text-gray-900 dark:text-white">Edit {{ contentType.name }} {{
                        contentType.is_component ? '(Component)' : '' }}</h2>

                    <form @submit.prevent="submit">
                        <!-- Content Type Name (Read-only) -->
                        <div class="mb-6">
                            <Input id="name" :model-value="contentType.name" label="Name" disabled />
                            <p class="text-xs text-gray-500 mt-1">
                                {{ contentType.is_component ? 'Component' : 'Content type' }} name cannot be changed
                            </p>
                        </div>

                        <!-- Is Component (Read-only) -->
                        <div class="mb-6" v-if="contentType.is_component">
                            <div
                                class="flex items-center gap-2 p-3 bg-blue-50 dark:bg-blue-900/20 text-blue-700 dark:text-blue-300 rounded-md border border-blue-100 dark:border-blue-800">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"
                                    class="w-5 h-5">
                                    <path fill-rule="evenodd"
                                        d="M2 4.25A2.25 2.25 0 0 1 4.25 2h2.5A2.25 2.25 0 0 1 9 4.25v2.5A2.25 2.25 0 0 1 6.75 9h-2.5A2.25 2.25 0 0 1 2 6.75v-2.5Zm0 9A2.25 2.25 0 0 1 4.25 11h2.5A2.25 2.25 0 0 1 9 13.25v2.5A2.25 2.25 0 0 1 6.75 18h-2.5A2.25 2.25 0 0 1 2 15.75v-2.5Zm9-9A2.25 2.25 0 0 1 13.25 2h2.5A2.25 2.25 0 0 1 18 4.25v2.5A2.25 2.25 0 0 1 15.75 9h-2.5A2.25 2.25 0 0 1 11 6.75v-2.5Zm0 9A2.25 2.25 0 0 1 13.25 11h2.5A2.25 2.25 0 0 1 18 13.25v2.5A2.25 2.25 0 0 1 15.75 18h-2.5A2.25 2.25 0 0 1 11 15.75v-2.5Z"
                                        clip-rule="evenodd" />
                                </svg>
                                <span class="text-sm font-medium">This is a Component</span>
                            </div>
                        </div>

                        <!-- Content Type Settings -->
                        <div v-if="!contentType.is_component"
                            class="mb-6 pb-6 border-b border-gray-200 dark:border-gray-700">
                            <h3 class="text-sm font-medium text-gray-900 dark:text-white mb-3">Content Type Settings
                            </h3>
                            <div class="space-y-3">
                                <div class="flex items-start">
                                    <input id="is_public" v-model="form.is_public" type="checkbox"
                                        class="h-4 w-4 mt-0.5 text-indigo-600 focus:ring-indigo-500 border-gray-300 dark:border-gray-600 dark:bg-gray-700 rounded" />
                                    <label for="is_public" class="ml-2 block text-sm">
                                        <span class="font-medium text-gray-900 dark:text-gray-200">Public Access</span>
                                        <span class="text-gray-500 dark:text-gray-400 block text-xs mt-0.5">Allow
                                            unauthenticated users to
                                            read this content via API</span>
                                    </label>
                                </div>
                                <div class="flex items-start">
                                    <input id="has_ownership" v-model="form.has_ownership" type="checkbox"
                                        class="h-4 w-4 mt-0.5 text-indigo-600 focus:ring-indigo-500 border-gray-300 dark:border-gray-600 dark:bg-gray-700 rounded" />
                                    <label for="has_ownership" class="ml-2 block text-sm">
                                        <span class="font-medium text-gray-900 dark:text-gray-200">User Ownership</span>
                                        <span class="text-gray-500 dark:text-gray-400 block text-xs mt-0.5">Track which
                                            user created each
                                            content item</span>
                                    </label>
                                </div>
                                <div class="flex items-start">
                                    <input id="is_localized" v-model="form.is_localized" type="checkbox"
                                        class="h-4 w-4 mt-0.5 text-indigo-600 focus:ring-indigo-500 border-gray-300 dark:border-gray-600 dark:bg-gray-700 rounded" />
                                    <label for="is_localized" class="ml-2 block text-sm">
                                        <span class="font-medium text-gray-900 dark:text-gray-200">Is Localized?</span>
                                        <span class="text-gray-500 dark:text-gray-400 block text-xs mt-0.5">Allow
                                            content to be translated</span>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <!-- Existing Fields (Read-only) -->
                        <div class="mb-6 pb-6 border-b border-gray-200 dark:border-gray-700">
                            <h3 class="text-sm font-medium text-gray-900 dark:text-white mb-3">Existing Fields</h3>
                            <div class="space-y-2">
                                <div v-for="field in contentType.fields" :key="field.id"
                                    class="flex items-center gap-3 p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                                    <div class="flex-1">
                                        <span class="text-sm font-medium text-gray-900 dark:text-gray-200">{{ field.name
                                        }}</span>
                                    </div>
                                    <div class="text-sm text-gray-500 dark:text-gray-400">
                                        {{fieldTypeOptions.find(opt => opt.value === field.type)?.label || field.type
                                        }}
                                        <span
                                            v-if="(field.type === 'relation' || field.type === 'component') && field.settings.related_content_type_id"
                                            class="ml-1 text-xs text-gray-400">
                                            (-> {{existingTypes.find(t => t.id ===
                                                field.settings.related_content_type_id)?.name || 'Unknown'}})
                                        </span>
                                        <span v-if="field.type === 'dynamic_zone'" class="ml-1 text-xs text-gray-400">
                                            (Dynamic Zone)
                                        </span>
                                        <span v-if="field.type === 'enum' && field.settings.options"
                                            class="ml-1 text-xs text-gray-400">
                                            [{{ field.settings.options.join(', ') }}]
                                        </span>
                                    </div>
                                    <div class="flex gap-2 text-xs text-gray-500">
                                        <span v-if="field.settings.required"
                                            class="px-2 py-1 bg-indigo-100 text-indigo-700 rounded">Required</span>
                                        <span v-if="field.settings.unique"
                                            class="px-2 py-1 bg-purple-100 text-purple-700 rounded">Unique</span>
                                    </div>
                                </div>
                            </div>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">Existing fields cannot be modified
                                or deleted</p>
                        </div>

                        <!-- New Fields -->
                        <div class="mb-6" v-if="form.fields.length > 0">
                            <h3 class="text-sm font-medium text-gray-900 dark:text-white mb-3">New Fields</h3>

                            <div class="space-y-4">
                                <div v-for="(field, index) in form.fields" :key="index"
                                    class="border border-gray-200 dark:border-gray-700 rounded-lg p-4">
                                    <div class="flex gap-4 items-start mb-3">
                                        <div class="flex-1">
                                            <Input v-model="field.name" placeholder="Field Name"
                                                :error="form.errors[`fields.${index}.name`]" required />
                                        </div>

                                        <div class="w-1/3">
                                            <Select v-model="field.type" :options="fieldTypeOptions"
                                                :error="form.errors[`fields.${index}.type`]" />
                                        </div>

                                        <button type="button" @click="removeField(index)"
                                            class="p-2 text-gray-400 dark:text-gray-500 hover:text-red-500 dark:hover:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/30 rounded-lg transition-all"
                                            title="Remove Field">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                                stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                                            </svg>
                                        </button>
                                    </div>

                                    <!-- Relation Settings -->
                                    <div v-if="field.type === 'relation'" class="mb-3 pl-1 w-2/3 flex gap-4">
                                        <div class="w-1/2">
                                            <label
                                                class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Related
                                                Content Type</label>
                                            <Select v-model="field.settings.related_content_type_id"
                                                :options="existingTypes.filter(t => !t.is_component).map(t => ({ value: t.id, label: t.name }))"
                                                placeholder="Select Type..."
                                                :error="form.errors[`fields.${index}.settings.related_content_type_id`]" />
                                        </div>
                                        <div class="w-1/2 pt-6">
                                            <label
                                                class="flex items-center text-sm text-gray-600 dark:text-gray-400 cursor-pointer">
                                                <input v-model="field.settings.multiple" type="checkbox"
                                                    class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 dark:border-gray-600 dark:bg-gray-700 rounded mr-2" />
                                                Allow Multiple (M2M)
                                            </label>
                                        </div>
                                    </div>

                                    <!-- Component Settings -->
                                    <div v-if="field.type === 'component'" class="mb-3 pl-1 w-2/3">
                                        <div class="w-full">
                                            <label
                                                class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Select
                                                Component</label>
                                            <Select v-model="field.settings.related_content_type_id"
                                                :options="existingTypes.filter(t => t.is_component).map(t => ({ value: t.id, label: t.name }))"
                                                placeholder="Select Component..."
                                                :error="form.errors[`fields.${index}.settings.related_content_type_id`]" />
                                        </div>
                                    </div>

                                    <!-- Dynamic Zone Settings -->
                                    <div v-if="field.type === 'dynamic_zone'" class="mb-3 pl-1 w-2/3">
                                        <div class="w-full">
                                            <label
                                                class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Allowed
                                                Components (hold Ctrl/Cmd to select multiple)</label>
                                            <select multiple v-model="field.settings.allowed_component_ids"
                                                class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6 dark:bg-gray-700 dark:text-white dark:ring-gray-600"
                                                :class="{ 'ring-red-300 focus:ring-red-500': form.errors[`fields.${index}.settings.allowed_component_ids`] }">
                                                <option v-for="type in existingTypes.filter(t => t.is_component)"
                                                    :key="type.id" :value="type.id">
                                                    {{ type.name }}
                                                </option>
                                            </select>
                                            <p v-if="form.errors[`fields.${index}.settings.allowed_component_ids`]"
                                                class="mt-1.5 text-sm text-red-600 dark:text-red-400">
                                                {{ form.errors[`fields.${index}.settings.allowed_component_ids`] }}
                                            </p>
                                        </div>
                                    </div>

                                    <!-- Enum Options -->
                                    <div v-if="field.type === 'enum'" class="mb-3 pl-1">
                                        <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                                            Options (comma separated)
                                        </label>
                                        <Input
                                            :model-value="Array.isArray(field.settings.options) ? field.settings.options.join(', ') : ''"
                                            @update:model-value="val => field.settings.options = (val as string).split(',').map(s => s.trim()).filter(s => s)"
                                            placeholder="admin, user, guest"
                                            :error="form.errors[`fields.${index}.settings.options`]" />
                                    </div>

                                    <!-- Field Settings -->
                                    <div class="flex gap-4 pl-1">
                                        <label
                                            class="flex items-center text-sm text-gray-600 dark:text-gray-400 cursor-pointer">
                                            <input v-model="field.settings.required" type="checkbox"
                                                class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 dark:border-gray-600 dark:bg-gray-700 rounded mr-2" />
                                            Required
                                        </label>
                                        <label
                                            class="flex items-center text-sm text-gray-600 dark:text-gray-400 cursor-pointer">
                                            <input v-model="field.settings.unique" type="checkbox"
                                                class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 dark:border-gray-600 dark:bg-gray-700 rounded mr-2" />
                                            Unique
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Add Field Button -->
                        <div class="mb-6">
                            <button type="button" @click="addField"
                                class="text-sm text-indigo-600 dark:text-indigo-400 hover:text-indigo-800 dark:hover:text-indigo-300 font-medium flex items-center gap-1">
                                <span>+ Add New Field</span>
                            </button>
                        </div>

                        <div class="flex justify-end gap-3 pt-4 border-t border-gray-100 dark:border-gray-700">
                            <a :href="route('admin.schema.index')"
                                class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white">
                                Cancel
                            </a>
                            <button type="submit" :disabled="form.processing"
                                class="bg-indigo-600 text-white px-4 py-2 rounded-md hover:bg-indigo-700 disabled:opacity-50 transition-colors">
                                Update Schema
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
