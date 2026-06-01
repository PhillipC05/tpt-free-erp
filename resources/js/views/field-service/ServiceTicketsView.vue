<template>
    <div>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100 mb-6">Service Tickets</h1>
        <DataTable :columns="columns" :data="tickets" searchable>
            <template #header>
                <button @click="openCreate" class="px-4 py-2 bg-blue-600 text-white text-sm rounded-md hover:bg-blue-700">
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
            <template #actions="{ row }">
                <button @click="openEdit(row)" class="text-blue-600 hover:text-blue-800 dark:text-blue-400 mr-3 text-sm">Edit</button>
                <button @click="deleteTicket(row.id)" class="text-red-600 hover:text-red-800 dark:text-red-400 text-sm">Delete</button>
            </template>
        </DataTable>

        <ModalDialog v-model="showModal" :title="editingTicket ? 'Edit Ticket' : 'New Service Ticket'">
            <form @submit.prevent="submitForm" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Ticket Number</label>
                    <input v-model="form.ticket_number" type="text" required placeholder="e.g. TKT-001" class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100" />
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Customer</label>
                    <select v-model="form.customer_id" required class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100">
                        <option value="" disabled>Select a customer</option>
                        <option v-for="c in customers" :key="c.id" :value="c.id">{{ c.name }}</option>
                    </select>
                </div>
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
                    <textarea v-model="form.description" rows="3" class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100"></textarea>
                </div>
                <div class="flex justify-end gap-2">
                    <button type="button" @click="showModal = false" class="px-4 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-md text-gray-700 dark:text-gray-300">Cancel</button>
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
const customers = ref<Array<{ id: number; name: string }>>([]);
const showModal = ref(false);
const editingTicket = ref<ServiceTicket | null>(null);
const form = reactive({ ticket_number: '', customer_id: '' as number | string, title: '', description: '', priority: 'medium' });

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
        const res = await apiClient.get('/field-service/tickets');
        tickets.value = res.data?.data ?? res.data ?? [];
    } catch {
        tickets.value = [];
    }
}

async function loadCustomers() {
    try {
        const res = await apiClient.get('/sales/customers');
        customers.value = res.data?.data ?? res.data ?? [];
    } catch {
        customers.value = [];
    }
}

function openCreate() {
    editingTicket.value = null;
    Object.assign(form, { ticket_number: '', customer_id: '', title: '', description: '', priority: 'medium' });
    showModal.value = true;
}

function openEdit(row: ServiceTicket) {
    editingTicket.value = row;
    Object.assign(form, {
        ticket_number: row.ticket_number,
        customer_id: (row as any).customer_id ?? '',
        title: row.title,
        description: row.description ?? '',
        priority: row.priority ?? 'medium',
    });
    showModal.value = true;
}

async function submitForm() {
    try {
        if (editingTicket.value) {
            await apiClient.put(`/field-service/tickets/${editingTicket.value.id}`, form);
            notify.success('Ticket updated successfully');
        } else {
            await apiClient.post('/field-service/tickets', form);
            notify.success('Ticket created successfully');
        }
        showModal.value = false;
        await loadTickets();
    } catch {
        notify.error(editingTicket.value ? 'Failed to update ticket' : 'Failed to create ticket');
    }
}

async function deleteTicket(id: number) {
    if (!confirm('Delete this ticket?')) return;
    try {
        await apiClient.delete(`/field-service/tickets/${id}`);
        notify.success('Ticket deleted');
        await loadTickets();
    } catch {
        notify.error('Failed to delete ticket');
    }
}

onMounted(() => {
    loadTickets();
    loadCustomers();
});
</script>
