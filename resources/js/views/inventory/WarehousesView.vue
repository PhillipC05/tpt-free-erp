<template>
    <div>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100 mb-6">Warehouses</h1>
        <DataTable :columns="columns" :data="warehouses" searchable>
            <template #header>
                <button @click="showCreateModal = true" class="px-4 py-2 bg-blue-600 text-white text-sm rounded-md hover:bg-blue-700">
                    Add Warehouse
                </button>
            </template>
            <template #cell-is_active="{ value }">
                <span :class="value ? 'text-green-600' : 'text-red-600'" class="text-xs">{{ value ? 'Active' : 'Inactive' }}</span>
            </template>
        </DataTable>

        <ModalDialog v-model="showCreateModal" title="Create Warehouse">
            <form @submit.prevent="createWarehouse" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Code</label>
                    <input v-model="form.code" type="text" required class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100" />
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Name</label>
                    <input v-model="form.name" type="text" required class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100" />
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Address</label>
                    <textarea v-model="form.address" rows="2" class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100"></textarea>
                </div>
                <div class="flex justify-end gap-2">
                    <button type="button" @click="showCreateModal = false" class="px-4 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-md text-gray-700 dark:text-gray-300">Cancel</button>
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
import type { Warehouse } from '@/types';
import { useNotificationStore } from '@/stores/notification';

const notify = useNotificationStore();
const warehouses = ref<Warehouse[]>([]);
const showCreateModal = ref(false);
const form = reactive({ code: '', name: '', address: '' });

const columns = [
    { key: 'code', label: 'Code', sortable: true },
    { key: 'name', label: 'Name', sortable: true },
    { key: 'address', label: 'Address', sortable: false },
    { key: 'is_active', label: 'Status', sortable: true },
];

async function loadWarehouses() {
    try {
        const res = await apiClient.get('/inventory/warehouses');
        warehouses.value = res.data?.data ?? res.data ?? [];
    } catch {
        warehouses.value = [];
    }
}

async function createWarehouse() {
    try {
        await apiClient.post('/inventory/warehouses', form);
        showCreateModal.value = false;
        notify.success('Warehouse created successfully');
        await loadWarehouses();
    } catch {
        notify.error('Failed to create warehouse');
    }
}

onMounted(loadWarehouses);
</script>
