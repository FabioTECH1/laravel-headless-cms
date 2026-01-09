import AdminLayout from '@/Layouts/AdminLayout.vue';
import MediaUpload from '@/Components/MediaUpload.vue';
import { useForm, Head } from '@inertiajs/vue3';
import { computed } from 'vue';

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

        if (field.type === 'relation') {
            // For relations, we store the ID in field_name_id
            // If editing, the item likely comes with the ID field already.
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
    if (isEditing.value) {
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
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg max-w-2xl mx-auto p-6">
                    <h2 class="text-2xl font-bold mb-6">{{ isEditing ? 'Edit' : 'Create' }} {{ contentType.name }}</h2>

                    <form @submit.prevent="submit">
                        <div v-for="field in contentType.fields" :key="field.name" class="mb-6">
                            <label :for="field.name" class="block text-sm font-medium text-gray-700 mb-1 capitalize">{{
                                field.name.replace(/_/g, ' ') }}</label>

                            <!-- Text Input -->
                            <input v-if="field.type === 'text'" :id="field.name" v-model="form[field.name]" type="text"
                                class="w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />

                            <!-- Integer Input -->
                            <input v-if="field.type === 'integer'" :id="field.name" v-model="form[field.name]"
                                type="number"
                                class="w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />

                            <!-- Long Text (Textarea) -->
                            <textarea v-if="field.type === 'longtext'" :id="field.name" v-model="form[field.name]"
                                rows="5"
                                class="w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500"></textarea>

                            <!-- Checkbox -->
                            <div v-if="field.type === 'boolean'" class="flex items-center">
                                <input :id="field.name" v-model="form[field.name]" type="checkbox"
                                    class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded" />
                                <label :for="field.name" class="ml-2 block text-sm text-gray-900">
                                    Enabled
                                </label>
                            </div>

                            <!-- Datetime -->
                            <input v-if="field.type === 'datetime'" :id="field.name" v-model="form[field.name]"
                                type="datetime-local"
                                class="w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />

                            <!-- Relation (Belongs To) -->
                            <div v-if="field.type === 'relation'">
                                <select
                                    :id="field.name"
                                    v-model="form[field.name + '_id']"
                                    class="w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                >
                                    <option value="">Select {{ field.name.replace(/_/g, ' ') }}</option>
                                    <option v-for="option in (options?.[field.name] || [])" :key="option.id" :value="option.id">
                                        {{ option.label }}
                                    </option>
                                </select>
                            </div>

                            <!-- Media Upload -->
                            <div v-if="field.type === 'media'">
                                <MediaUpload v-model="form[field.name + '_id']" />
                            </div>
                        </div>

                        <!-- Published At (System Field) -->
                        <div class="mb-6 border-t pt-4">
                            <label for="published_at" class="block text-sm font-medium text-gray-700 mb-1">Published
                                At</label>
                            <input id="published_at" v-model="form.published_at" type="datetime-local"
                                class="w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                            <p class="text-xs text-gray-500 mt-1">Leave empty to save as Draft.</p>
                        </div>

                        <div class="flex justify-end pt-4 border-t border-gray-100">
                            <button type="submit" :disabled="form.processing"
                                class="bg-indigo-600 text-white px-4 py-2 rounded-md hover:bg-indigo-700 disabled:opacity-50 transition-colors">
                                {{ isEditing ? 'Update' : 'Save' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
