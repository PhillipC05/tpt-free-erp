<template>
    <div>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100 mb-6">Assets</h1>
        <DataTable :columns="columns" :data="assets" searchable>
            <template #header>
                <button @click="showCreateModal = true" class="px-4 py-2 bg-blue-600 text-white text-sm rounded-md hover:bg-blue-700">
                    Add Asset
                </button>
            </template>
            <template #cell-status="{ value }">
                <span :class="statusClass(value as string)" class="px-2 py-1 text-xs rounded-full font-medium capitalize">
                    {{ value }}
                </span>
            </template>
            <template #cell-current_value="{ value }">
                ${{ Number(value).toLocaleString() }}
            </template>
            <template #cell-purchase_cost="{ value }">
                ${{ Number(value).toLocaleString() }}
            </template>
        </DataTable>

        <ModalDialog v-model="showCreateModal" title="Add Asset">
            <form @submit.prevent="createAsset" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Asset Name</label>
                    <input v-model="form.name" type="text" required class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100" />
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Asset Code</label>
                    <input v-model="form.asset_code" type="text" required class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100" />
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Category</label>
                    <input v-model="form.category" type="text" required class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100" />
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Purchase Date</label>
                        <input v-model="form.purchase_date" type="date" required class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Purchase Cost ($)</label>
                        <input v-model.number="form.purchase_cost" type="number" min="0" step="0.01" required class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100" />
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Location</label>
                    <input v-model="form.location" type="text" class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100" />
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
import type { Asset } from '@/types';
import { useNotificationStore } from '@/stores/notification';

const notify = useNotificationStore();
const assets = ref<Asset[]>([]);
const showCreateModal = ref(false);
const form = reactive({ name: '', asset_code: '', category: '', purchase_date: '', purchase_cost: 0, location: '' });

const columns = [
    { key: 'asset_code', label: 'Code', sortable: true },
    { key: 'name', label: 'Name', sortable: true },
    { key: 'category', label: 'Category', sortable: true },
    { key: 'purchase_cost', label: 'Cost', sortable: true },
    { key: 'current_value', label: 'Current Value', sortable: true },
    { key: 'status', label: 'Status', sortable: true },
];

function statusClass(status: string): string {
    const classes: Record<string, string> = {
        active: 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
        maintenance: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
        retired: 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
    };
    return classes[status] || 'bg-gray-100 text-gray-800';
}

async function loadAssets() {
    try {
        const res = await apiClient.get('/assets');
        assets.value = res.data;
    } catch {
        assets.value = [];
    }
}

async function createAsset() {
    try {
        await apiClient.post('/assets', form);
        showCreateModal.value = false;
        notify.success('Asset added successfully');
        await loadAssets();
    } catch {
        notify.error('Failed to add asset');
    }
}

onMounted(loadAssets);
</script>
