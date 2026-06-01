<template>
    <div>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100 mb-6">Journal Entries</h1>
        <DataTable :columns="columns" :data="entries" searchable>
            <template #cell-status="{ value }">
                <span :class="statusClass(value as string)" class="px-2 py-1 text-xs rounded-full font-medium capitalize">
                    {{ value }}
                </span>
            </template>
            <template #cell-total_debit="{ value }">${{ Number(value).toLocaleString() }}</template>
            <template #cell-total_credit="{ value }">${{ Number(value).toLocaleString() }}</template>
        </DataTable>
    </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';
import DataTable from '@/components/DataTable.vue';
import apiClient from '@/api/axios';
import { useNotificationStore } from '@/stores/notification';

const notify = useNotificationStore();
const entries = ref([]);

const columns = [
    { key: 'entry_number', label: 'Entry #', sortable: true },
    { key: 'entry_date', label: 'Date', sortable: true },
    { key: 'description', label: 'Description', sortable: false },
    { key: 'total_debit', label: 'Debit', sortable: true },
    { key: 'total_credit', label: 'Credit', sortable: true },
    { key: 'status', label: 'Status', sortable: true },
];

function statusClass(status: string): string {
    const classes: Record<string, string> = {
        draft: 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200',
        posted: 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
        void: 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
    };
    return classes[status] || 'bg-gray-100 text-gray-800';
}

onMounted(async () => {
    try {
        const res = await apiClient.get('/finance/journal-entries');
        entries.value = res.data?.data ?? res.data ?? [];
    } catch {
        notify.error('Failed to load journal entries');
    }
});
</script>
