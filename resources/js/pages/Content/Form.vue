<script setup lang="ts">
import AdminLayout from '@/Layouts/AdminLayout.vue';
import MediaUpload from '@/Components/MediaUpload.vue';
import RichEditor from '@/Components/RichEditor.vue';
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
        }>;
    };
    item?: Record<string, any>;
    options?: Record<string, Array<{ id: number; label: string }>>;
}>();

const isEditing = computed(() => !!props.item);

// Initialize form with defaults or existing item values
const form = useForm(
    props.contentType.fields.reduce((acc, field) => {
        let value = props.item ? props.item[field.name] : '';

        if (field.type === 'boolean') {
            value = props.item ? Boolean(props.item[field.name]) : false;
        }

        if (field.type === 'relation' || field.type === 'media') {
            acc[field.name + '_id'] = props.item ? props.item[field.name + '_id'] : '';
            return acc;
        }

        acc[field.name] = value;
        return acc;
    }, {
        published_at: props.item ? props.item.published_at : null
    } as Record<string, any>)
);

const submit = () => {
    if (isEditing.value && props.item) {
        form.put(route('admin.content.update', { slug: props.contentType.slug, id: props.item.id }));
    } else {
        form.post(route('admin.content.store', props.contentType.slug));
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
                        <!-- Dynamic Fields -->
                        <div v-for="field in contentType.fields" :key="field.name" class="mb-6">
                            <!-- Text Input -->
                            <Input v-if="field.type === 'text'" :id="field.name" v-model="form[field.name]"
                                :label="field.name.replace(/_/g, ' ')" />

                            <!-- Integer Input -->
                            <Input v-if="field.type === 'integer'" :id="field.name" v-model="form[field.name]"
                                type="number" :label="field.name.replace(/_/g, ' ')" />

                            <!-- Long Text (RichEditor) -->
                            <div v-if="field.type === 'longtext'">
                                <label :for="field.name"
                                    class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5 capitalize">
                                    {{ field.name.replace(/_/g, ' ') }}
                                </label>
                                <RichEditor :model-value="form[field.name]"
                                    @update:model-value="val => form[field.name] = val" />
                            </div>

                            <!-- Checkbox -->
                            <div v-if="field.type === 'boolean'" class="flex items-center">
                                <input :id="field.name" v-model="form[field.name]" type="checkbox"
                                    class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 dark:border-gray-600 dark:bg-gray-700 rounded" />
                                <label :for="field.name"
                                    class="ml-2 block text-sm text-gray-900 dark:text-gray-300 capitalize">
                                    {{ field.name.replace(/_/g, ' ') }}
                                </label>
                            </div>

                            <!-- Datetime -->
                            <Input v-if="field.type === 'datetime'" :id="field.name" v-model="form[field.name]"
                                type="datetime-local" :label="field.name.replace(/_/g, ' ')" />

                            <!-- Relation (Belongs To) -->
                            <Select v-if="field.type === 'relation'" :id="field.name" v-model="form[field.name + '_id']"
                                :label="field.name.replace(/_/g, ' ')"
                                :placeholder="`Select ${field.name.replace(/_/g, ' ')}`"
                                :options="(options?.[field.name] || []).map(opt => ({ value: opt.id, label: opt.label }))" />

                            <!-- Media Upload -->
                            <div v-if="field.type === 'media'">
                                <label
                                    class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5 capitalize">
                                    {{ field.name.replace(/_/g, ' ') }}
                                </label>
                                <MediaUpload v-model="form[field.name + '_id']" />
                            </div>
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
