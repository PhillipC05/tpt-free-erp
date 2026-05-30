<template>
    <div>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100 mb-6">Products</h1>
        <DataTable :columns="columns" :data="products" searchable>
            <template #header>
                <button class="px-4 py-2 bg-blue-600 text-white text-sm rounded-md hover:bg-blue-700">Add Product</button>
            </template>
            <template #cell-price="{ value }">${{ Number(value).toLocaleString() }}</template>
            <template #cell-is_active="{ value }">
                <span :class="value ? 'text-green-600' : 'text-red-600'" class="text-xs">{{ value ? 'Active' : 'Inactive' }}</span>
            </template>
        </DataTable>
    </div>
</template>
<script setup lang="ts">
import { ref, onMounted } from 'vue';
import DataTable from '@/components/DataTable.vue';
import apiClient from '@/api/axios';
import type { Product } from '@/types';

const products = ref<Product[]>([]);
const columns = [
    { key: 'sku', label: 'SKU', sortable: true },
    { key: 'name', label: 'Name', sortable: true },
    { key: 'price', label: 'Price', sortable: true },
    { key: 'unit', label: 'Unit', sortable: true },
    { key: 'is_active', label: 'Status', sortable: true },
];
onMounted(async () => {
    try { const res = await apiClient.get('/products'); products.value = res.data; } catch { products.value = []; }
});
</script>