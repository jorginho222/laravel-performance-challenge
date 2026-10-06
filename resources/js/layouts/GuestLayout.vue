<script setup lang="ts">
import ToastList from '@/components/ToastList.vue';
import { useCartStore } from '@/stores/cart';

// Nobody is signed in: forget the previous user's cart (it stays in their storage key).
useCartStore().restore(null);
</script>

<template>
    <div class="flex min-h-screen flex-col items-center justify-center gap-6 px-4 py-12">
        <p class="text-2xl font-semibold text-indigo-600">Shop App</p>
        <div class="w-full max-w-sm rounded-xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
            <slot />
        </div>
        <ToastList />
    </div>
</template>
