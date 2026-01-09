<script setup lang="ts">
import { computed } from 'vue';

const props = defineProps<{
    modelValue?: string | number;
    placeholder?: string;
    required?: boolean;
    disabled?: boolean;
    error?: string;
    label?: string;
    id?: string;
    options: Array<{ value: string | number; label: string }>;
}>();

const emit = defineEmits<{
    'update:modelValue': [value: string | number];
}>();

const inputId = computed(() => props.id || `select-${Math.random().toString(36).substr(2, 9)}`);

const handleChange = (event: Event) => {
    const target = event.target as HTMLSelectElement;
    emit('update:modelValue', target.value);
};
</script>

<template>
    <div class="w-full">
        <label v-if="label" :for="inputId" class="block text-sm font-medium text-gray-700 mb-1.5">
            {{ label }}
            <span v-if="required" class="text-red-500">*</span>
        </label>
        <div class="relative">
            <select :id="inputId" :value="modelValue" :required="required" :disabled="disabled" @change="handleChange"
                class="w-full px-4 py-2.5 text-sm text-gray-900 bg-white border rounded-lg transition-all duration-200 focus:outline-none focus:ring-2 disabled:opacity-50 disabled:cursor-not-allowed appearance-none cursor-pointer"
                :class="[
                    error
                        ? 'border-red-300 focus:border-red-500 focus:ring-red-500/20'
                        : 'border-gray-300 focus:border-indigo-500 focus:ring-indigo-500/20 hover:border-gray-400'
                ]">
                <option v-if="placeholder" value="">{{ placeholder }}</option>
                <option v-for="option in options" :key="option.value" :value="option.value">
                    {{ option.label }}
                </option>
            </select>
            <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-3 text-gray-500">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                </svg>
            </div>
        </div>
        <p v-if="error" class="mt-1.5 text-sm text-red-600">{{ error }}</p>
    </div>
</template>
