<template>
    <div>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100 mb-6">Bill of Materials</h1>
        <DataTable :columns="columns" :data="boms" searchable>
            <template #header>
                <button @click="openCreate" class="px-4 py-2 bg-blue-600 text-white text-sm rounded-md hover:bg-blue-700">
                    Create BOM
                </button>
            </template>
            <template #cell-is_active="{ value }">
                <span :class="value ? 'text-green-600' : 'text-red-600'" class="text-xs">{{ value ? 'Active' : 'Inactive' }}</span>
            </template>
            <template #actions="{ row }">
                <button @click="openEdit(row)" class="text-blue-600 hover:text-blue-800 dark:text-blue-400 mr-3 text-sm">Edit</button>
                <button @click="deleteBom(row.id)" class="text-red-600 hover:text-red-800 dark:text-red-400 text-sm">Delete</button>
            </template>
        </DataTable>

        <ModalDialog v-model="showModal" :title="editingBom ? 'Edit BOM' : 'Create BOM'">
            <form @submit.prevent="submitForm" class="space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">BOM Code</label>
                        <input v-model="form.code" type="text" required placeholder="e.g. BOM-001" class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">BOM Name</label>
                        <input v-model="form.name" type="text" required class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100" />
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Product</label>
                    <select v-model="form.product_id" required class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100">
                        <option value="" disabled>Select a product</option>
                        <option v-for="p in products" :key="p.id" :value="p.id">{{ p.name }} ({{ p.sku }})</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Quantity</label>
                    <input v-model.number="form.quantity" type="number" min="0.01" step="0.01" required class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100" />
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Description</label>
                    <textarea v-model="form.description" rows="2" class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100"></textarea>
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
import type { Bom } from '@/types';
import { useNotificationStore } from '@/stores/notification';

const notify = useNotificationStore();
const boms = ref<Bom[]>([]);
const products = ref<Array<{ id: number; name: string; sku: string }>>([]);
const showModal = ref(false);
const editingBom = ref<Bom | null>(null);
const form = reactive({ code: '', name: '', product_id: '' as number | string, quantity: 1, description: '' });

const columns = [
    { key: 'code', label: 'Code', sortable: true },
    { key: 'name', label: 'Name', sortable: true },
    { key: 'quantity', label: 'Quantity', sortable: true },
    { key: 'is_active', label: 'Status', sortable: true },
];

async function loadBoms() {
    try {
        const res = await apiClient.get('/manufacturing/boms');
        boms.value = res.data?.data ?? res.data ?? [];
    } catch {
        boms.value = [];
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
    editingBom.value = null;
    Object.assign(form, { code: '', name: '', product_id: '', quantity: 1, description: '' });
    showModal.value = true;
}

function openEdit(row: Bom) {
    editingBom.value = row;
    Object.assign(form, {
        code: (row as any).code ?? '',
        name: row.name,
        product_id: (row as any).product_id ?? '',
        quantity: row.quantity ?? 1,
        description: (row as any).description ?? '',
    });
    showModal.value = true;
}

async function submitForm() {
    try {
        if (editingBom.value) {
            await apiClient.put(`/manufacturing/boms/${editingBom.value.id}`, form);
            notify.success('BOM updated successfully');
        } else {
            await apiClient.post('/manufacturing/boms', form);
            notify.success('BOM created successfully');
        }
        showModal.value = false;
        await loadBoms();
    } catch {
        notify.error(editingBom.value ? 'Failed to update BOM' : 'Failed to create BOM');
    }
}

async function deleteBom(id: number) {
    if (!confirm('Delete this BOM?')) return;
    try {
        await apiClient.delete(`/manufacturing/boms/${id}`);
        notify.success('BOM deleted');
        await loadBoms();
    } catch {
        notify.error('Failed to delete BOM');
    }
}

onMounted(() => {
    loadBoms();
    loadProducts();
});
</script>
