<template>
    <div>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100 mb-6">Bill of Materials</h1>
        <DataTable :columns="columns" :data="boms" searchable>
            <template #header>
                <button @click="showCreateModal = true" class="px-4 py-2 bg-blue-600 text-white text-sm rounded-md hover:bg-blue-700">
                    Create BOM
                </button>
            </template>
            <template #cell-type="{ value }">
                <span class="px-2 py-1 text-xs rounded-full font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200 capitalize">
                    {{ value }}
                </span>
            </template>
            <template #cell-is_active="{ value }">
                <span :class="value ? 'text-green-600' : 'text-red-600'" class="text-xs">{{ value ? 'Active' : 'Inactive' }}</span>
            </template>
        </DataTable>

        <ModalDialog v-model="showCreateModal" title="Create BOM">
            <form @submit.prevent="createBom" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">BOM Name</label>
                    <input v-model="form.name" type="text" required class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100" />
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Type</label>
                    <select v-model="form.type" required class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100">
                        <option value="manufacturing">Manufacturing</option>
                        <option value="assembly">Assembly</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Quantity</label>
                    <input v-model.number="form.quantity" type="number" min="1" required class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100" />
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
import type { Bom } from '@/types';
import { useNotificationStore } from '@/stores/notification';

const notify = useNotificationStore();
const boms = ref<Bom[]>([]);
const showCreateModal = ref(false);
const form = reactive({ name: '', type: 'manufacturing' as Bom['type'], quantity: 1, product_id: null as number | null });

const columns = [
    { key: 'name', label: 'Name', sortable: true },
    { key: 'type', label: 'Type', sortable: true },
    { key: 'quantity', label: 'Quantity', sortable: true },
    { key: 'is_active', label: 'Status', sortable: true },
];

async function loadBoms() {
    try {
        const res = await apiClient.get('/boms');
        boms.value = res.data;
    } catch {
        boms.value = [];
    }
}

async function createBom() {
    try {
        await apiClient.post('/boms', form);
        showCreateModal.value = false;
        notify.success('BOM created successfully');
        await loadBoms();
    } catch {
        notify.error('Failed to create BOM');
    }
}

onMounted(loadBoms);
</script>
