<script setup lang="ts">
import { useToastStore } from '@/stores/toasts';

const toasts = useToastStore();
</script>

<template>
    <div class="pointer-events-none fixed inset-x-4 bottom-4 z-50 flex flex-col items-end gap-2" aria-live="polite">
        <TransitionGroup
            enter-from-class="translate-y-2 opacity-0"
            enter-active-class="transition duration-200"
            leave-to-class="opacity-0"
            leave-active-class="transition duration-150"
        >
            <div
                v-for="toast in toasts.items"
                :key="toast.id"
                class="pointer-events-auto flex w-full max-w-sm items-start gap-3 rounded-lg bg-white p-4 text-sm shadow-lg ring-1"
                :class="toast.type === 'success' ? 'ring-emerald-200' : 'ring-red-200'"
                role="status"
            >
                <span
                    class="mt-1 size-2 shrink-0 rounded-full"
                    :class="toast.type === 'success' ? 'bg-emerald-500' : 'bg-red-500'"
                />
                <p class="grow text-slate-700">{{ toast.message }}</p>
                <button type="button" class="text-slate-400 hover:text-slate-600" aria-label="Dismiss" @click="toasts.dismiss(toast.id)">
                    &times;
                </button>
            </div>
        </TransitionGroup>
    </div>
</template>
