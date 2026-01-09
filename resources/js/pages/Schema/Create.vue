<script setup lang="ts">
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { useForm, Head } from '@inertiajs/vue3';

const form = useForm({
    name: '',
    fields: [
        { name: '', type: 'text' }
    ]
});

const addField = () => {
    form.fields.push({ name: '', type: 'text' });
};

const removeField = (index: number) => {
    if (form.fields.length > 1) {
        form.fields.splice(index, 1);
    }
};

const submit = () => {
    form.post(route('admin.schema.store'));
};
</script>

<template>
    <AdminLayout>
        <Head title="Create Content Type" />

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg max-w-2xl mx-auto p-6">
                    <h2 class="text-2xl font-bold mb-6">Create Content Type</h2>

                    <form @submit.prevent="submit">
                        <div class="mb-6">
                            <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Name</label>
                            <input
                                id="name"
                                v-model="form.name"
                                type="text"
                                class="w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                placeholder="e.g. BlogPost"
                                required
                            />
                            <div v-if="form.errors.name" class="text-red-500 text-sm mt-1">{{ form.errors.name }}</div>
                        </div>

                        <div class="mb-6">
                            <div class="flex items-center justify-between mb-2">
                                <label class="block text-sm font-medium text-gray-700">Fields</label>
                                <button
                                    type="button"
                                    @click="addField"
                                    class="text-sm text-indigo-600 hover:text-indigo-800 font-medium flex items-center gap-1"
                                >
                                    <span>+ Add Field</span>
                                </button>
                            </div>

                            <div class="space-y-3">
                                <div
                                    v-for="(field, index) in form.fields"
                                    :key="index"
                                    class="flex gap-4 items-start"
                                >
                                    <div class="flex-1">
                                        <input
                                            v-model="field.name"
                                            type="text"
                                            class="w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm"
                                            placeholder="Field Name"
                                            required
                                        />
                                        <div v-if="form.errors[`fields.${index}.name`]" class="text-red-500 text-xs mt-1">
                                            {{ form.errors[`fields.${index}.name`] }}
                                        </div>
                                    </div>

                                    <div class="w-1/3">
                                        <select
                                            v-model="field.type"
                                            class="w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm"
                                        >
                                            <option value="text">Text</option>
                                            <option value="longtext">Long Text</option>
                                            <option value="integer">Integer</option>
                                            <option value="boolean">Boolean</option>
                                            <option value="datetime">Datetime</option>
                                        </select>
                                    </div>

                                    <button
                                        type="button"
                                        @click="removeField(index)"
                                        :disabled="form.fields.length === 1"
                                        class="mt-1 text-gray-400 hover:text-red-500 disabled:opacity-50 disabled:cursor-not-allowed"
                                        title="Remove Field"
                                    >
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                                          <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="flex justify-end pt-4 border-t border-gray-100">
                            <button
                                type="submit"
                                :disabled="form.processing"
                                class="bg-indigo-600 text-white px-4 py-2 rounded-md hover:bg-indigo-700 disabled:opacity-50 transition-colors"
                            >
                                Create Schema
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
