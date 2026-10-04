<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import Pagination from '@/components/Pagination.vue';
import StatusBadge from '@/components/StatusBadge.vue';
import { debounce } from '@/lib/debounce';
import { useCartStore } from '@/stores/cart';
import { useToastStore } from '@/stores/toasts';
import type { CategoryOption, Product, ProductFilters, ProductStatus, ResourceCollection } from '@/types';

const props = defineProps<{
    products: ResourceCollection<Product>;
    filters: ProductFilters;
    categories: CategoryOption[];
}>();

const can = usePage().props.auth.can;
const cart = useCartStore();
const toasts = useToastStore();

const search = ref(props.filters.search ?? '');
const categoryId = ref(props.filters.category_id ?? '');
const status = ref<ProductStatus | ''>(props.filters.status ?? '');

// Empty filters are left out: the backend rejects an empty search.
function applyFilters(): void {
    const query: ProductFilters = {};
    if (search.value.trim()) query.search = search.value.trim();
    if (categoryId.value) query.category_id = categoryId.value;
    if (status.value) query.status = status.value;

    router.get('/products', { ...query }, {
        only: ['products', 'filters'],
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
}

watch(search, debounce(applyFilters, 300));
watch([categoryId, status], applyFilters);

const canAddToCart = (product: Product) => product.status === 'active' && cart.quantityOf(product.id) < product.stock;

function addToCart(product: Product): void {
    cart.add(product);
    toasts.push({ type: 'success', message: `Added ${product.name} to the cart.` });
}

function destroy(product: Product): void {
    if (confirm(`Delete ${product.name}?`)) {
        router.delete(`/products/${product.id}`, { preserveScroll: true });
    }
}
</script>

<template>
    <Head title="Products" />

    <div class="flex flex-col gap-6">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <h1 class="text-2xl font-semibold">Products</h1>
            <Link v-if="can.manageCatalog" href="/products/create" class="btn-primary">New product</Link>
        </div>

        <!-- Only admins see inactive products, so only they can filter by status. -->
        <div class="grid gap-3" :class="can.manageCatalog ? 'sm:grid-cols-[1fr_16rem_10rem]' : 'sm:grid-cols-[1fr_16rem]'">
            <input v-model="search" type="search" class="field" placeholder="Search products…" aria-label="Search products" />
            <select v-model="categoryId" class="field" aria-label="Category">
                <option value="">All categories</option>
                <option v-for="category in categories" :key="category.id" :value="category.id">{{ category.name }}</option>
            </select>
            <select v-if="can.manageCatalog" v-model="status" class="field" aria-label="Status">
                <option value="">Any status</option>
                <option value="active">Active</option>
                <option value="inactive">Inactive</option>
            </select>
        </div>

        <p v-if="filters.search" class="text-sm text-slate-500">Sorted by relevance to “{{ filters.search }}”.</p>

        <div class="overflow-x-auto rounded-xl bg-white shadow-sm ring-1 ring-slate-200">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50 text-left text-xs font-medium tracking-wide text-slate-500 uppercase">
                    <tr>
                        <th class="px-4 py-3">Name</th>
                        <th class="px-4 py-3">Category</th>
                        <th class="px-4 py-3 text-right">Price</th>
                        <th class="px-4 py-3 text-right">Stock</th>
                        <th v-if="can.manageCatalog" class="px-4 py-3">Status</th>
                        <th class="px-4 py-3"><span class="sr-only">Actions</span></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <tr v-for="product in products.data" :key="product.id">
                        <td class="px-4 py-3 font-medium">{{ product.name }}</td>
                        <td class="px-4 py-3 text-slate-600">{{ product.category?.name }}</td>
                        <td class="px-4 py-3 text-right tabular-nums">{{ product.price }}</td>
                        <td class="px-4 py-3 text-right tabular-nums">{{ product.stock }}</td>
                        <td v-if="can.manageCatalog" class="px-4 py-3"><StatusBadge :status="product.status" /></td>
                        <td class="px-4 py-3">
                            <div class="flex justify-end gap-2">
                                <template v-if="can.manageCatalog">
                                    <Link :href="`/products/${product.id}/edit`" class="btn-secondary">Edit</Link>
                                    <button type="button" class="btn-danger" @click="destroy(product)">Delete</button>
                                </template>
                                <button
                                    v-if="can.placeOrders"
                                    type="button"
                                    class="btn-primary"
                                    :disabled="!canAddToCart(product)"
                                    @click="addToCart(product)"
                                >
                                    Add to cart
                                    <span v-if="cart.quantityOf(product.id)" class="text-xs opacity-80">({{ cart.quantityOf(product.id) }})</span>
                                </button>
                            </div>
                        </td>
                    </tr>
                    <tr v-if="products.data.length === 0">
                        <td :colspan="can.manageCatalog ? 6 : 5" class="px-4 py-12 text-center text-slate-500">No products match these filters.</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <Pagination :links="products.links" :meta="products.meta" />
    </div>
</template>
