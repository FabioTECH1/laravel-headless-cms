<script setup lang="ts">
import { useEditor, EditorContent } from '@tiptap/vue-3'
import StarterKit from '@tiptap/starter-kit'
import { watch, onBeforeUnmount } from 'vue'

const props = defineProps<{
    modelValue: string
}>()

const emit = defineEmits<{
    (e: 'update:modelValue', value: string): void
}>()

const editor = useEditor({
    extensions: [
        StarterKit,
    ],
    content: props.modelValue,
    editorProps: {
        attributes: {
            class: 'prose prose-sm dark:prose-invert max-w-none focus:outline-none min-h-[150px] p-4 text-gray-900 dark:text-gray-100',
        },
    },
    onUpdate: () => {
        emit('update:modelValue', editor.value?.getHTML() || '')
    },
})

// Watch modelValue for external changes (e.g. initial load)
watch(() => props.modelValue, (newValue) => {
    // Only update if content is different to avoid cursor jumping
    const isSame = editor.value?.getHTML() === newValue
    if (!isSame) {
        editor.value?.commands.setContent(newValue, { emitUpdate: false })
    }
})

onBeforeUnmount(() => {
    editor.value?.destroy()
})
</script>

<template>
    <div
        class="border border-gray-300 dark:border-gray-600 rounded-md shadow-sm bg-white dark:bg-gray-800 overflow-hidden">
        <!-- Toolbar -->
        <div v-if="editor"
            class="bg-gray-50 dark:bg-gray-700 border-b border-gray-200 dark:border-gray-600 px-3 py-2 flex flex-wrap gap-1">
            <button type="button" @click="editor.chain().focus().toggleBold().run()"
                :class="{ 'bg-gray-300 dark:bg-gray-600 text-gray-900 dark:text-white': editor.isActive('bold'), 'text-gray-600 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600': !editor.isActive('bold') }"
                class="p-1.5 rounded transition-colors text-xs font-medium" title="Bold">
                B
            </button>
            <button type="button" @click="editor.chain().focus().toggleItalic().run()"
                :class="{ 'bg-gray-300 dark:bg-gray-600 text-gray-900 dark:text-white': editor.isActive('italic'), 'text-gray-600 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600': !editor.isActive('italic') }"
                class="p-1.5 rounded transition-colors text-xs font-medium italic" title="Italic">
                I
            </button>

            <div class="w-px h-5 bg-gray-300 dark:bg-gray-600 mx-1 self-center"></div>

            <button type="button" @click="editor.chain().focus().toggleHeading({ level: 2 }).run()"
                :class="{ 'bg-gray-300 dark:bg-gray-600 text-gray-900 dark:text-white': editor.isActive('heading', { level: 2 }), 'text-gray-600 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600': !editor.isActive('heading', { level: 2 }) }"
                class="p-1.5 rounded transition-colors text-xs font-medium" title="Heading 2">
                H2
            </button>
            <button type="button" @click="editor.chain().focus().toggleHeading({ level: 3 }).run()"
                :class="{ 'bg-gray-300 dark:bg-gray-600 text-gray-900 dark:text-white': editor.isActive('heading', { level: 3 }), 'text-gray-600 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600': !editor.isActive('heading', { level: 3 }) }"
                class="p-1.5 rounded transition-colors text-xs font-medium" title="Heading 3">
                H3
            </button>

            <div class="w-px h-5 bg-gray-300 dark:bg-gray-600 mx-1 self-center"></div>

            <button type="button" @click="editor.chain().focus().toggleBulletList().run()"
                :class="{ 'bg-gray-300 dark:bg-gray-600 text-gray-900 dark:text-white': editor.isActive('bulletList'), 'text-gray-600 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600': !editor.isActive('bulletList') }"
                class="p-1.5 rounded transition-colors text-xs font-medium" title="Bullet List">
                Create List
            </button>
            <button type="button" @click="editor.chain().focus().toggleOrderedList().run()"
                :class="{ 'bg-gray-300 dark:bg-gray-600 text-gray-900 dark:text-white': editor.isActive('orderedList'), 'text-gray-600 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600': !editor.isActive('orderedList') }"
                class="p-1.5 rounded transition-colors text-xs font-medium" title="Ordered List">
                1. List
            </button>
            <button type="button" @click="editor.chain().focus().toggleBlockquote().run()"
                :class="{ 'bg-gray-300 dark:bg-gray-600 text-gray-900 dark:text-white': editor.isActive('blockquote'), 'text-gray-600 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600': !editor.isActive('blockquote') }"
                class="p-1.5 rounded transition-colors text-xs font-medium" title="Blockquote">
                ""
            </button>
        </div>

        <!-- Editor Content -->
        <editor-content :editor="editor" />
    </div>
</template>
