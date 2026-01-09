<script setup lang="ts">
import { ref } from 'vue';
import axios from 'axios';
import { route } from '@/route-helper';
import { useToast } from '@/composables/useToast';

const { error: showError } = useToast();

defineProps<{
    modelValue?: number | string;
}>();

const emit = defineEmits(['update:modelValue']);

const fileInput = ref<HTMLInputElement | null>(null);
const isUploading = ref(false);
const previewUrl = ref<string | null>(null);
const filename = ref<string | null>(null);

// If we have an initial value (ID), we ideally want to fetch the file info
// For MVP, we might display "File ID: 123" unless we pass the full object or fetch it.
// Let's assume for now we just show the ID or trigger a fetch if needed.

const upload = async (event: Event) => {
    const target = event.target as HTMLInputElement;
    if (!target.files || target.files.length === 0) return;

    const file = target.files[0];
    const formData = new FormData();
    formData.append('file', file);

    isUploading.value = true;

    try {
        const response = await axios.post(route('admin.media.store'), formData, {
            headers: {
                'Content-Type': 'multipart/form-data'
            }
        });

        const { id, url, filename: name } = response.data;

        previewUrl.value = url;
        filename.value = name;
        emit('update:modelValue', id);
    } catch (error) {
        console.error('Upload failed', error);
        showError('Upload failed. Please try again.');
    } finally {
        isUploading.value = false;
        if (fileInput.value) fileInput.value.value = '';
    }
};

const remove = () => {
    emit('update:modelValue', null);
    previewUrl.value = null;
    filename.value = null;
};
</script>

<template>
    <div
        class="border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-lg p-6 flex flex-col items-center justify-center bg-gray-50 dark:bg-gray-800 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">
        <div v-if="modelValue" class="text-center">
            <div v-if="previewUrl" class="mb-2">
                <img v-if="previewUrl.match(/\.(jpeg|jpg|gif|png)$/i)" :src="previewUrl"
                    class="max-h-32 mx-auto rounded shadow-sm" />
                <div v-else
                    class="text-gray-500 dark:text-gray-400 text-sm p-4 bg-white dark:bg-gray-900 rounded border dark:border-gray-700">
                    {{ filename }}</div>
            </div>
            <div v-else class="text-sm text-gray-500 dark:text-gray-400 mb-2">
                Media ID: {{ modelValue }}
            </div>
            <button type="button" @click="remove" class="text-red-600 dark:text-red-400 text-sm hover:underline">Remove
                Media</button>
        </div>

        <div v-else class="text-center w-full">
            <template v-if="isUploading">
                <div class="animate-pulse flex flex-col items-center">
                    <div class="h-4 w-4 bg-indigo-500 rounded-full mb-2"></div>
                    <span class="text-sm text-gray-500 dark:text-gray-400">Uploading...</span>
                </div>
            </template>
            <template v-else>
                <input ref="fileInput" type="file" class="hidden" @change="upload" />
                <button type="button" @click="fileInput?.click()"
                    class="bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-200 px-4 py-2 rounded-md hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                    Select File
                </button>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">Images, Docs (Max 10MB)</p>
            </template>
        </div>
    </div>
</template>
