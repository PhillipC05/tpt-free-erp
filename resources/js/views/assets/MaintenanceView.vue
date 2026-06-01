<template>
    <div>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100 mb-6">Asset Maintenance</h1>
        <DataTable :columns="columns" :data="records" searchable>
            <template #cell-status="{ value }">
                <span :class="statusClass(value as string)" class="px-2 py-1 text-xs rounded-full font-medium capitalize">
                    {{ (value as string).replace('_', ' ') }}
                </span>
            </template>
            <template #cell-type="{ value }">
                <span class="text-xs text-gray-600 dark:text-gray-400 capitalize">{{ value }}</span>
            </template>
            <template #cell-cost="{ value }">
                {{ value != null ? '$' + Number(value).toLocaleString() : '—' }}
            </template>
        </DataTable>
    </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';
import DataTable from '@/components/DataTable.vue';
import apiClient from '@/api/axios';
import { useNotificationStore } from '@/stores/notification';

const notify = useNotificationStore();
const records = ref([]);

const columns = [
    { key: 'title', label: 'Title', sortable: true },
    { key: 'type', label: 'Type', sortable: true },
    { key: 'scheduled_date', label: 'Scheduled', sortable: true },
    { key: 'completed_date', label: 'Completed', sortable: true },
    { key: 'cost', label: 'Cost', sortable: true },
    { key: 'status', label: 'Status', sortable: true },
];

function statusClass(status: string): string {
    const classes: Record<string, string> = {
        scheduled: 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
        in_progress: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
        completed: 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
        cancelled: 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
    };
    return classes[status] || 'bg-gray-100 text-gray-800';
}

onMounted(async () => {
    try {
        const res = await apiClient.get('/assets/maintenance');
        records.value = res.data?.data ?? res.data ?? [];
    } catch {
        notify.error('Failed to load maintenance records');
    }
});
</script>
