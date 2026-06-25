<template>
    <div>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100 mb-6">POS Terminals</h1>
        <DataTable :columns="columns" :data="terminals" searchable>
            <template #header>
                <button @click="showCreateModal = true" class="px-4 py-2 bg-blue-600 text-white text-sm rounded-md hover:bg-blue-700">
                    Add Terminal
                </button>
            </template>
            <template #cell-status="{ value }">
                <span :class="{
                    'text-green-600': value === 'active',
                    'text-red-600': value === 'inactive',
                    'text-yellow-600': value === 'maintenance'
                }" class="text-xs font-medium capitalize">{{ value }}</span>
            </template>
        </DataTable>

        <ModalDialog v-model="showCreateModal" title="Add POS Terminal">
            <form @submit.prevent="createTerminal" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Terminal Code</label>
                    <input v-model="form.terminal_code" type="text" required placeholder="e.g. POS-001" class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100" />
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Name</label>
                    <input v-model="form.name" type="text" required placeholder="e.g. Main Counter" class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100" />
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Status</label>
                    <select v-model="form.status" class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100">
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                        <option value="maintenance">Maintenance</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Notes</label>
                    <textarea v-model="form.notes" rows="2" class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100"></textarea>
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
import type { PosTerminal } from '@/types';
import { useNotificationStore } from '@/stores/notification';

const notify = useNotificationStore();
const terminals = ref<PosTerminal[]>([]);
const showCreateModal = ref(false);
const form = reactive({ terminal_code: '', name: '', status: 'active', notes: '' });

const columns = [
    { key: 'terminal_code', label: 'Code', sortable: true },
    { key: 'name', label: 'Name', sortable: true },
    { key: 'status', label: 'Status', sortable: true },
    { key: 'notes', label: 'Notes', sortable: false },
];

async function loadTerminals() {
    try {
        const res = await apiClient.get('/pos/terminals');
        terminals.value = res.data?.data ?? res.data ?? [];
    } catch {
        terminals.value = [];
    }
}

async function createTerminal() {
    try {
        await apiClient.post('/pos/terminals', form);
        showCreateModal.value = false;
        notify.success('Terminal added successfully');
        form.terminal_code = '';
        form.name = '';
        form.status = 'active';
        form.notes = '';
        await loadTerminals();
    } catch {
        notify.error('Failed to add terminal');
    }
}

onMounted(loadTerminals);
</script>
