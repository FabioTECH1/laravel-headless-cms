import { ref } from 'vue';

export interface Toast {
    id: number;
    message: string;
    type: 'success' | 'error' | 'info' | 'warning';
    duration?: number;
}

const toasts = ref<Toast[]>([]);
let nextId = 0;

export function useToast() {
    const add = (message: string, type: Toast['type'] = 'info', duration = 3000) => {
        const id = nextId++;
        const toast: Toast = { id, message, type, duration };
        toasts.value.push(toast);

        if (duration > 0) {
            setTimeout(() => {
                remove(id);
            }, duration);
        }
    };

    const remove = (id: number) => {
        const index = toasts.value.findIndex((t) => t.id === id);
        if (index !== -1) {
            toasts.value.splice(index, 1);
        }
    };

    const success = (message: string, duration?: number) => add(message, 'success', duration);
    const error = (message: string, duration?: number) => add(message, 'error', duration);
    const info = (message: string, duration?: number) => add(message, 'info', duration);
    const warning = (message: string, duration?: number) => add(message, 'warning', duration);

    return {
        toasts,
        add,
        remove,
        success,
        error,
        info,
        warning,
    };
}
