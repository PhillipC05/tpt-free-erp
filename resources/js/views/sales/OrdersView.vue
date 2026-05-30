<template>
    <div>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100 mb-6">Sales Orders</h1>
        <DataTable :columns="columns" :data="orders" searchable>
            <template #cell-status="{ value }">
                <span :class="statusClass(value as string)" class="px-2 py-1 text-xs rounded-full font-medium capitalize">
                    {{ value }}
                </span>
            </template>
            <template #cell-total="{ value }">
                ${{ Number(value).toLocaleString() }}
            </template>
        </DataTable>
    </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';
import DataTable from '@/components/DataTable.vue';
import apiClient from '@/api/axios';
import type { Order } from '@/types';

const orders = ref<Order[]>([]);

const columns = [
    { key: 'order_number', label: 'Order #', sortable: true },
    { key: 'order_date', label: 'Date', sortable: true },
    { key: 'status', label: 'Status', sortable: true },
    { key: 'total', label: 'Total', sortable: true },
    { key: 'notes', label: 'Notes', sortable: false },
];

function statusClass(status: string): string {
    const classes: Record<string, string> = {
        draft: 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200',
        confirmed: 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
        shipped: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
        delivered: 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
        cancelled: 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
    };
    return classes[status] || 'bg-gray-100 text-gray-800';
}

onMounted(async () => {
    try {
        const res = await apiClient.get('/orders');
        orders.value = res.data;
    } catch {
        orders.value = [];
    }
});
</script>
