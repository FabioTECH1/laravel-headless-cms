<script setup lang="ts">
import AdminLayout from '@/Layouts/AdminLayout.vue';
import MediaUpload from '@/Components/MediaUpload.vue';
import RichEditor from '@/Components/RichEditor.vue';
import ComponentField from '@/Components/Content/ComponentField.vue';
import DynamicZoneField from '@/Components/Content/DynamicZoneField.vue';
import Input from '@/Components/Input.vue';
import Select from '@/Components/Select.vue';
import { useForm, Head } from '@inertiajs/vue3';
import { computed } from 'vue';
import { route } from '@/route-helper';

const props = defineProps<{
    contentType: {
        name: string;
        slug: string;
        fields: Array<{
            name: string;
            type: string;
            settings?: { options?: string[]; multiple?: boolean; related_content_type_id?: number; allowed_component_ids?: number[] };
        }>;
        is_localized?: boolean;
    };
    item?: Record<string, any>;
    options?: Record<string, Array<{ id: number; label: string }>>;
    components?: Array<{ id: number; name: string; slug: string; fields: Array<any> }>;
}>();

const isEditing = computed(() => !!props.item);

// Initialize form with defaults or existing item values
const form = useForm(
    props.contentType.fields.reduce((acc, field) => {
        let value = props.item ? props.item[field.name] : '';

        if (field.type === 'boolean') {
            value = props.item ? Boolean(props.item[field.name]) : false;
        }

        if (field.type === 'relation') {
            if (field.settings?.multiple) {
                const relations = props.item ? props.item[field.name] : [];
                acc[field.name] = Array.isArray(relations) ? relations.map((r: any) => r.id) : [];
            } else {
                acc[field.name + '_id'] = props.item ? props.item[field.name + '_id'] : '';
            }
            return acc;
        }

        if (field.type === 'media') {
            acc[field.name + '_id'] = props.item ? props.item[field.name + '_id'] : '';
            return acc;
        }

        if (field.type === 'component') {
            acc[field.name] = props.item ? props.item[field.name] : {};
            return acc;
        }

        if (field.type === 'dynamic_zone') {
            acc[field.name] = props.item ? props.item[field.name] : [];
            return acc;
        }

        if (field.type === 'json') {
            // Ensure JSON fields are initialized as array or object if empty
            // For now, let's treat it as a JSON string for editing.
            const val = props.item ? props.item[field.name] : '';
            value = typeof val === 'object' ? JSON.stringify(val, null, 2) : val;
        }

        acc[field.name] = value;
        return acc;
    }, {
        published_at: props.item ? props.item.published_at : null,
        locale: props.item ? props.item.locale : 'en'
    } as Record<string, any>)
);

const submit = () => {
    if (isEditing.value && props.item) {
        form.put(route('admin.content.update', { slug: props.contentType.slug, id: props.item.id }));
    } else {
        // Parse JSON fields back to object before submit if we used string input
        // Use transform or manual post? form.transform() is cleaner.
        form.transform((data) => {
            const finalData = { ...data };
            props.contentType.fields.forEach(f => {
                if (f.type === 'json' && typeof finalData[f.name] === 'string') {
                    try {
                        finalData[f.name] = JSON.parse(finalData[f.name]);
                    } catch {
                        // let it fail backend validation if bad json
                    }
                }
            });
            return finalData;
        }).post(route('admin.content.store', props.contentType.slug));
    }
};
</script>
<template>
    <AdminLayout>

        <Head :title="`${isEditing ? 'Edit' : 'Create'} ${contentType.name}`" />

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg max-w-2xl mx-auto p-6">
                    <h2 class="text-2xl font-bold mb-6 text-gray-900 dark:text-white">{{ isEditing ? 'Edit' : 'Create'
                        }} {{ contentType.name }}</h2>

                    <form @submit.prevent="submit">
                        <!-- Locale Selector -->
                        <div v-if="contentType.is_localized" class="mb-6 w-1/3">
                            <Select id="locale" v-model="form.locale" label="Locale"
                                :options="[{ value: 'en', label: 'English' }, { value: 'fr', label: 'French' }, { value: 'de', label: 'German' }, { value: 'es', label: 'Spanish' }]" />
                        </div>

                        <!-- Dynamic Fields -->
                        <div v-for="field in contentType.fields" :key="field.name" class="mb-6">
                            <!-- Text Input -->
                            <Input v-if="field.type === 'text'" :id="field.name" v-model="form[field.name]"
                                :label="field.name.replace(/_/g, ' ')" :error="form.errors[field.name]" />

                            <!-- Integer Input -->
                            <Input v-if="field.type === 'integer'" :id="field.name" v-model="form[field.name]"
                                type="number" :label="field.name.replace(/_/g, ' ')" :error="form.errors[field.name]" />

                            <!-- Long Text (RichEditor) -->
                            <div v-if="field.type === 'longtext'">
                                <label :for="field.name"
                                    class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5 capitalize">
                                    {{ field.name.replace(/_/g, ' ') }}
                                </label>
                                <RichEditor :model-value="form[field.name]"
                                    @update:model-value="val => form[field.name] = val" />
                                <p v-if="form.errors[field.name]" class="mt-1.5 text-sm text-red-600 dark:text-red-400">
                                    {{ form.errors[field.name] }}</p>
                            </div>

                            <!-- Checkbox -->
                            <div v-if="field.type === 'boolean'">
                                <div class="flex items-center">
                                    <input :id="field.name" v-model="form[field.name]" type="checkbox"
                                        class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 dark:border-gray-600 dark:bg-gray-700 rounded" />
                                    <label :for="field.name"
                                        class="ml-2 block text-sm text-gray-900 dark:text-gray-300 capitalize">
                                        {{ field.name.replace(/_/g, ' ') }}
                                    </label>
                                </div>
                                <p v-if="form.errors[field.name]" class="mt-1.5 text-sm text-red-600 dark:text-red-400">
                                    {{ form.errors[field.name]
                                    }}</p>
                            </div>

                            <!-- Datetime -->
                            <Input v-if="field.type === 'datetime'" :id="field.name" v-model="form[field.name]"
                                type="datetime-local" :label="field.name.replace(/_/g, ' ')"
                                :error="form.errors[field.name]" />

                            <!-- Relation (Belongs To & Many-to-Many) -->
                            <Select v-if="field.type === 'relation'" :id="field.name"
                                v-model="form[field.settings?.multiple ? field.name : field.name + '_id']"
                                :multiple="field.settings?.multiple" :label="field.name.replace(/_/g, ' ')"
                                :placeholder="`Select ${field.name.replace(/_/g, ' ')}`"
                                :options="(options?.[field.name] || []).map(opt => ({ value: opt.id, label: opt.label }))"
                                :error="form.errors[field.settings?.multiple ? field.name : field.name + '_id']" />

                            <!-- Media Upload -->
                            <div v-if="field.type === 'media'">
                                <label
                                    class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5 capitalize">
                                    {{ field.name.replace(/_/g, ' ') }}
                                </label>
                                <MediaUpload v-model="form[field.name + '_id']" />
                                <p v-if="form.errors[field.name + '_id']"
                                    class="mt-1.5 text-sm text-red-600 dark:text-red-400">{{ form.errors[field.name +
                                        '_id'] }}</p>
                            </div>

                            <!-- Component Field -->
                            <div v-if="field.type === 'component'">
                                <label
                                    class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5 capitalize">
                                    {{ field.name.replace(/_/g, ' ') }}
                                </label>
                                <ComponentField v-if="field.settings?.related_content_type_id && components"
                                    :model-value="form[field.name] || {}"
                                    @update:model-value="(val) => form[field.name] = val"
                                    :component-id="field.settings.related_content_type_id" :components="components" />
                                <p v-if="form.errors[field.name]" class="mt-1.5 text-sm text-red-600 dark:text-red-400">
                                    {{ form.errors[field.name] }}</p>
                            </div>

                            <!-- Dynamic Zone Field -->
                            <div v-if="field.type === 'dynamic_zone'">
                                <label
                                    class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5 capitalize">
                                    {{ field.name.replace(/_/g, ' ') }}
                                </label>
                                <DynamicZoneField v-if="components" :model-value="form[field.name] || []"
                                    @update:model-value="(val) => form[field.name] = val" :field="field"
                                    :components="components" />
                                <p v-if="form.errors[field.name]" class="mt-1.5 text-sm text-red-600 dark:text-red-400">
                                    {{ form.errors[field.name] }}</p>
                            </div>

                            <!-- Email Input -->
                            <Input v-if="field.type === 'email'" :id="field.name" v-model="form[field.name]"
                                type="email" :label="field.name.replace(/_/g, ' ')" :error="form.errors[field.name]" />


                            <!-- JSON Input (Textarea for now) -->
                            <div v-if="field.type === 'json'">
                                <label :for="field.name"
                                    class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5 capitalize">
                                    {{ field.name.replace(/_/g, ' ') }} (JSON)
                                </label>
                                <textarea :id="field.name" v-model="form[field.name]" rows="5"
                                    class="border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm w-full font-mono text-sm"
                                    :class="{ 'border-red-300 focus:border-red-500 focus:ring-red-500': form.errors[field.name] }"></textarea>
                                <p v-if="form.errors[field.name]" class="mt-1.5 text-sm text-red-600 dark:text-red-400">
                                    {{ form.errors[field.name] }}</p>
                            </div>

                            <!-- Enum Select -->
                            <Select v-if="field.type === 'enum'" :id="field.name" v-model="form[field.name]"
                                :label="field.name.replace(/_/g, ' ')"
                                :placeholder="`Select ${field.name.replace(/_/g, ' ')}`"
                                :options="(field.settings?.options || []).map(opt => ({ value: opt, label: opt }))"
                                :error="form.errors[field.name]" />
                        </div>

                        <!-- Publishing Section Header -->
                        <div class="mt-10 mb-4">
                            <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                Publishing</h3>
                        </div>

                        <!-- Published At (System Field) -->
                        <div class="mb-6">
                            <Input id="published_at" v-model="form.published_at" type="datetime-local"
                                label="Published At" />
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Leave empty to save as Draft.</p>
                        </div>

                        <div class="flex justify-end pt-4">
                            <button type="submit" :disabled="form.processing"
                                class="bg-indigo-600 text-white px-4 py-2 rounded-md hover:bg-indigo-700 disabled:opacity-50 transition-colors">
                                {{ (isEditing ? 'Update' : 'Save') + (form.published_at ? ' & Publish' : '') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
