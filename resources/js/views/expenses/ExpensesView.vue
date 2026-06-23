<template>
    <div>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100 mb-6">Expense Reports</h1>

        <DataTable :columns="columns" :data="expenses" searchable>
            <template #header>
                <button @click="openCreate" class="px-4 py-2 bg-blue-600 text-white text-sm rounded-md hover:bg-blue-700">
                    New Expense Report
                </button>
            </template>
            <template #cell-status="{ value }">
                <span :class="statusClass(value as string)" class="px-2 py-1 text-xs rounded-full font-medium">
                    {{ formatStatus(value as string) }}
                </span>
            </template>
            <template #cell-total_amount="{ value }">
                ${{ Number(value ?? 0).toLocaleString() }}
            </template>
            <template #cell-actions="{ row }">
                <div class="flex gap-2">
                    <button
                        v-if="(row as any).status === 'draft'"
                        @click="submitExpense(row as any)"
                        class="text-xs text-blue-600 dark:text-blue-400 hover:underline"
                    >Submit</button>
                    <button
                        v-if="(row as any).status === 'submitted'"
                        @click="approveExpense(row as any)"
                        class="text-xs text-green-600 dark:text-green-400 hover:underline"
                    >Approve</button>
                </div>
            </template>
        </DataTable>

        <ModalDialog v-model="showModal" title="New Expense Report">
            <form @submit.prevent="createExpense" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Title</label>
                    <input v-model="form.title" type="text" required class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100" />
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Notes</label>
                    <textarea v-model="form.notes" rows="3" class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100" />
                </div>
                <div class="flex justify-end gap-2">
                    <button type="button" @click="showModal = false" class="px-4 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-md text-gray-700 dark:text-gray-300">Cancel</button>
                    <button type="submit" :disabled="saving" class="px-4 py-2 bg-blue-600 text-white text-sm rounded-md hover:bg-blue-700 disabled:opacity-50">
                        {{ saving ? 'Creating...' : 'Create' }}
                    </button>
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
import { useNotificationStore } from '@/stores/notification';

const notify = useNotificationStore();

interface Expense {
    id: number;
    title: string;
    submitter_name?: string;
    department?: string;
    status: string;
    total_amount?: number;
    submitted_at?: string;
    notes?: string;
}

const expenses = ref<Expense[]>([]);
const showModal = ref(false);
const saving = ref(false);

const form = reactive({ title: '', notes: '' });

const columns = [
    { key: 'title', label: 'Title', sortable: true },
    { key: 'submitter_name', label: 'Submitter', sortable: true },
    { key: 'department', label: 'Department', sortable: true },
    { key: 'status', label: 'Status', sortable: true },
    { key: 'total_amount', label: 'Total', sortable: true },
    { key: 'submitted_at', label: 'Submitted', sortable: true },
    { key: 'actions', label: 'Actions' },
];

function statusClass(status: string): string {
    const classes: Record<string, string> = {
        draft: 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200',
        submitted: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
        approved: 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
        rejected: 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
        paid: 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
    };
    return classes[status] || 'bg-gray-100 text-gray-800';
}

function formatStatus(status: string): string {
    return status.charAt(0).toUpperCase() + status.slice(1);
}

function openCreate() {
    form.title = '';
    form.notes = '';
    showModal.value = true;
}

async function createExpense() {
    saving.value = true;
    try {
        await apiClient.post('/v1/expenses', form);
        notify.success('Expense report created');
        showModal.value = false;
        await load();
    } catch {
        notify.error('Failed to create expense report');
    } finally {
        saving.value = false;
    }
}

async function submitExpense(expense: Expense) {
    try {
        await apiClient.put(`/v1/expenses/${expense.id}`, { status: 'submitted' });
        notify.success('Submitted for approval');
        await load();
    } catch {
        notify.error('Failed to submit');
    }
}

async function approveExpense(expense: Expense) {
    try {
        await apiClient.post(`/v1/expenses/${expense.id}/approve`);
        notify.success('Expense approved');
        await load();
    } catch {
        notify.error('Failed to approve');
    }
}

async function load() {
    try {
        const res = await apiClient.get('/v1/expenses');
        expenses.value = res.data?.data ?? [];
    } catch {
        expenses.value = [];
    }
}

onMounted(load);
</script>
