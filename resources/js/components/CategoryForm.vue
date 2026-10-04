<script setup lang="ts">
import type { InertiaForm } from '@inertiajs/vue3';
import { Link } from '@inertiajs/vue3';
import FormField from '@/components/FormField.vue';
import type { CategoryFormData } from '@/types';

defineProps<{
    form: InertiaForm<CategoryFormData>;
    submitLabel: string;
}>();

defineEmits<{ submit: [] }>();
</script>

<template>
    <form class="flex max-w-xl flex-col gap-5 rounded-xl bg-white p-6 shadow-sm ring-1 ring-slate-200" @submit.prevent="$emit('submit')">
        <FormField label="Name" for="name" :error="form.errors.name">
            <input id="name" v-model="form.name" type="text" class="field" required maxlength="255" autofocus />
        </FormField>

        <div class="flex gap-3">
            <button type="submit" class="btn-primary" :disabled="form.processing">{{ submitLabel }}</button>
            <Link href="/categories" class="btn-secondary">Cancel</Link>
        </div>
    </form>
</template>
