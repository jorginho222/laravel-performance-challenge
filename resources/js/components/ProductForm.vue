<script setup lang="ts">
import type { InertiaForm } from '@inertiajs/vue3';
import { Link } from '@inertiajs/vue3';
import FormField from '@/components/FormField.vue';
import type { CategoryOption, ProductFormData } from '@/types';

defineProps<{
    form: InertiaForm<ProductFormData>;
    categories: CategoryOption[];
    submitLabel: string;
}>();

defineEmits<{ submit: [] }>();
</script>

<template>
    <form class="flex max-w-xl flex-col gap-5 rounded-xl bg-white p-6 shadow-sm ring-1 ring-slate-200" @submit.prevent="$emit('submit')">
        <FormField label="Name" for="name" :error="form.errors.name">
            <input id="name" v-model="form.name" type="text" class="field" required maxlength="255" />
        </FormField>

        <FormField label="Category" for="category_id" :error="form.errors.category_id">
            <select id="category_id" v-model="form.category_id" class="field" required>
                <option value="" disabled>Choose a category</option>
                <option v-for="category in categories" :key="category.id" :value="category.id">{{ category.name }}</option>
            </select>
        </FormField>

        <div class="grid gap-5 sm:grid-cols-3">
            <FormField label="Price" for="price" :error="form.errors.price">
                <input id="price" v-model="form.price" type="number" class="field" min="0" step="0.01" required />
            </FormField>

            <FormField label="Stock" for="stock" :error="form.errors.stock">
                <input id="stock" v-model.number="form.stock" type="number" class="field" min="0" step="1" required />
            </FormField>

            <FormField label="Status" for="status" :error="form.errors.status">
                <select id="status" v-model="form.status" class="field">
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                </select>
            </FormField>
        </div>

        <div class="flex gap-3">
            <button type="submit" class="btn-primary" :disabled="form.processing">{{ submitLabel }}</button>
            <Link href="/products" class="btn-secondary">Cancel</Link>
        </div>
    </form>
</template>
