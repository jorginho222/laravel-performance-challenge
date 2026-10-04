<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { computed, watch } from 'vue';
import ToastList from '@/components/ToastList.vue';
import { useCartStore } from '@/stores/cart';

const page = usePage();
const auth = computed(() => page.props.auth);
const cart = useCartStore();

watch(() => auth.value.user?.id ?? null, (userId) => cart.restore(userId), { immediate: true });

// Categories are only managed by admins; customers filter products by category instead.
const links = computed(() => [
    { href: '/products', label: 'Products' },
    ...(auth.value.can.manageCatalog ? [{ href: '/categories', label: 'Categories' }] : []),
]);

const isActive = (href: string) => page.url.startsWith(href);
</script>

<template>
    <div class="min-h-screen">
        <header class="border-b border-slate-200 bg-white">
            <nav class="mx-auto flex max-w-6xl flex-wrap items-center gap-x-6 gap-y-3 px-4 py-3">
                <Link href="/products" class="text-lg font-semibold text-indigo-600">Shop</Link>

                <div class="flex gap-1">
                    <Link
                        v-for="link in links"
                        :key="link.href"
                        :href="link.href"
                        class="rounded-md px-3 py-2 text-sm font-medium"
                        :class="isActive(link.href) ? 'bg-indigo-50 text-indigo-700' : 'text-slate-600 hover:text-slate-900'"
                    >
                        {{ link.label }}
                    </Link>
                </div>

                <div class="ml-auto flex items-center gap-4">
                    <Link
                        v-if="auth.can.placeOrders"
                        href="/cart"
                        class="inline-flex items-center gap-2 rounded-md px-3 py-2 text-sm font-medium"
                        :class="isActive('/cart') ? 'bg-indigo-50 text-indigo-700' : 'text-slate-600 hover:text-slate-900'"
                    >
                        Cart
                        <span class="rounded-full bg-indigo-600 px-2 py-0.5 text-xs text-white">{{ cart.count }}</span>
                    </Link>

                    <span v-if="auth.user" class="flex items-center gap-2 text-sm text-slate-600">
                        {{ auth.user.name }}
                        <span class="rounded-full bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-600">{{ auth.user.role }}</span>
                    </span>

                    <Link href="/logout" method="post" as="button" class="text-sm font-medium text-slate-600 hover:text-slate-900">
                        Log out
                    </Link>
                </div>
            </nav>
        </header>

        <main class="mx-auto max-w-6xl px-4 py-8">
            <slot />
        </main>

        <ToastList />
    </div>
</template>
