<script setup lang="ts">
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { ref, onMounted, computed } from 'vue';
import axios from 'axios';
import { Head } from '@inertiajs/vue3';
import { useToast } from '@/composables/useToast';
import { usePermissions } from '@/composables/usePermissions';
import ConfirmModal from '@/Components/ConfirmModal.vue';
import Input from '@/Components/Input.vue';

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
const showCreateFolderModal = ref(false);
const isUploading = ref(false);
const isCreatingFolder = ref(false);
const newFolderName = ref('');
const toast = useToast();
const { can } = usePermissions();
const showDeleteConfirm = ref(false);
const fileToDelete = ref<string | null>(null);
const folderToDelete = ref<string | null>(null);
const showDeleteFolderConfirm = ref(false);

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

const createFolder = async () => {
    if (!newFolderName.value.trim()) return;

    isCreatingFolder.value = true;
    try {
        await axios.post('/admin/media-folders', {
            name: newFolderName.value,
            parent_id: currentFolderId.value,
        });
        toast.success('Folder created successfully');
        showCreateFolderModal.value = false;
        newFolderName.value = '';
        loadFolders();
    } catch (error: any) {
        console.error('Failed to create folder', error);
        toast.error(error.response?.data?.message || 'Failed to create folder');
    } finally {
        isCreatingFolder.value = false;
    }
};

const deleteFolder = async (id: string) => {
    folderToDelete.value = id;
    showDeleteFolderConfirm.value = true;
};

const confirmDeleteFolder = async () => {
    if (!folderToDelete.value) return;

    try {
        await axios.delete(`/admin/media-folders/${folderToDelete.value}`);
        toast.success('Folder deleted successfully');
        loadFolders();
        if (currentFolderId.value === folderToDelete.value) {
            currentFolderId.value = null;
            loadMedia();
        }
    } catch (error: any) {
        console.error('Failed to delete folder', error);
        toast.error(error.response?.data?.message || 'Failed to delete folder');
    } finally {
        showDeleteFolderConfirm.value = false;
        folderToDelete.value = null;
    }
};

const uploadFile = async (event: Event) => {
    const target = event.target as HTMLInputElement;
    if (!target.files || target.files.length === 0) return;

    const file = target.files[0];

    // Check file size (server limit is 2MB)
    if (file.size > 2 * 1024 * 1024) {
        toast.error('File size exceeds the server limit of 2MB.');
        target.value = '';
        return;
    }

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
        toast.success('File uploaded successfully');
    } catch (error: any) {
        console.error('Upload failed', error);

        // Handle axios validation error
        let errorMessage = 'Upload failed. Please check the file and try again.';

        if (error.response && error.response.data) {
            if (error.response.data.message) {
                errorMessage = error.response.data.message;
            }
            // If validation errors collection exists
            if (error.response.data.errors) {
                const firstError = Object.values(error.response.data.errors)[0];
                if (typeof firstError === 'string') errorMessage = firstError;
                else if (Array.isArray(firstError) && firstError.length > 0) errorMessage = firstError[0];
            }
        }

        toast.error(errorMessage);
    } finally {
        isUploading.value = false;
        target.value = '';
    }
};

const deleteFile = async (id: string) => {
    fileToDelete.value = id;
    showDeleteConfirm.value = true;
};

const confirmDelete = async () => {
    if (!fileToDelete.value) return;

    try {
        await axios.delete(`/admin/media/${fileToDelete.value}`);
        loadMedia();
        selectedFile.value = null;
        toast.success('File deleted successfully');
        showDeleteConfirm.value = false;
    } catch (error) {
        console.error('Delete failed', error);
        toast.error('Delete failed. Please try again.');
    } finally {
        fileToDelete.value = null;
    }
};

const moveFile = async (file: MediaFile, folderId: string) => {
    try {
        await axios.put(`/admin/media/${file.id}`, {
            folder_id: folderId || null
        });

        // Update local state
        file.folder_id = folderId || null;

        // Optimistic UI update: Remove from current view if folder changed
        if (currentFolderId.value !== (folderId || null)) {
            const index = media.value.findIndex(m => m.id === file.id);
            if (index !== -1) media.value.splice(index, 1);
            selectedFile.value = null; // Close modal
        }

        toast.success('File moved successfully');
        loadFolders(); // Refresh folder counts
    } catch (error) {
        console.error('Move failed', error);
        toast.error('Failed to move file');
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
                <!-- Header -->
                <div class="sm:flex sm:items-center justify-between mb-6">
                    <div class="sm:flex-auto">
                        <h1 class="text-xl font-semibold text-gray-900 dark:text-white">Media Library</h1>
                        <p class="mt-2 text-sm text-gray-700 dark:text-gray-300">Manage your files and assets.</p>
                    </div>
                    <div class="mt-4 sm:ml-16 sm:mt-0 sm:flex-none flex gap-2">
                        <button v-if="can('upload-media')" @click="showCreateFolderModal = true"
                            class="bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-200 border border-gray-300 dark:border-gray-600 px-4 py-2 rounded-md hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors shadow-sm text-sm font-medium">
                            New Folder
                        </button>
                        <button v-if="can('upload-media')" @click="showUploadModal = true"
                            class="bg-indigo-600 text-white px-4 py-2 rounded-md hover:bg-indigo-700 transition-colors shadow-sm text-sm font-medium">
                            Upload File
                        </button>
                    </div>
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
                    <button @click="showCreateFolderModal = true"
                        class="ml-2 bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-200 border border-gray-300 dark:border-gray-600 px-4 py-2 rounded-md hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors">
                        Create Folder
                    </button>
                </div>

                <div v-else class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-4">
                    <!-- Folders -->
                    <div v-for="folder in folders.filter((f: MediaFolder) => f.parent_id === currentFolderId)"
                        :key="folder.id"
                        class="relative aspect-square bg-gray-100 dark:bg-gray-700 rounded-lg p-4 flex flex-col items-center justify-center cursor-pointer hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors group">

                        <div @click="navigateToFolder(folder.id)"
                            class="w-full h-full flex flex-col items-center justify-center">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                                stroke="currentColor" class="w-12 h-12 text-yellow-500 mb-2">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M2.25 12.75V12A2.25 2.25 0 0 1 4.5 9.75h15A2.25 2.25 0 0 1 21.75 12v.75m-8.69-6.44-2.12-2.12a1.5 1.5 0 0 0-1.061-.44H4.5A2.25 2.25 0 0 0 2.25 6v12a2.25 2.25 0 0 0 2.25 2.25h15A2.25 2.25 0 0 0 21.75 18V9a2.25 2.25 0 0 0-2.25-2.25h-5.379a1.5 1.5 0 0 1-1.06-.44Z" />
                            </svg>
                            <span
                                class="text-sm font-medium text-gray-900 dark:text-white text-center truncate w-full">{{
                                    folder.name }}</span>
                            <span class="text-xs text-gray-500 dark:text-gray-400">{{ folder.files_count }} files</span>
                        </div>

                        <!-- Delete Folder Button -->
                        <button v-if="can('delete-media')" @click.stop="deleteFolder(folder.id)"
                            class="absolute top-2 right-2 p-1 text-gray-400 hover:text-red-500 opacity-0 group-hover:opacity-100 transition-opacity bg-white dark:bg-gray-800 rounded-full shadow-sm">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                                stroke="currentColor" class="w-4 h-4">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                            </svg>
                        </button>
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

                <!-- Create Folder Modal -->
                <div v-if="showCreateFolderModal"
                    class="fixed inset-0 bg-gray-900/10 dark:bg-black/20 backdrop-blur flex items-center justify-center z-50 p-4"
                    @click.self="showCreateFolderModal = false">
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-sm w-full">
                        <div class="p-6">
                            <h3 class="text-lg font-bold mb-4 text-gray-900 dark:text-white">New Folder</h3>
                            <Input id="folderName" v-model="newFolderName" label="Folder Name"
                                placeholder="e.g. Vacation Photos" @keyup.enter="createFolder" />
                        </div>
                        <div class="bg-gray-50 dark:bg-gray-900 px-6 py-4 rounded-b-lg flex justify-end gap-2">
                            <button @click="showCreateFolderModal = false" :disabled="isCreatingFolder"
                                class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 rounded-md transition-colors disabled:opacity-50">
                                Cancel
                            </button>
                            <button @click="createFolder" :disabled="isCreatingFolder || !newFolderName.trim()"
                                class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 rounded-md transition-colors disabled:opacity-50">
                                {{ isCreatingFolder ? 'Creating...' : 'Create' }}
                            </button>
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
                    class="fixed inset-0 bg-gray-900/50 dark:bg-black/70 backdrop-blur-sm flex items-center justify-center z-50 p-4"
                    @click.self="selectedFile = null">
                    <div
                        class="bg-white dark:bg-gray-800 rounded-xl shadow-2xl p-6 max-w-3xl w-full max-h-[90vh] overflow-y-auto flex flex-col">
                        <div
                            class="flex justify-between items-center mb-6 border-b border-gray-100 dark:border-gray-700 pb-4">
                            <h3 class="text-xl font-bold text-gray-900 dark:text-white truncate pr-4">{{
                                selectedFile.filename }}</h3>
                            <button @click="selectedFile = null"
                                class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 transition-colors p-1 rounded-full hover:bg-gray-100 dark:hover:bg-gray-700">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2"
                                    stroke="currentColor" class="w-5 h-5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mb-6">
                            <!-- Image/Preview Side -->
                            <div
                                class="bg-gray-50 dark:bg-gray-900/50 rounded-lg p-4 flex items-center justify-center border border-gray-100 dark:border-gray-700 min-h-[200px]">
                                <img v-if="selectedFile.mime_type.startsWith('image/')" :src="selectedFile.url"
                                    :alt="selectedFile.alt_text || selectedFile.filename"
                                    class="max-w-full max-h-[400px] object-contain rounded-md shadow-sm" />
                                <div v-else class="text-center p-8">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                        stroke-width="1.5" stroke="currentColor"
                                        class="w-20 h-20 mx-auto text-gray-400 mb-4">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                                    </svg>
                                    <p class="text-gray-500 dark:text-gray-400">{{ selectedFile.mime_type }}</p>
                                </div>
                            </div>

                            <!-- Details Side -->
                            <div class="space-y-4">
                                <div>
                                    <label
                                        class="block text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1">Public
                                        URL</label>
                                    <div class="flex">
                                        <input type="text" :value="selectedFile.url" readonly
                                            class="flex-1 bg-gray-50 dark:bg-gray-900 border border-gray-300 dark:border-gray-600 text-gray-900 dark:text-white text-sm rounded-l-md focus:ring-indigo-500 focus:border-indigo-500 block w-full p-2.5" />
                                        <a :href="selectedFile.url" target="_blank"
                                            class="inline-flex items-center px-3 text-sm text-gray-900 bg-gray-100 border border-l-0 border-gray-300 rounded-r-md dark:bg-gray-600 dark:text-gray-400 dark:border-gray-600 hover:bg-gray-200 dark:hover:bg-gray-500">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                                stroke-width="1.5" stroke="currentColor" class="w-4 h-4">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="M13.5 6H5.25A2.25 2.25 0 0 0 3 8.25v10.5A2.25 2.25 0 0 0 5.25 21h10.5A2.25 2.25 0 0 0 18 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25" />
                                            </svg>
                                        </a>
                                    </div>
                                </div>

                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label
                                            class="block text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1">Size</label>
                                        <p class="text-sm font-medium text-gray-900 dark:text-gray-200">{{
                                            formatFileSize(selectedFile.size) }}</p>
                                    </div>
                                    <div v-if="selectedFile.width">
                                        <label
                                            class="block text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1">Dimensions</label>
                                        <p class="text-sm font-medium text-gray-900 dark:text-gray-200">{{
                                            selectedFile.width }} × {{
                                                selectedFile.height }} px</p>
                                    </div>
                                    <div class="col-span-2">
                                        <label
                                            class="block text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1">MIME
                                            Type</label>
                                        <p class="text-sm font-medium text-gray-900 dark:text-gray-200">{{
                                            selectedFile.mime_type }}</p>
                                    </div>
                                    <div class="col-span-2">
                                        <label
                                            class="block text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1">Folder</label>
                                        <select :value="selectedFile.folder_id || ''"
                                            @change="moveFile(selectedFile, ($event.target as HTMLSelectElement).value)"
                                            class="bg-gray-50 dark:bg-gray-900 border border-gray-300 dark:border-gray-600 text-gray-900 dark:text-white text-sm rounded-lg focus:ring-indigo-500 focus:border-indigo-500 block w-full p-2.5">
                                            <option value="">(Root)</option>
                                            <option v-for="folder in folders" :key="folder.id" :value="folder.id">
                                                {{ folder.name }}
                                            </option>
                                        </select>
                                    </div>
                                    <div class="col-span-2">
                                        <label
                                            class="block text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1">Uploaded</label>
                                        <p class="text-sm font-medium text-gray-900 dark:text-gray-200">{{ new
                                            Date(selectedFile.created_at).toLocaleDateString() }} {{ new
                                                Date(selectedFile.created_at).toLocaleTimeString() }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="mt-auto flex justify-end gap-3 pt-6 border-t border-gray-100 dark:border-gray-700">
                            <button @click="selectedFile = null"
                                class="px-4 py-2 bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-200 border border-gray-300 dark:border-gray-600 rounded-md hover:bg-gray-50 dark:hover:bg-gray-600 font-medium transition-colors shadow-sm">
                                Close
                            </button>
                            <button v-if="can('delete-media')" @click="deleteFile(selectedFile.id)"
                                class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700 font-medium transition-colors shadow-sm focus:ring-2 focus:ring-red-500 focus:ring-offset-2">
                                Delete File
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Delete Confirmation Modal -->
                <ConfirmModal :show="showDeleteConfirm" title="Delete File"
                    content="Are you sure you want to delete this file? This action cannot be undone."
                    confirm-text="Delete File" @close="showDeleteConfirm = false" @confirm="confirmDelete" />

                <!-- Delete Folder Confirmation Modal -->
                <ConfirmModal :show="showDeleteFolderConfirm" title="Delete Folder"
                    content="Are you sure you want to delete this folder? All files inside it will be deleted! This action cannot be undone."
                    confirm-text="Delete Folder" @close="showDeleteFolderConfirm = false"
                    @confirm="confirmDeleteFolder" />
            </div>
        </div>
    </AdminLayout>
</template>
