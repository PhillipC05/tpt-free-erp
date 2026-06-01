<template>
    <div>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100 mb-6">Payroll</h1>
        <DataTable :columns="columns" :data="payrolls" searchable>
            <template #cell-status="{ value }">
                <span :class="statusClass(value as string)" class="px-2 py-1 text-xs rounded-full font-medium">
                    {{ value }}
                </span>
            </template>
            <template #cell-net_salary="{ value }">
                ${{ Number(value).toLocaleString() }}
            </template>
            <template #cell-basic_salary="{ value }">
                ${{ Number(value).toLocaleString() }}
            </template>
        </DataTable>
    </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';
import DataTable from '@/components/DataTable.vue';
import apiClient from '@/api/axios';
import type { Payroll } from '@/types';

const payrolls = ref<Payroll[]>([]);

const columns = [
    { key: 'period_start', label: 'Period Start', sortable: true },
    { key: 'period_end', label: 'Period End', sortable: true },
    { key: 'basic_salary', label: 'Basic Salary', sortable: true },
    { key: 'net_salary', label: 'Net Salary', sortable: true },
    { key: 'status', label: 'Status', sortable: true },
];

function statusClass(status: string): string {
    const classes: Record<string, string> = {
        draft: 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200',
        approved: 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
        paid: 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
    };
    return classes[status] || 'bg-gray-100 text-gray-800';
}

onMounted(async () => {
    try {
        const res = await apiClient.get('/hr/payroll');
        payrolls.value = res.data?.data ?? res.data ?? [];
    } catch {
        payrolls.value = [];
    }
});
</script>
