<template>
    <div>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100 mb-6">Budgets</h1>

        <DataTable :columns="columns" :data="budgets" searchable>
            <template #header>
                <button @click="openCreate" class="px-4 py-2 bg-blue-600 text-white text-sm rounded-md hover:bg-blue-700">
                    New Budget
                </button>
            </template>
            <template #cell-status="{ value }">
                <span :class="value === 'approved' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200'" class="px-2 py-1 text-xs rounded-full font-medium">
                    {{ String(value).charAt(0).toUpperCase() + String(value).slice(1) }}
                </span>
            </template>
            <template #cell-actions="{ row }">
                <div class="flex gap-2">
                    <button @click="openEdit(row as any)" class="text-xs text-blue-600 dark:text-blue-400 hover:underline">Edit</button>
                    <button @click="deleteBudget(row as any)" class="text-xs text-red-600 dark:text-red-400 hover:underline">Delete</button>
                </div>
            </template>
        </DataTable>

        <ModalDialog v-model="showModal" :title="editingId ? 'Edit Budget' : 'New Budget'">
            <form @submit.prevent="saveBudget" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Name</label>
                    <input v-model="form.name" type="text" required class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100" />
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Period Type</label>
                        <select v-model="form.period_type" class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100">
                            <option value="annual">Annual</option>
                            <option value="quarterly">Quarterly</option>
                            <option value="monthly">Monthly</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Year</label>
                        <input v-model.number="form.year" type="number" min="2000" max="2100" class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Department ID</label>
                        <input v-model.number="form.department_id" type="number" placeholder="Optional" class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Status</label>
                        <select v-model="form.status" class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100">
                            <option value="draft">Draft</option>
                            <option value="approved">Approved</option>
                            <option value="archived">Archived</option>
                        </select>
                    </div>
                </div>
                <div class="flex justify-end gap-2">
                    <button type="button" @click="showModal = false" class="px-4 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-md text-gray-700 dark:text-gray-300">Cancel</button>
                    <button type="submit" :disabled="saving" class="px-4 py-2 bg-blue-600 text-white text-sm rounded-md hover:bg-blue-700 disabled:opacity-50">
                        {{ saving ? 'Saving...' : 'Save' }}
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

interface Budget {
    id: number;
    name: string;
    period_type: string;
    year: number;
    department_id?: number;
    status: string;
}

const budgets = ref<Budget[]>([]);
const showModal = ref(false);
const saving = ref(false);
const editingId = ref<number | null>(null);

const defaultForm = () => ({
    name: '', period_type: 'annual', year: new Date().getFullYear(),
    department_id: null as number | null, status: 'draft',
});
const form = reactive(defaultForm());

const columns = [
    { key: 'name', label: 'Name', sortable: true },
    { key: 'period_type', label: 'Period', sortable: true },
    { key: 'year', label: 'Year', sortable: true },
    { key: 'department_id', label: 'Department', sortable: true },
    { key: 'status', label: 'Status', sortable: true },
    { key: 'actions', label: 'Actions' },
];

function openCreate() {
    editingId.value = null;
    Object.assign(form, defaultForm());
    showModal.value = true;
}

function openEdit(budget: Budget) {
    editingId.value = budget.id;
    Object.assign(form, budget);
    showModal.value = true;
}

async function saveBudget() {
    saving.value = true;
    try {
        if (editingId.value) {
            await apiClient.put(`/v1/finance/budgets/${editingId.value}`, form);
            notify.success('Budget updated');
        } else {
            await apiClient.post('/v1/finance/budgets', form);
            notify.success('Budget created');
        }
        showModal.value = false;
        await load();
    } catch {
        notify.error('Failed to save budget');
    } finally {
        saving.value = false;
    }
}

async function deleteBudget(budget: Budget) {
    if (!confirm(`Delete budget "${budget.name}"?`)) return;
    try {
        await apiClient.delete(`/v1/finance/budgets/${budget.id}`);
        notify.success('Budget deleted');
        await load();
    } catch {
        notify.error('Failed to delete budget');
    }
}

async function load() {
    try {
        const res = await apiClient.get('/v1/finance/budgets');
        budgets.value = res.data?.data ?? [];
    } catch {
        budgets.value = [];
    }
}

onMounted(load);
</script>
