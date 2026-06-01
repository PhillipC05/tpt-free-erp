<template>
    <div>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100 mb-6">Work Orders</h1>
        <DataTable :columns="columns" :data="workOrders" searchable>
            <template #header>
                <button @click="openCreate" class="px-4 py-2 bg-blue-600 text-white text-sm rounded-md hover:bg-blue-700">
                    New Work Order
                </button>
            </template>
            <template #cell-status="{ value }">
                <span :class="statusClass(value as string)" class="px-2 py-1 text-xs rounded-full font-medium">
                    {{ formatStatus(value as string) }}
                </span>
            </template>
            <template #cell-quantity="{ value }">
                {{ Number(value).toLocaleString() }}
            </template>
            <template #cell-produced_quantity="{ value }">
                {{ Number(value).toLocaleString() }}
            </template>
            <template #actions="{ row }">
                <button @click="openEdit(row)" class="text-blue-600 hover:text-blue-800 dark:text-blue-400 mr-3 text-sm">Edit</button>
                <button @click="deleteWorkOrder(row.id)" class="text-red-600 hover:text-red-800 dark:text-red-400 text-sm">Delete</button>
            </template>
        </DataTable>

        <ModalDialog v-model="showModal" :title="editingWorkOrder ? 'Edit Work Order' : 'New Work Order'">
            <form @submit.prevent="submitForm" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">WO Number</label>
                    <input v-model="form.wo_number" type="text" required placeholder="e.g. WO-001" class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100" />
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Product</label>
                    <select v-model="form.product_id" required class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100">
                        <option value="" disabled>Select a product</option>
                        <option v-for="p in products" :key="p.id" :value="p.id">{{ p.name }} ({{ p.sku }})</option>
                    </select>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Planned Qty</label>
                        <input v-model.number="form.planned_quantity" type="number" min="1" required class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Status</label>
                        <select v-model="form.status" class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100">
                            <option value="planned">Planned</option>
                            <option value="in_progress">In Progress</option>
                            <option value="completed">Completed</option>
                            <option value="cancelled">Cancelled</option>
                        </select>
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Start Date</label>
                        <input v-model="form.start_date" type="date" required class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">End Date</label>
                        <input v-model="form.end_date" type="date" class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100" />
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Notes</label>
                    <textarea v-model="form.notes" rows="2" class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100"></textarea>
                </div>
                <div class="flex justify-end gap-2">
                    <button type="button" @click="showModal = false" class="px-4 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-md text-gray-700 dark:text-gray-300">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white text-sm rounded-md hover:bg-blue-700">Save</button>
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
import type { WorkOrder } from '@/types';
import { useNotificationStore } from '@/stores/notification';

const notify = useNotificationStore();
const workOrders = ref<WorkOrder[]>([]);
const products = ref<Array<{ id: number; name: string; sku: string }>>([]);
const showModal = ref(false);
const editingWorkOrder = ref<WorkOrder | null>(null);
const form = reactive({ wo_number: '', product_id: '' as number | string, planned_quantity: 1, status: 'planned', start_date: '', end_date: '', notes: '' });

const columns = [
    { key: 'work_order_number', label: 'WO #', sortable: true },
    { key: 'quantity', label: 'Qty', sortable: true },
    { key: 'produced_quantity', label: 'Produced', sortable: true },
    { key: 'scheduled_date', label: 'Scheduled', sortable: true },
    { key: 'status', label: 'Status', sortable: true },
];

function statusClass(status: string): string {
    const classes: Record<string, string> = {
        planned: 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200',
        in_progress: 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
        completed: 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
        cancelled: 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
    };
    return classes[status] || 'bg-gray-100 text-gray-800';
}

function formatStatus(status: string): string {
    return status.replace(/_/g, ' ').replace(/\b\w/g, (c) => c.toUpperCase());
}

async function loadWorkOrders() {
    try {
        const res = await apiClient.get('/manufacturing/work-orders');
        workOrders.value = res.data?.data ?? res.data ?? [];
    } catch {
        workOrders.value = [];
    }
}

async function loadProducts() {
    try {
        const res = await apiClient.get('/inventory/products');
        products.value = res.data?.data ?? res.data ?? [];
    } catch {
        products.value = [];
    }
}

function openCreate() {
    editingWorkOrder.value = null;
    Object.assign(form, { wo_number: '', product_id: '', planned_quantity: 1, status: 'planned', start_date: '', end_date: '', notes: '' });
    showModal.value = true;
}

function openEdit(row: WorkOrder) {
    editingWorkOrder.value = row;
    Object.assign(form, {
        wo_number: (row as any).wo_number ?? '',
        product_id: (row as any).product_id ?? '',
        planned_quantity: (row as any).planned_quantity ?? 1,
        status: row.status ?? 'planned',
        start_date: (row as any).start_date ?? '',
        end_date: (row as any).end_date ?? '',
        notes: (row as any).notes ?? '',
    });
    showModal.value = true;
}

async function submitForm() {
    try {
        if (editingWorkOrder.value) {
            await apiClient.put(`/manufacturing/work-orders/${editingWorkOrder.value.id}`, form);
            notify.success('Work order updated successfully');
        } else {
            await apiClient.post('/manufacturing/work-orders', form);
            notify.success('Work order created successfully');
        }
        showModal.value = false;
        await loadWorkOrders();
    } catch {
        notify.error(editingWorkOrder.value ? 'Failed to update work order' : 'Failed to create work order');
    }
}

async function deleteWorkOrder(id: number) {
    if (!confirm('Delete this work order?')) return;
    try {
        await apiClient.delete(`/manufacturing/work-orders/${id}`);
        notify.success('Work order deleted');
        await loadWorkOrders();
    } catch {
        notify.error('Failed to delete work order');
    }
}

onMounted(() => {
    loadWorkOrders();
    loadProducts();
});
</script>
