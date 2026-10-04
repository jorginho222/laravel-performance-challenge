<script setup lang="ts">
defineProps<{
    label: string;
    for: string;
    error?: string;
}>();
</script>

<template>
    <div class="flex flex-col gap-1.5">
        <label :for="$props.for" class="text-sm font-medium text-slate-700">{{ label }}</label>
        <slot />
        <p v-if="error" class="text-sm text-red-600">{{ error }}</p>
    </div>
</template>
