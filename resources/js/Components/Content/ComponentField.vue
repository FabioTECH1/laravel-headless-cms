<script setup lang="ts">
import { computed } from 'vue';
defineOptions({ name: 'ComponentField' });
import Input from '@/Components/Input.vue';
import Select from '@/Components/Select.vue';
import RichEditor from '@/Components/RichEditor.vue';

const props = defineProps<{
    modelValue: Record<string, any>;
    componentId: number;
    components: Array<{
        id: number;
        name: string;
        slug: string;
        fields: Array<any>;
    }>;
}>();

const emit = defineEmits(['update:modelValue']);

const componentSchema = computed(() => {
    return props.components.find(c => c.id === props.componentId);
});

const updateField = (key: string, value: any) => {
    emit('update:modelValue', {
        ...props.modelValue,
        [key]: value
    });
};
</script>

<template>
    <div v-if="componentSchema"
        class="bg-gray-50 dark:bg-gray-700/50 p-4 rounded-lg border border-gray-200 dark:border-gray-700 space-y-4">
        <h4 class="text-xs font-semibold uppercase text-gray-500 dark:text-gray-400 mb-2">{{ componentSchema.name }}
        </h4>

        <div v-for="field in componentSchema.fields" :key="field.name">
            <!-- Text Input -->
            <Input v-if="field.type === 'text'" :id="`${componentSchema.slug}_${field.name}`"
                :model-value="modelValue[field.name]" @update:model-value="(val) => updateField(field.name, val)"
                :label="field.name.replace(/_/g, ' ')" />

            <!-- Integer Input -->
            <Input v-if="field.type === 'integer'" :id="`${componentSchema.slug}_${field.name}`"
                :model-value="modelValue[field.name]" @update:model-value="(val) => updateField(field.name, val)"
                type="number" :label="field.name.replace(/_/g, ' ')" />

            <!-- Long Text -->
            <div v-if="field.type === 'longtext'">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5 capitalize">
                    {{ field.name.replace(/_/g, ' ') }}
                </label>
                <RichEditor :model-value="modelValue[field.name]"
                    @update:model-value="(val) => updateField(field.name, val)" />
            </div>

            <!-- Checkbox -->
            <div v-if="field.type === 'boolean'" class="flex items-center">
                <input :id="`${componentSchema.slug}_${field.name}`" :checked="modelValue[field.name]"
                    @change="(e) => updateField(field.name, (e.target as HTMLInputElement).checked)" type="checkbox"
                    class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 dark:border-gray-600 dark:bg-gray-700 rounded" />
                <label :for="`${componentSchema.slug}_${field.name}`"
                    class="ml-2 block text-sm text-gray-900 dark:text-gray-300 capitalize">
                    {{ field.name.replace(/_/g, ' ') }}
                </label>
            </div>

            <!-- DateTime -->
            <Input v-if="field.type === 'datetime'" :id="`${componentSchema.slug}_${field.name}`"
                :model-value="modelValue[field.name]" @update:model-value="(val) => updateField(field.name, val)"
                type="datetime-local" :label="field.name.replace(/_/g, ' ')" />

            <!-- Enum -->
            <Select v-if="field.type === 'enum'" :id="`${componentSchema.slug}_${field.name}`"
                :model-value="modelValue[field.name]" @update:model-value="(val) => updateField(field.name, val)"
                :label="field.name.replace(/_/g, ' ')"
                :options="(field.settings?.options || []).map((opt: string) => ({ value: opt, label: opt }))" />

            <!-- Email Input -->
            <Input v-if="field.type === 'email'" :id="`${componentSchema.slug}_${field.name}`"
                :model-value="modelValue[field.name]" @update:model-value="(val) => updateField(field.name, val)"
                type="email" :label="field.name.replace(/_/g, ' ')" />

            <!-- Recursive Component -->
            <div v-if="field.type === 'component'">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5 capitalize">
                    {{ field.name.replace(/_/g, ' ') }}
                </label>
                <div v-if="modelValue[field.name]" class="pl-4 border-l-2 border-indigo-200 dark:border-indigo-900">
                    <ComponentField :model-value="modelValue[field.name] || {}"
                        @update:model-value="(val) => updateField(field.name, val)"
                        :component-id="field.settings.related_content_type_id" :components="components" />
                </div>
                <!-- Initialize button if null -->
                <button v-else type="button" @click="updateField(field.name, {})"
                    class="text-sm text-indigo-600 hover:underline">
                    + Add Component
                </button>
            </div>
        </div>
    </div>
    <div v-else class="text-red-500 text-xs">
        Component schema not found (ID: {{ componentId }})
    </div>
</template>
