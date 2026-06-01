<template>
    <div>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100 mb-6">Invoices</h1>
        <DataTable :columns="columns" :data="invoices" searchable>
            <template #cell-status="{ value }">
                <span :class="statusClass(value as string)" class="px-2 py-1 text-xs rounded-full font-medium capitalize">
                    {{ value }}
                </span>
            </template>
            <template #cell-total="{ value }">
                ${{ Number(value).toLocaleString() }}
            </template>
            <template #cell-paid_amount="{ value }">
                ${{ Number(value).toLocaleString() }}
            </template>
        </DataTable>
    </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';
import DataTable from '@/components/DataTable.vue';
import apiClient from '@/api/axios';
import type { Invoice } from '@/types';

const invoices = ref<Invoice[]>([]);

const columns = [
    { key: 'invoice_number', label: 'Invoice #', sortable: true },
    { key: 'invoice_date', label: 'Date', sortable: true },
    { key: 'due_date', label: 'Due Date', sortable: true },
    { key: 'total', label: 'Total', sortable: true },
    { key: 'paid_amount', label: 'Paid', sortable: true },
    { key: 'status', label: 'Status', sortable: true },
];

function statusClass(status: string): string {
    const classes: Record<string, string> = {
        draft: 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200',
        sent: 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
        paid: 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
        overdue: 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
        cancelled: 'bg-gray-100 text-gray-500 dark:bg-gray-700 dark:text-gray-400',
    };
    return classes[status] || 'bg-gray-100 text-gray-800';
}

onMounted(async () => {
    try {
        const res = await apiClient.get('/sales/invoices');
        invoices.value = res.data?.data ?? res.data ?? [];
    } catch {
        invoices.value = [];
    }
});
</script>
