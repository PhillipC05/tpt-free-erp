<template>
    <div>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100 mb-6">Products</h1>
        <DataTable :columns="columns" :data="products" searchable>
            <template #header>
                <button @click="openCreate" class="px-4 py-2 bg-blue-600 text-white text-sm rounded-md hover:bg-blue-700">Add Product</button>
            </template>
            <template #cell-unit_price="{ value }">${{ Number(value).toLocaleString() }}</template>
            <template #cell-is_active="{ value }">
                <span :class="value ? 'text-green-600' : 'text-red-600'" class="text-xs">{{ value ? 'Active' : 'Inactive' }}</span>
            </template>
            <template #actions="{ row }">
                <button @click="openEdit(row)" class="text-blue-600 hover:text-blue-800 dark:text-blue-400 mr-3 text-sm">Edit</button>
                <button @click="deleteProduct(row.id)" class="text-red-600 hover:text-red-800 dark:text-red-400 text-sm">Delete</button>
            </template>
        </DataTable>

        <ModalDialog v-model="showModal" :title="editingProduct ? 'Edit Product' : 'Add Product'">
            <form @submit.prevent="submitForm" class="space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">SKU</label>
                        <input v-model="form.sku" type="text" required placeholder="e.g. PROD-001" class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Unit</label>
                        <input v-model="form.unit" type="text" required placeholder="e.g. pcs, kg, L" class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100" />
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Name</label>
                    <input v-model="form.name" type="text" required class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100" />
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Unit Price ($)</label>
                        <input v-model.number="form.unit_price" type="number" min="0" step="0.01" class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Cost Price ($)</label>
                        <input v-model.number="form.cost_price" type="number" min="0" step="0.01" class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100" />
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Valuation Method</label>
                        <select v-model="form.valuation_method" class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100">
                            <option value="average">Average</option>
                            <option value="fifo">FIFO</option>
                            <option value="lifo">LIFO</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Min Stock Level</label>
                        <input v-model.number="form.min_stock_level" type="number" min="0" step="0.01" class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100" />
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Description</label>
                    <textarea v-model="form.description" rows="2" class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100"></textarea>
                </div>
                <div class="flex items-center gap-2">
                    <input v-model="form.is_active" type="checkbox" id="is_active" class="rounded border-gray-300 dark:border-gray-600" />
                    <label for="is_active" class="text-sm font-medium text-gray-700 dark:text-gray-300">Active</label>
                </div>
                <div class="flex justify-end gap-2">
                    <button type="button" @click="showModal = false" class="px-4 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-md text-gray-700 dark:text-gray-300">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white text-sm rounded-md hover:bg-blue-700">Save</button>
                </div>
            </form>
        </ModalDialog>
    </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted } from 'vue';
import DataTable from '@/components/DataTable.vue';
import ModalDialog from '@/components/ModalDialog.vue';
import apiClient from '@/api/axios';
import type { Product } from '@/types';
import { useNotificationStore } from '@/stores/notification';

const notify = useNotificationStore();
const products = ref<Product[]>([]);
const showModal = ref(false);
const editingProduct = ref<Product | null>(null);
const form = reactive({ sku: '', name: '', unit: '', unit_price: 0, cost_price: 0, valuation_method: 'average', min_stock_level: 0, description: '', is_active: true });

const columns = [
    { key: 'sku', label: 'SKU', sortable: true },
    { key: 'name', label: 'Name', sortable: true },
    { key: 'unit_price', label: 'Price', sortable: true },
    { key: 'unit', label: 'Unit', sortable: true },
    { key: 'valuation_method', label: 'Valuation', sortable: true },
    { key: 'is_active', label: 'Status', sortable: true },
];

async function loadProducts() {
    try {
        const res = await apiClient.get('/inventory/products');
        products.value = res.data?.data ?? res.data ?? [];
    } catch {
        products.value = [];
    }
}

function openCreate() {
    editingProduct.value = null;
    Object.assign(form, { sku: '', name: '', unit: '', unit_price: 0, cost_price: 0, valuation_method: 'average', min_stock_level: 0, description: '', is_active: true });
    showModal.value = true;
}

function openEdit(row: Product) {
    editingProduct.value = row;
    Object.assign(form, {
        sku: row.sku,
        name: row.name,
        unit: row.unit ?? '',
        unit_price: row.unit_price ?? 0,
        cost_price: (row as any).cost_price ?? 0,
        valuation_method: row.valuation_method ?? 'average',
        min_stock_level: (row as any).min_stock_level ?? 0,
        description: row.description ?? '',
        is_active: row.is_active ?? true,
    });
    showModal.value = true;
}

async function submitForm() {
    try {
        if (editingProduct.value) {
            await apiClient.put(`/inventory/products/${editingProduct.value.id}`, form);
            notify.success('Product updated successfully');
        } else {
            await apiClient.post('/inventory/products', form);
            notify.success('Product added successfully');
        }
        showModal.value = false;
        await loadProducts();
    } catch {
        notify.error(editingProduct.value ? 'Failed to update product' : 'Failed to add product');
    }
}

async function deleteProduct(id: number) {
    if (!confirm('Delete this product?')) return;
    try {
        await apiClient.delete(`/inventory/products/${id}`);
        notify.success('Product deleted');
        await loadProducts();
    } catch {
        notify.error('Failed to delete product');
    }
}

onMounted(loadProducts);
</script>
