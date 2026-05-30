<template>
    <div>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100 mb-6">Service Tickets</h1>
        <DataTable :columns="columns" :data="tickets" searchable>
            <template #header>
                <button @click="showCreateModal = true" class="px-4 py-2 bg-blue-600 text-white text-sm rounded-md hover:bg-blue-700">
                    New Ticket
                </button>
            </template>
            <template #cell-status="{ value }">
                <span :class="statusClass(value as string)" class="px-2 py-1 text-xs rounded-full font-medium capitalize">
                    {{ (value as string).replace(/_/g, ' ') }}
                </span>
            </template>
            <template #cell-priority="{ value }">
                <span :class="priorityClass(value as string)" class="px-2 py-1 text-xs rounded-full font-medium capitalize">
                    {{ value }}
                </span>
            </template>
        </DataTable>

        <ModalDialog v-model="showCreateModal" title="New Service Ticket">
            <form @submit.prevent="createTicket" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Title</label>
                    <input v-model="form.title" type="text" required class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100" />
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Priority</label>
                    <select v-model="form.priority" class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100">
                        <option value="low">Low</option>
                        <option value="medium">Medium</option>
                        <option value="high">High</option>
                        <option value="urgent">Urgent</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Description</label>
                    <textarea v-model="form.description" rows="3" required class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100"></textarea>
                </div>
                <div class="flex justify-end gap-2">
                    <button type="button" @click="showCreateModal = false" class="px-4 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-md text-gray-700 dark:text-gray-300">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white text-sm rounded-md hover:bg-blue-700">Submit</button>
                </div>
            </form>
        </ModalDialog>
    </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted } from 'vue';
import DataTable from '@/components/DataTable.vue';
import ModalDialog from '@/components/ModalDialog.vue';
import apiClient from '@/api/axios';
import type { ServiceTicket } from '@/types';
import { useNotificationStore } from '@/stores/notification';

const notify = useNotificationStore();
const tickets = ref<ServiceTicket[]>([]);
const showCreateModal = ref(false);
const form = reactive({ title: '', description: '', priority: 'medium' as ServiceTicket['priority'], customer_id: null as number | null });

const columns = [
    { key: 'ticket_number', label: 'Ticket #', sortable: true },
    { key: 'title', label: 'Title', sortable: true },
    { key: 'priority', label: 'Priority', sortable: true },
    { key: 'status', label: 'Status', sortable: true },
    { key: 'created_at', label: 'Created', sortable: true },
];

function statusClass(status: string): string {
    const classes: Record<string, string> = {
        open: 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
        assigned: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
        in_progress: 'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200',
        resolved: 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
        closed: 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200',
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

async function loadTickets() {
    try {
        const res = await apiClient.get('/service-tickets');
        tickets.value = res.data;
    } catch {
        tickets.value = [];
    }
}

async function createTicket() {
    try {
        await apiClient.post('/service-tickets', form);
        showCreateModal.value = false;
        notify.success('Ticket created successfully');
        await loadTickets();
    } catch {
        notify.error('Failed to create ticket');
    }
}

onMounted(loadTickets);
</script>
