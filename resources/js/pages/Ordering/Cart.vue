<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { formatCents, toCents } from '@/lib/money';
import { useCartStore, type CartLine } from '@/stores/cart';
import { uuidv4 } from '@/lib/uuid';

const cart = useCartStore();

// One id per checkout: resubmitting the same order (e.g. a retry after a network error) is rejected
// by the server instead of placing it twice. Leaving the page after an order clears the cart.
const orderId = uuidv4();

const form = useForm({
    id: '',
    products: [] as { product_id: string; quantity: number }[],
});

const errors = () => form.errors as Record<string, string | undefined>;

const lineError = (line: CartLine, index: number) =>
    errors()[`products.${line.productId}`] ?? errors()[`products.${index}.product_id`] ?? errors()[`products.${index}.quantity`];

function placeOrder(): void {
    form.transform(() => ({
        id: orderId,
        products: cart.lines.map((line) => ({ product_id: line.productId, quantity: line.quantity })),
    })).post('/orders', {
        preserveScroll: true,
        onSuccess: () => cart.clear(),
    });
}
</script>

<template>
    <Head title="Cart" />

    <div class="flex flex-col gap-6">
        <h1 class="text-2xl font-semibold">Cart</h1>

        <div v-if="cart.lines.length === 0" class="rounded-xl bg-white p-12 text-center shadow-sm ring-1 ring-slate-200">
            <p class="text-slate-500">Your cart is empty.</p>
            <Link href="/products" class="btn-primary mt-4">Browse products</Link>
        </div>

        <template v-else>
            <p v-if="form.errors.id" class="rounded-md bg-red-50 p-3 text-sm text-red-700">{{ form.errors.id }}</p>
            <p v-if="form.errors.products" class="rounded-md bg-red-50 p-3 text-sm text-red-700">{{ form.errors.products }}</p>

            <ul class="divide-y divide-slate-100 rounded-xl bg-white shadow-sm ring-1 ring-slate-200">
                <li v-for="(line, index) in cart.lines" :key="line.productId" class="flex flex-wrap items-center gap-4 p-4">
                    <div class="min-w-48 grow">
                        <p class="font-medium">{{ line.name }}</p>
                        <p class="text-sm text-slate-500">{{ line.price }} each</p>
                        <p v-if="lineError(line, index)" class="mt-1 text-sm text-red-600">{{ lineError(line, index) }}</p>
                    </div>

                    <input
                        :value="line.quantity"
                        type="number"
                        class="field w-24"
                        min="1"
                        :max="line.stock"
                        :aria-label="`Quantity of ${line.name}`"
                        @change="cart.setQuantity(line.productId, Number(($event.target as HTMLInputElement).value))"
                    />

                    <p class="w-24 text-right font-medium tabular-nums">{{ formatCents(toCents(line.price) * line.quantity) }}</p>

                    <button type="button" class="btn-danger" @click="cart.remove(line.productId)">Remove</button>
                </li>
            </ul>

            <div class="flex flex-wrap items-center justify-end gap-6">
                <p class="text-lg">
                    Total <span class="font-semibold tabular-nums">{{ formatCents(cart.totalCents) }}</span>
                </p>
                <button type="button" class="btn-primary" :disabled="form.processing" @click="placeOrder">Place order</button>
            </div>
        </template>
    </div>
</template>
