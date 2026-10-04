import { defineStore } from 'pinia';
import { ref } from 'vue';
import type { Toast } from '@/types';

export interface ToastItem extends Toast {
    id: number;
}

const DISMISS_AFTER_MS = 4000;

/** Notifications from the server (Inertia flash data) and from client actions. */
export const useToastStore = defineStore('toasts', () => {
    const items = ref<ToastItem[]>([]);
    let nextId = 1;

    function push(toast: Toast): void {
        const id = nextId++;
        items.value.push({ ...toast, id });
        setTimeout(() => dismiss(id), DISMISS_AFTER_MS);
    }

    function dismiss(id: number): void {
        items.value = items.value.filter((item) => item.id !== id);
    }

    return { items, push, dismiss };
});
