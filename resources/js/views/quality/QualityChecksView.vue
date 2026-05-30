<template>
    <div>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100 mb-6">Quality Checks</h1>
        <DataTable :columns="columns" :data="checks" searchable>
            <template #cell-status="{ value }">
                <span :class="statusClass(value as string)" class="px-2 py-1 text-xs rounded-full font-medium capitalize">
                    {{ value }}
                </span>
            </template>
            <template #cell-type="{ value }">
                <span class="text-xs text-gray-600 dark:text-gray-400 capitalize">
                    {{ (value as string).replace(/_/g, ' ') }}
                </span>
            </template>
        </DataTable>
    </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';
import DataTable from '@/components/DataTable.vue';
import apiClient from '@/api/axios';
import type { QualityCheck } from '@/types';

const checks = ref<QualityCheck[]>([]);

const columns = [
    { key: 'check_number', label: 'Check #', sortable: true },
    { key: 'type', label: 'Type', sortable: true },
    { key: 'inspector', label: 'Inspector', sortable: true },
    { key: 'status', label: 'Status', sortable: true },
    { key: 'checked_at', label: 'Checked At', sortable: true },
];

function statusClass(status: string): string {
    const classes: Record<string, string> = {
        pending: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
        passed: 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
        failed: 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
    };
    return classes[status] || 'bg-gray-100 text-gray-800';
}

onMounted(async () => {
    try {
        const res = await apiClient.get('/quality-checks');
        checks.value = res.data;
    } catch {
        checks.value = [];
    }
});
</script>
