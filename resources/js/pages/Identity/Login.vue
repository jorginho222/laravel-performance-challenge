<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import FormField from '@/components/FormField.vue';

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const submit = () => form.post('/login', { onFinish: () => form.reset('password') });
</script>

<template>
    <Head title="Log in" />

    <form class="flex flex-col gap-4" @submit.prevent="submit">
        <h1 class="text-lg font-semibold">Log in</h1>

        <FormField label="Email" for="email" :error="form.errors.email">
            <input id="email" v-model="form.email" type="email" class="field" autocomplete="username" required autofocus />
        </FormField>

        <FormField label="Password" for="password" :error="form.errors.password">
            <input id="password" v-model="form.password" type="password" class="field" autocomplete="current-password" required />
        </FormField>

        <label class="flex items-center gap-2 text-sm text-slate-600">
            <input v-model="form.remember" type="checkbox" class="rounded border-slate-300" />
            Remember me
        </label>

        <button type="submit" class="btn-primary" :disabled="form.processing">Log in</button>

        <p class="text-center text-sm text-slate-600">
            No account yet?
            <Link href="/register" class="font-medium text-indigo-600 hover:text-indigo-500">Register</Link>
        </p>
    </form>
</template>
