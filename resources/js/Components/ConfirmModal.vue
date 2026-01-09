<script setup lang="ts">
import Modal from '@/Components/Modal.vue';

const props = defineProps<{
    show: boolean;
    title: string;
    content: string;
    confirmText?: string;
    cancelText?: string;
    confirmType?: 'danger' | 'primary' | 'warning';
}>();

const emit = defineEmits(['close', 'confirm']);

const close = () => {
    emit('close');
};

const confirm = () => {
    emit('confirm');
    close();
};

const confirmClasses = {
    danger: 'bg-red-600 hover:bg-red-700 focus:ring-red-500',
    primary: 'bg-indigo-600 hover:bg-indigo-700 focus:ring-indigo-500',
    warning: 'bg-yellow-600 hover:bg-yellow-700 focus:ring-yellow-500',
}[props.confirmType || 'danger'];
</script>

<template>
    <Modal :show="show" max-width="md" :closeable="true" @close="close">
        <div class="px-6 py-4">
            <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                {{ title }}
            </h3>
            <div class="mt-4 text-sm text-gray-600 dark:text-gray-400">
                {{ content }}
            </div>
        </div>

        <div class="px-6 py-4 bg-gray-100 dark:bg-gray-700/50 text-right rounded-b-lg flex flex-row-reverse">
            <button type="button" @click="confirm"
                class="inline-flex w-full justify-center rounded-md border border-transparent px-4 py-2 text-base font-medium text-white shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-2 sm:ml-3 sm:w-auto sm:text-sm transition-colors"
                :class="confirmClasses">
                {{ confirmText || 'Confirm' }}
            </button>
            <button type="button" @click="close"
                class="mt-3 inline-flex w-full justify-center rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-4 py-2 text-base font-medium text-gray-700 dark:text-gray-300 shadow-sm hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm transition-colors">
                {{ cancelText || 'Cancel' }}
            </button>
        </div>
    </Modal>
</template>
