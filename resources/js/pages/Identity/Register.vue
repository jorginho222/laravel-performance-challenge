<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import FormField from '@/components/FormField.vue';

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
});

const submit = () => form.post('/register', { onFinish: () => form.reset('password', 'password_confirmation') });
</script>

<template>
    <Head title="Register" />

    <form class="flex flex-col gap-4" @submit.prevent="submit">
        <h1 class="text-lg font-semibold">Create an account</h1>

        <FormField label="Name" for="name" :error="form.errors.name">
            <input id="name" v-model="form.name" type="text" class="field" autocomplete="name" required autofocus />
        </FormField>

        <FormField label="Email" for="email" :error="form.errors.email">
            <input id="email" v-model="form.email" type="email" class="field" autocomplete="username" required />
        </FormField>

        <FormField label="Password" for="password" :error="form.errors.password">
            <input id="password" v-model="form.password" type="password" class="field" autocomplete="new-password" required />
        </FormField>

        <FormField label="Confirm password" for="password_confirmation">
            <input
                id="password_confirmation"
                v-model="form.password_confirmation"
                type="password"
                class="field"
                autocomplete="new-password"
                required
            />
        </FormField>

        <button type="submit" class="btn-primary" :disabled="form.processing">Register</button>

        <p class="text-center text-sm text-slate-600">
            Already registered?
            <Link href="/login" class="font-medium text-indigo-600 hover:text-indigo-500">Log in</Link>
        </p>
    </form>
</template>
