import { defineStore } from 'pinia';
import { computed, ref, watch } from 'vue';
import { toCents } from '@/lib/money';
import type { Product } from '@/types';

export interface CartLine {
    productId: string;
    name: string;
    price: string;
    /** Stock when the product was added; the server checks the real stock on checkout. */
    stock: number;
    quantity: number;
}

const storageKey = (userId: number) => `cart:${userId}`;

/**
 * The shopping cart lives in the browser until it's placed as an order. It's kept per user
 * in localStorage, so it survives reloads and isn't shared with whoever signs in next.
 */
export const useCartStore = defineStore('cart', () => {
    const ownerId = ref<number | null>(null);
    const lines = ref<CartLine[]>([]);

    const count = computed(() => lines.value.reduce((sum, line) => sum + line.quantity, 0));
    const totalCents = computed(() => lines.value.reduce((sum, line) => sum + toCents(line.price) * line.quantity, 0));

    function quantityOf(productId: string): number {
        return lines.value.find((line) => line.productId === productId)?.quantity ?? 0;
    }

    function add(product: Product, quantity = 1): void {
        const line = lines.value.find((item) => item.productId === product.id);

        if (line) {
            line.quantity = Math.min(line.quantity + quantity, product.stock);
            line.stock = product.stock;
            line.price = product.price;
            return;
        }

        lines.value.push({
            productId: product.id,
            name: product.name,
            price: product.price,
            stock: product.stock,
            quantity: Math.min(quantity, product.stock),
        });
    }

    function setQuantity(productId: string, quantity: number): void {
        const line = lines.value.find((item) => item.productId === productId);

        if (line) {
            line.quantity = Math.max(1, Math.min(Math.floor(quantity) || 1, line.stock));
        }
    }

    function remove(productId: string): void {
        lines.value = lines.value.filter((line) => line.productId !== productId);
    }

    function clear(): void {
        lines.value = [];
    }

    /** Load the cart of the signed-in user (or empty it when nobody is signed in). */
    function restore(userId: number | null): void {
        ownerId.value = userId;
        lines.value = userId === null ? [] : read(storageKey(userId));
    }

    watch(
        lines,
        (value) => {
            if (ownerId.value !== null) {
                write(storageKey(ownerId.value), value);
            }
        },
        { deep: true },
    );

    return { lines, count, totalCents, quantityOf, add, setQuantity, remove, clear, restore };
});

// Storage can be unavailable (private mode, blocked site data): the cart then only lives in memory.
function read(key: string): CartLine[] {
    try {
        const stored = localStorage.getItem(key);

        return stored ? (JSON.parse(stored) as CartLine[]) : [];
    } catch {
        return [];
    }
}

function write(key: string, lines: CartLine[]): void {
    try {
        localStorage.setItem(key, JSON.stringify(lines));
    } catch {
        // Ignored: see read().
    }
}
