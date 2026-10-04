<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import ProductForm from '@/components/ProductForm.vue';
import type { CategoryOption, Product, ProductFormData } from '@/types';

const props = defineProps<{
    product: Product;
    categories: CategoryOption[];
}>();

const form = useForm<ProductFormData>({
    name: props.product.name,
    category_id: props.product.category_id,
    price: props.product.price,
    stock: props.product.stock,
    status: props.product.status,
});
</script>

<template>
    <Head :title="`Edit ${product.name}`" />

    <div class="flex flex-col gap-6">
        <h1 class="text-2xl font-semibold">Edit {{ product.name }}</h1>
        <ProductForm :form="form" :categories="categories" submit-label="Save changes" @submit="form.put(`/products/${product.id}`)" />
    </div>
</template>
