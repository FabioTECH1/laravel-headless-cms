<script setup lang="ts">
import { ref, computed } from 'vue';
import ComponentField from './ComponentField.vue';

const props = defineProps<{
    modelValue: Array<Record<string, any>>;
    field: {
        name: string;
        settings?: { allowed_component_ids?: number[] };
    };
    components: Array<{
        id: number;
        name: string;
        slug: string;
        fields: Array<any>;
    }>;
}>();

const emit = defineEmits(['update:modelValue']);

const allowedComponents = computed(() => {
    const ids = props.field.settings?.allowed_component_ids || [];
    return props.components.filter(c => ids.includes(c.id));
});

const showPicker = ref(false);

const addBlock = (component: typeof allowedComponents.value[0]) => {
    const newBlock = { __component: component.slug }; // Slug is used for identification
    emit('update:modelValue', [...(props.modelValue || []), newBlock]);
    showPicker.value = false;
};

const updateBlock = (index: number, val: any) => {
    const newValue = [...(props.modelValue || [])];
    newValue[index] = val;
    emit('update:modelValue', newValue);
};

const removeBlock = (index: number) => {
    const newValue = [...(props.modelValue || [])];
    newValue.splice(index, 1);
    emit('update:modelValue', newValue);
};

const getComponentId = (slug: string) => {
    return props.components.find(c => c.slug === slug)?.id;
};

const moveBlock = (index: number, direction: 'up' | 'down') => {
    const newIndex = direction === 'up' ? index - 1 : index + 1;
    if (newIndex < 0 || newIndex >= props.modelValue.length) return;

    const newValue = [...(props.modelValue || [])];
    const item = newValue[index];
    newValue.splice(index, 1);
    newValue.splice(newIndex, 0, item);
    emit('update:modelValue', newValue);
};
</script>

<template>
    <div class="space-y-4">
        <!-- Render Blocks -->
        <div v-for="(block, index) in modelValue" :key="index" class="relative group">
            <div
                class="absolute right-0 top-0 mt-2 mr-2 flex gap-1 opacity-0 group-hover:opacity-100 transition-opacity z-10">
                <button type="button" @click="moveBlock(index, 'up')" :disabled="index === 0"
                    class="p-1 text-gray-400 hover:text-gray-600 bg-white rounded shadow-sm border border-gray-200">
                    ↑
                </button>
                <button type="button" @click="moveBlock(index, 'down')" :disabled="index === modelValue.length - 1"
                    class="p-1 text-gray-400 hover:text-gray-600 bg-white rounded shadow-sm border border-gray-200">
                    ↓
                </button>
                <button type="button" @click="removeBlock(index)"
                    class="p-1 text-red-400 hover:text-red-600 bg-white rounded shadow-sm border border-gray-200">
                    ×
                </button>
            </div>

            <ComponentField v-if="block.__component" :model-value="block"
                @update:model-value="(val) => updateBlock(index, val)"
                :component-id="getComponentId(block.__component)!" :components="components" />
            <div v-else class="text-red-500 text-sm p-4 border border-red-200 rounded">
                Invalid block data: missing __component key.
            </div>
        </div>

        <!-- Add Button / Picker -->
        <div class="relative">
            <div v-if="!showPicker">
                <button type="button" @click="showPicker = true"
                    class="w-full py-3 border-2 border-dashed border-gray-300 dark:border-gray-700 rounded-lg text-gray-500 hover:border-indigo-500 hover:text-indigo-500 transition-colors flex items-center justify-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                        stroke="currentColor" class="w-5 h-5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                    </svg>
                    Add Component to Zone
                </button>
            </div>

            <div v-if="showPicker"
                class="p-4 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg shadow-sm">
                <h4 class="text-sm font-medium text-gray-900 dark:text-gray-200 mb-3">Select Component</h4>
                <div class="grid grid-cols-2 sm:grid-cols-3 gap-2">
                    <button v-for="comp in allowedComponents" :key="comp.id" type="button" @click="addBlock(comp)"
                        class="px-3 py-2 text-sm text-left bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded hover:bg-indigo-50 dark:hover:bg-indigo-900/30 hover:border-indigo-200 transition-colors">
                        {{ comp.name }}
                    </button>
                </div>
                <button type="button" @click="showPicker = false"
                    class="mt-3 text-xs text-gray-500 hover:text-gray-700 underline">Cancel</button>
            </div>
        </div>
    </div>
</template>
