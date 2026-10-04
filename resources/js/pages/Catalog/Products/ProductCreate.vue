<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import ProductForm from '@/components/ProductForm.vue';
import type { CategoryOption, ProductFormData } from '@/types';

defineProps<{ categories: CategoryOption[] }>();

const form = useForm<ProductFormData>({
    name: '',
    category_id: '',
    price: '',
    stock: 0,
    status: 'active',
});
</script>

<template>
    <Head title="New product" />

    <div class="flex flex-col gap-6">
        <h1 class="text-2xl font-semibold">New product</h1>
        <ProductForm :form="form" :categories="categories" submit-label="Create product" @submit="form.post('/products')" />
    </div>
</template>
