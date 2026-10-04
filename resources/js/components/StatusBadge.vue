<script setup lang="ts">
import type { ProductStatus } from '@/types';

defineProps<{ status: ProductStatus }>();
</script>

<template>
    <span
        class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium"
        :class="status === 'active' ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-600'"
    >
        {{ status }}
    </span>
</template>
