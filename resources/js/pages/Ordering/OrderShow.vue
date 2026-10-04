<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import { formatCents, toCents } from '@/lib/money';
import type { Order, OrderLine } from '@/types';

const props = defineProps<{ order: Order }>();

// Only the customer who placed an order can see it, so the confirmation goes to the signed-in user.
const email = usePage().props.auth.user?.email;

const placedAt = new Date(props.order.created_at).toLocaleString();
const subtotal = (line: OrderLine) => formatCents(toCents(line.price) * line.quantity);
</script>

<template>
    <Head :title="`Order #${order.number}`" />

    <div class="flex flex-col gap-6">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <p class="text-sm font-medium text-emerald-600">Order placed</p>
                <h1 class="text-2xl font-semibold">Order #{{ order.number }}</h1>
                <p class="text-sm text-slate-500">{{ placedAt }}</p>
            </div>
            <Link href="/products" class="btn-primary">Back to products</Link>
        </div>

        <div class="flex items-start gap-3 rounded-xl bg-emerald-50 p-4 text-sm text-emerald-800 ring-1 ring-emerald-200">
            <span class="mt-1 size-2 shrink-0 rounded-full bg-emerald-500" />
            <p>
                A confirmation email for this order has been sent to <span class="font-medium">{{ email }}</span>.
            </p>
        </div>

        <div class="overflow-x-auto rounded-xl bg-white shadow-sm ring-1 ring-slate-200">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50 text-left text-xs font-medium tracking-wide text-slate-500 uppercase">
                    <tr>
                        <th class="px-4 py-3">Product</th>
                        <th class="px-4 py-3 text-right">Price</th>
                        <th class="px-4 py-3 text-right">Quantity</th>
                        <th class="px-4 py-3 text-right">Subtotal</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <tr v-for="line in order.products" :key="line.product_id">
                        <td class="px-4 py-3 font-medium">{{ line.name }}</td>
                        <td class="px-4 py-3 text-right tabular-nums">{{ line.price }}</td>
                        <td class="px-4 py-3 text-right tabular-nums">{{ line.quantity }}</td>
                        <td class="px-4 py-3 text-right tabular-nums">{{ subtotal(line) }}</td>
                    </tr>
                </tbody>
                <tfoot>
                    <tr class="border-t border-slate-200">
                        <th colspan="3" class="px-4 py-3 text-right font-medium">Total</th>
                        <td class="px-4 py-3 text-right text-base font-semibold tabular-nums">{{ order.total }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</template>
