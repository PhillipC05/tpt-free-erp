<template>
    <div>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100 mb-6">Tasks</h1>
        <DataTable :columns="columns" :data="tasks" searchable>
            <template #cell-status="{ value }">
                <span :class="statusClass(value as string)" class="px-2 py-1 text-xs rounded-full font-medium">
                    {{ formatStatus(value as string) }}
                </span>
            </template>
            <template #cell-priority="{ value }">
                <span :class="priorityClass(value as string)" class="px-2 py-1 text-xs rounded-full font-medium capitalize">
                    {{ value }}
                </span>
            </template>
        </DataTable>
    </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';
import DataTable from '@/components/DataTable.vue';
import apiClient from '@/api/axios';
import type { Task } from '@/types';

const tasks = ref<Task[]>([]);

const columns = [
    { key: 'title', label: 'Title', sortable: true },
    { key: 'priority', label: 'Priority', sortable: true },
    { key: 'status', label: 'Status', sortable: true },
    { key: 'due_date', label: 'Due Date', sortable: true },
    { key: 'estimated_hours', label: 'Est. Hours', sortable: true },
];

function statusClass(status: string): string {
    const classes: Record<string, string> = {
        todo: 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200',
        in_progress: 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
        review: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
        done: 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
    };
    return classes[status] || 'bg-gray-100 text-gray-800';
}

function priorityClass(priority: string): string {
    const classes: Record<string, string> = {
        low: 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300',
        medium: 'bg-blue-100 text-blue-700 dark:bg-blue-900 dark:text-blue-300',
        high: 'bg-orange-100 text-orange-700 dark:bg-orange-900 dark:text-orange-300',
        urgent: 'bg-red-100 text-red-700 dark:bg-red-900 dark:text-red-300',
    };
    return classes[priority] || 'bg-gray-100 text-gray-600';
}

function formatStatus(status: string): string {
    return status.replace(/_/g, ' ').replace(/\b\w/g, (c) => c.toUpperCase());
}

onMounted(async () => {
    try {
        const res = await apiClient.get('/projects/tasks');
        tasks.value = res.data?.data ?? res.data ?? [];
    } catch {
        tasks.value = [];
    }
});
</script>
