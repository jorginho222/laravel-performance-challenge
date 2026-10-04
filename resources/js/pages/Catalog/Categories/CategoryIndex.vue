<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import Pagination from '@/components/Pagination.vue';
import type { Category, ResourceCollection } from '@/types';

defineProps<{ categories: ResourceCollection<Category> }>();

const can = usePage().props.auth.can;

function destroy(category: Category): void {
    if (confirm(`Delete ${category.name}?`)) {
        router.delete(`/categories/${category.id}`, { preserveScroll: true });
    }
}
</script>

<template>
    <Head title="Categories" />

    <div class="flex flex-col gap-6">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <h1 class="text-2xl font-semibold">Categories</h1>
            <Link v-if="can.manageCatalog" href="/categories/create" class="btn-primary">New category</Link>
        </div>

        <div class="overflow-x-auto rounded-xl bg-white shadow-sm ring-1 ring-slate-200">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50 text-left text-xs font-medium tracking-wide text-slate-500 uppercase">
                    <tr>
                        <th class="px-4 py-3">Name</th>
                        <th class="px-4 py-3"><span class="sr-only">Actions</span></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <tr v-for="category in categories.data" :key="category.id">
                        <td class="px-4 py-3 font-medium">{{ category.name }}</td>
                        <td class="px-4 py-3">
                            <div class="flex justify-end gap-2">
                                <Link :href="`/products?category_id=${category.id}`" class="btn-secondary">View products</Link>
                                <template v-if="can.manageCatalog">
                                    <Link :href="`/categories/${category.id}/edit`" class="btn-secondary">Edit</Link>
                                    <button type="button" class="btn-danger" @click="destroy(category)">Delete</button>
                                </template>
                            </div>
                        </td>
                    </tr>
                    <tr v-if="categories.data.length === 0">
                        <td colspan="2" class="px-4 py-12 text-center text-slate-500">No categories yet.</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <Pagination :links="categories.links" :meta="categories.meta" />
    </div>
</template>
