<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import CategoryForm from '@/components/CategoryForm.vue';
import type { CategoryFormData } from '@/types';

const form = useForm<CategoryFormData>({ name: '' });
</script>

<template>
    <Head title="New category" />

    <div class="flex flex-col gap-6">
        <h1 class="text-2xl font-semibold">New category</h1>
        <CategoryForm :form="form" submit-label="Create category" @submit="form.post('/categories')" />
    </div>
</template>
