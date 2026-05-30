<template>
    <div>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100 mb-6">Work Orders</h1>
        <DataTable :columns="columns" :data="workOrders" searchable>
            <template #cell-status="{ value }">
                <span :class="statusClass(value as string)" class="px-2 py-1 text-xs rounded-full font-medium">
                    {{ formatStatus(value as string) }}
                </span>
            </template>
            <template #cell-quantity="{ value }">
                {{ Number(value).toLocaleString() }}
            </template>
            <template #cell-produced_quantity="{ value }">
                {{ Number(value).toLocaleString() }}
            </template>
        </DataTable>
    </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';
import DataTable from '@/components/DataTable.vue';
import apiClient from '@/api/axios';
import type { WorkOrder } from '@/types';

const workOrders = ref<WorkOrder[]>([]);

const columns = [
    { key: 'work_order_number', label: 'WO #', sortable: true },
    { key: 'quantity', label: 'Qty', sortable: true },
    { key: 'produced_quantity', label: 'Produced', sortable: true },
    { key: 'scheduled_date', label: 'Scheduled', sortable: true },
    { key: 'status', label: 'Status', sortable: true },
];

function statusClass(status: string): string {
    const classes: Record<string, string> = {
        planned: 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200',
        in_progress: 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
        completed: 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
        cancelled: 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
    };
    return classes[status] || 'bg-gray-100 text-gray-800';
}

function formatStatus(status: string): string {
    return status.replace(/_/g, ' ').replace(/\b\w/g, (c) => c.toUpperCase());
}

onMounted(async () => {
    try {
        const res = await apiClient.get('/work-orders');
        workOrders.value = res.data;
    } catch {
        workOrders.value = [];
    }
});
</script>
