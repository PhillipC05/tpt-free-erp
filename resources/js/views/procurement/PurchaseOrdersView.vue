<template>
    <div>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100 mb-6">Purchase Orders</h1>
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
import type { PurchaseOrder } from '@/types';

const orders = ref<PurchaseOrder[]>([]);

const columns = [
    { key: 'po_number', label: 'PO #', sortable: true },
    { key: 'order_date', label: 'Order Date', sortable: true },
    { key: 'expected_delivery', label: 'Expected Delivery', sortable: true },
    { key: 'total', label: 'Total', sortable: true },
    { key: 'status', label: 'Status', sortable: true },
];

function statusClass(status: string): string {
    const classes: Record<string, string> = {
        draft: 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200',
        sent: 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
        confirmed: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
        received: 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
        cancelled: 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
    };
    return classes[status] || 'bg-gray-100 text-gray-800';
}

onMounted(async () => {
    try {
        const res = await apiClient.get('/purchase-orders');
        orders.value = res.data;
    } catch {
        orders.value = [];
    }
});
</script>
