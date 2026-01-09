<script setup lang="ts">
import { computed } from 'vue';

const props = defineProps<{
    modelValue?: string | number;
    type?: string;
    placeholder?: string;
    required?: boolean;
    disabled?: boolean;
    error?: string;
    label?: string;
    id?: string;
}>();

const emit = defineEmits<{
    'update:modelValue': [value: string | number];
}>();

const inputId = computed(() => props.id || `input-${Math.random().toString(36).substr(2, 9)}`);

const handleInput = (event: Event) => {
    const target = event.target as HTMLInputElement;
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
            <input :id="inputId" :type="type || 'text'" :value="modelValue" :placeholder="placeholder"
                :required="required" :disabled="disabled" @input="handleInput"
                class="w-full px-4 py-2.5 text-sm text-gray-900 bg-white border rounded-lg transition-all duration-200 placeholder:text-gray-400 focus:outline-none focus:ring-2 disabled:opacity-50 disabled:cursor-not-allowed"
                :class="[
                    error
                        ? 'border-red-300 focus:border-red-500 focus:ring-red-500/20'
                        : 'border-gray-300 focus:border-indigo-500 focus:ring-indigo-500/20 hover:border-gray-400'
                ]" />
        </div>
        <p v-if="error" class="mt-1.5 text-sm text-red-600">{{ error }}</p>
    </div>
</template>
