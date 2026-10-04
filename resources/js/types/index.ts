export type Role = 'admin' | 'customer';

export interface User {
    id: string;
    name: string;
    email: string;
    role: Role;
}

/** Shared by every page (HandleInertiaRequests). */
export interface Auth {
    user: User | null;
    can: {
        manageCatalog: boolean;
        placeOrders: boolean;
    };
}

export interface Toast {
    type: 'success' | 'error';
    message: string;
}

export interface Category {
    id: string;
    name: string;
    created_at: string;
    updated_at: string;
}

export type CategoryOption = Pick<Category, 'id' | 'name'>;

export type ProductStatus = 'active' | 'inactive';

export interface Product {
    id: string;
    name: string;
    category_id: string;
    category?: Category;
    /** Decimal string with two decimals, e.g. "19.99". */
    price: string;
    stock: number;
    status: ProductStatus;
    created_at: string;
    updated_at: string;
}

export interface ProductFilters {
    search?: string;
    category_id?: string;
    status?: ProductStatus;
}

/**
 * A Laravel API resource collection. Page-number paginators fill `current_page`/`last_page`/
 * `total`; cursor paginators fill `next_cursor`/`prev_cursor` instead.
 */
export interface ResourceCollection<T> {
    data: T[];
    links: {
        prev: string | null;
        next: string | null;
    };
    meta: {
        per_page: number;
        current_page?: number;
        last_page?: number;
        total?: number;
        next_cursor?: string | null;
        prev_cursor?: string | null;
    };
}

export interface ProductFormData {
    name: string;
    category_id: string;
    price: string;
    stock: number;
    status: ProductStatus;
}

export interface CategoryFormData {
    name: string;
}

export interface OrderLine {
    product_id: string;
    name: string;
    /** Unit price, decimal string. */
    price: string;
    quantity: number;
}

export interface Order {
    id: string;
    user_id: string;
    number: number;
    /** Decimal string, calculated when the order was placed. */
    total: string;
    products: OrderLine[];
    created_at: string;
    updated_at: string;
}
