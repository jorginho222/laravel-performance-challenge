<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import type { ResourceCollection } from '@/types';

defineProps<{
    links: ResourceCollection<unknown>['links'];
    meta: ResourceCollection<unknown>['meta'];
}>();
</script>

<template>
    <nav v-if="links.prev || links.next" class="flex items-center justify-between gap-4 text-sm" aria-label="Pagination">
        <Link v-if="links.prev" :href="links.prev" class="btn-secondary" preserve-scroll>Previous</Link>
        <span v-else class="btn-secondary opacity-50">Previous</span>

        <!-- Cursor pagination doesn't know the total, so the position is only shown for page numbers. -->
        <span v-if="meta.current_page && meta.last_page" class="text-slate-500">
            Page {{ meta.current_page }} of {{ meta.last_page }}
        </span>

        <Link v-if="links.next" :href="links.next" class="btn-secondary" preserve-scroll>Next</Link>
        <span v-else class="btn-secondary opacity-50">Next</span>
    </nav>
</template>
