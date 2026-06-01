<template>
    <div>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100 mb-6">Stock Movements</h1>
        <DataTable :columns="columns" :data="movements" searchable>
            <template #cell-type="{ value }">
                <span :class="typeClass(value as string)" class="px-2 py-1 text-xs rounded-full font-medium">
                    {{ value }}
                </span>
            </template>
            <template #cell-quantity="{ value }">
                {{ Number(value).toLocaleString() }}
            </template>
        </DataTable>
    </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';
import DataTable from '@/components/DataTable.vue';
import apiClient from '@/api/axios';
import type { StockMovement } from '@/types';

const movements = ref<StockMovement[]>([]);

const columns = [
    { key: 'product', label: 'Product', sortable: false },
    { key: 'warehouse', label: 'Warehouse', sortable: false },
    { key: 'type', label: 'Type', sortable: true },
    { key: 'quantity', label: 'Quantity', sortable: true },
    { key: 'notes', label: 'Notes', sortable: false },
    { key: 'created_at', label: 'Date', sortable: true },
];

function typeClass(type: string): string {
    const classes: Record<string, string> = {
        in: 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
        out: 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
        transfer: 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
    };
    return classes[type] || 'bg-gray-100 text-gray-800';
}

onMounted(async () => {
    try {
        const res = await apiClient.get('/inventory/stock-movements');
        movements.value = res.data?.data ?? res.data ?? [];
    } catch {
        movements.value = [];
    }
});
</script>
