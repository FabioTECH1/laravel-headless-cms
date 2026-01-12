<script setup lang="ts">
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { ref, onMounted, computed } from 'vue';
import axios from 'axios';
import { Head } from '@inertiajs/vue3';

interface MediaFile {
    id: string;
    filename: string;
    alt_text: string | null;
    caption: string | null;
    url: string;
    mime_type: string;
    size: number;
    width: number | null;
    height: number | null;
    folder_id: string | null;
    created_at: string;
}

interface MediaFolder {
    id: string;
    name: string;
    parent_id: string | null;
    files_count: number;
}

const media = ref<MediaFile[]>([]);
const folders = ref<MediaFolder[]>([]);
const currentFolderId = ref<string | null>(null);
const searchQuery = ref('');
const isLoading = ref(false);
const selectedFile = ref<MediaFile | null>(null);
const showUploadModal = ref(false);
const isUploading = ref(false);

const currentFolder = computed(() => {
    if (!currentFolderId.value) return null;
    return folders.value.find(f => f.id === currentFolderId.value);
});

const breadcrumbs = computed(() => {
    const crumbs: MediaFolder[] = [];
    let folder = currentFolder.value;
    while (folder) {
        crumbs.unshift(folder);
        folder = folders.value.find(f => f.id === folder?.parent_id);
    }
    return crumbs;
});

const loadMedia = async () => {
    isLoading.value = true;
    try {
        const params: any = {};
        if (currentFolderId.value) {
            params.folder_id = currentFolderId.value;
        } else {
            params.folder_id = 'null';
        }
        if (searchQuery.value) {
            params.search = searchQuery.value;
        }

        const response = await axios.get('/admin/media', { params });
        media.value = response.data.data;
    } catch (error) {
        console.error('Failed to load media', error);
    } finally {
        isLoading.value = false;
    }
};

const loadFolders = async () => {
    try {
        const response = await axios.get('/admin/media-folders');
        folders.value = response.data;
    } catch (error) {
        console.error('Failed to load folders', error);
    }
};

const navigateToFolder = (folderId: string | null) => {
    currentFolderId.value = folderId;
    loadMedia();
};

const uploadFile = async (event: Event) => {
    const target = event.target as HTMLInputElement;
    if (!target.files || target.files.length === 0) return;

    const file = target.files[0];
    const formData = new FormData();
    formData.append('file', file);
    if (currentFolderId.value) {
        formData.append('folder_id', currentFolderId.value);
    }

    isUploading.value = true;
    try {
        await axios.post('/admin/media', formData);
        loadMedia();
        showUploadModal.value = false;
    } catch (error) {
        console.error('Upload failed', error);
        alert('Upload failed. Please try again.');
    } finally {
        isUploading.value = false;
        target.value = '';
    }
};

const deleteFile = async (id: string) => {
    if (!confirm('Are you sure you want to delete this file?')) return;

    try {
        await axios.delete(`/admin/media/${id}`);
        loadMedia();
        selectedFile.value = null;
    } catch (error) {
        console.error('Delete failed', error);
        alert('Delete failed. Please try again.');
    }
};

const formatFileSize = (bytes: number) => {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
};

onMounted(() => {
    loadMedia();
    loadFolders();
});
</script>

<template>
    <AdminLayout>

        <Head title="Media Library" />

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <!-- Header -->
                <div class="mb-6 flex items-center justify-between">
                    <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Media Library</h2>
                    <button @click="showUploadModal = true"
                        class="bg-indigo-600 text-white px-4 py-2 rounded-md hover:bg-indigo-700 transition-colors">
                        Upload File
                    </button>
                </div>

                <!-- Breadcrumbs -->
                <div class="mb-4 flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400">
                    <button @click="navigateToFolder(null)" class="hover:text-indigo-600 dark:hover:text-indigo-400">
                        All Media
                    </button>
                    <template v-for="crumb in breadcrumbs" :key="crumb.id">
                        <span>/</span>
                        <button @click="navigateToFolder(crumb.id)"
                            class="hover:text-indigo-600 dark:hover:text-indigo-400">
                            {{ crumb.name }}
                        </button>
                    </template>
                </div>

                <!-- Search -->
                <div class="mb-6">
                    <input v-model="searchQuery" @input="loadMedia" type="text" placeholder="Search files..."
                        class="w-full max-w-md px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-800 text-gray-900 dark:text-white" />
                </div>

                <!-- Grid -->
                <div v-if="isLoading" class="text-center py-12">
                    <div
                        class="animate-spin h-8 w-8 border-4 border-indigo-600 border-t-transparent rounded-full mx-auto">
                    </div>
                </div>

                <div v-else-if="media.length === 0 && folders.length === 0" class="text-center py-12">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                        stroke="currentColor" class="w-16 h-16 mx-auto text-gray-400 mb-4">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 0 0 1.5-1.5V6a1.5 1.5 0 0 0-1.5-1.5H3.75A1.5 1.5 0 0 0 2.25 6v12a1.5 1.5 0 0 0 1.5 1.5Zm10.5-11.25h.008v.008h-.008V8.25Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" />
                    </svg>
                    <p class="text-gray-500 dark:text-gray-400 mb-4">No media files yet</p>
                    <button @click="showUploadModal = true"
                        class="bg-indigo-600 text-white px-4 py-2 rounded-md hover:bg-indigo-700 transition-colors">
                        Upload Your First File
                    </button>
                </div>

                <div v-else class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-4">
                    <!-- Folders -->
                    <div v-for="folder in folders.filter((f: MediaFolder) => f.parent_id === currentFolderId)"
                        :key="folder.id" @click="navigateToFolder(folder.id)"
                        class="aspect-square bg-gray-100 dark:bg-gray-700 rounded-lg p-4 flex flex-col items-center justify-center cursor-pointer hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                            stroke="currentColor" class="w-12 h-12 text-yellow-500 mb-2">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M2.25 12.75V12A2.25 2.25 0 0 1 4.5 9.75h15A2.25 2.25 0 0 1 21.75 12v.75m-8.69-6.44-2.12-2.12a1.5 1.5 0 0 0-1.061-.44H4.5A2.25 2.25 0 0 0 2.25 6v12a2.25 2.25 0 0 0 2.25 2.25h15A2.25 2.25 0 0 0 21.75 18V9a2.25 2.25 0 0 0-2.25-2.25h-5.379a1.5 1.5 0 0 1-1.06-.44Z" />
                        </svg>
                        <span class="text-sm font-medium text-gray-900 dark:text-white text-center truncate w-full">{{
                            folder.name }}</span>
                        <span class="text-xs text-gray-500 dark:text-gray-400">{{ folder.files_count }} files</span>
                    </div>

                    <!-- Files -->
                    <div v-for="file in media" :key="file.id" @click="selectedFile = file"
                        class="aspect-square bg-white dark:bg-gray-800 rounded-lg overflow-hidden cursor-pointer hover:ring-2 hover:ring-indigo-500 transition-all shadow-sm">
                        <div class="h-full flex flex-col">
                            <div class="flex-1 flex items-center justify-center bg-gray-100 dark:bg-gray-700 p-2">
                                <img v-if="file.mime_type.startsWith('image/')" :src="file.url"
                                    :alt="file.alt_text || file.filename"
                                    class="max-w-full max-h-full object-contain" />
                                <div v-else class="text-gray-400 text-center">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                        stroke-width="1.5" stroke="currentColor" class="w-12 h-12 mx-auto">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                                    </svg>
                                </div>
                            </div>
                            <div class="p-2 bg-white dark:bg-gray-800">
                                <p class="text-xs text-gray-900 dark:text-white truncate">{{ file.filename }}</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">{{ formatFileSize(file.size) }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Upload Modal -->
                <div v-if="showUploadModal"
                    class="fixed inset-0 bg-gray-900/10 dark:bg-black/20 backdrop-blur flex items-center justify-center z-50 p-4"
                    @click.self="showUploadModal = false">
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-md w-full">
                        <div class="p-6">
                            <h3 class="text-lg font-bold mb-4 text-gray-900 dark:text-white">Upload File</h3>

                            <div
                                class="border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-lg p-8 text-center hover:border-indigo-500 dark:hover:border-indigo-400 transition-colors">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                    stroke-width="1.5" stroke="currentColor"
                                    class="w-12 h-12 mx-auto text-gray-400 mb-3">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5m-13.5-9L12 3m0 0 4.5 4.5M12 3v13.5" />
                                </svg>
                                <label class="cursor-pointer">
                                    <span
                                        class="text-indigo-600 dark:text-indigo-400 hover:text-indigo-700 dark:hover:text-indigo-300 font-medium">
                                        Choose a file
                                    </span>
                                    <span class="text-gray-500 dark:text-gray-400"> or drag and drop</span>
                                    <input type="file" @change="uploadFile" :disabled="isUploading" class="hidden" />
                                </label>
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">PNG, JPG, GIF, PDF up to 10MB
                                </p>
                            </div>

                            <div v-if="isUploading" class="mt-4">
                                <div
                                    class="flex items-center justify-center gap-2 text-indigo-600 dark:text-indigo-400">
                                    <div
                                        class="animate-spin h-4 w-4 border-2 border-indigo-600 dark:border-indigo-400 border-t-transparent rounded-full">
                                    </div>
                                    <span class="text-sm">Uploading...</span>
                                </div>
                            </div>
                        </div>

                        <div class="bg-gray-50 dark:bg-gray-900 px-6 py-4 rounded-b-lg flex justify-end gap-2">
                            <button @click="showUploadModal = false" :disabled="isUploading"
                                class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 rounded-md transition-colors disabled:opacity-50">
                                Cancel
                            </button>
                        </div>
                    </div>
                </div>

                <!-- File Details Modal -->
                <div v-if="selectedFile"
                    class="fixed inset-0 bg-gray-900/10 dark:bg-black/20 backdrop-blur flex items-center justify-center z-50 p-4"
                    @click.self="selectedFile = null">
                    <div class="bg-white dark:bg-gray-800 rounded-lg p-6 max-w-2xl w-full max-h-[90vh] overflow-y-auto">
                        <div class="flex justify-between items-start mb-4">
                            <h3 class="text-lg font-bold text-gray-900 dark:text-white">{{ selectedFile.filename }}</h3>
                            <button @click="selectedFile = null"
                                class="text-gray-500 hover:text-gray-700 dark:hover:text-gray-300">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                    stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>

                        <div class="mb-4">
                            <img v-if="selectedFile.mime_type.startsWith('image/')" :src="selectedFile.url"
                                :alt="selectedFile.alt_text || selectedFile.filename" class="max-w-full rounded-lg" />
                        </div>

                        <div class="space-y-2 text-sm">
                            <div><span class="font-medium">URL:</span> <input type="text" :value="selectedFile.url"
                                    readonly class="w-full mt-1 px-2 py-1 border rounded text-xs" /></div>
                            <div><span class="font-medium">Size:</span> {{ formatFileSize(selectedFile.size) }}</div>
                            <div v-if="selectedFile.width"><span class="font-medium">Dimensions:</span> {{
                                selectedFile.width }} ×
                                {{ selectedFile.height }}</div>
                            <div><span class="font-medium">Type:</span> {{ selectedFile.mime_type }}</div>
                        </div>

                        <div class="mt-6 flex justify-end gap-2">
                            <button @click="deleteFile(selectedFile.id)"
                                class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700">
                                Delete
                            </button>
                            <button @click="selectedFile = null"
                                class="px-4 py-2 bg-gray-200 dark:bg-gray-700 text-gray-900 dark:text-white rounded-md hover:bg-gray-300 dark:hover:bg-gray-600">
                                Close
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
