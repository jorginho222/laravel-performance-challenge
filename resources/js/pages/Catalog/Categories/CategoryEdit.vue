<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import CategoryForm from '@/components/CategoryForm.vue';
import type { Category, CategoryFormData } from '@/types';

const props = defineProps<{ category: Category }>();

const form = useForm<CategoryFormData>({ name: props.category.name });
</script>

<template>
    <Head :title="`Edit ${category.name}`" />

    <div class="flex flex-col gap-6">
        <h1 class="text-2xl font-semibold">Edit {{ category.name }}</h1>
        <CategoryForm :form="form" submit-label="Save changes" @submit="form.put(`/categories/${category.id}`)" />
    </div>
</template>
