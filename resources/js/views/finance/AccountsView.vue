<template>
    <div>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100 mb-6">Chart of Accounts</h1>
        <DataTable
            :columns="columns"
            :data="accounts"
            searchable
        >
            <template #header>
                <button @click="showCreateModal = true" class="px-4 py-2 bg-blue-600 text-white text-sm rounded-md hover:bg-blue-700">
                    Add Account
                </button>
            </template>
            <template #cell-type="{ value }">
                <span :class="typeClass(value as string)" class="px-2 py-1 text-xs rounded-full font-medium">
                    {{ value }}
                </span>
            </template>
            <template #cell-balance="{ value }">
                <span :class="Number(value) >= 0 ? 'text-green-600' : 'text-red-600'">
                    ${{ Number(value).toLocaleString() }}
                </span>
            </template>
            <template #cell-is_active="{ value }">
                <span :class="value ? 'text-green-600' : 'text-red-600'" class="text-xs">
                    {{ value ? 'Active' : 'Inactive' }}
                </span>
            </template>
        </DataTable>

        <ModalDialog v-model="showCreateModal" title="Create Account">
            <form @submit.prevent="createAccount" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Code</label>
                    <input v-model="form.code" type="text" required class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100" />
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Name</label>
                    <input v-model="form.name" type="text" required class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100" />
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Type</label>
                    <select v-model="form.type" required class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100">
                        <option value="asset">Asset</option>
                        <option value="liability">Liability</option>
                        <option value="equity">Equity</option>
                        <option value="revenue">Revenue</option>
                        <option value="expense">Expense</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Description</label>
                    <textarea v-model="form.description" rows="2" class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100"></textarea>
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
import type { Account } from '@/types';
import { useNotificationStore } from '@/stores/notification';

const notify = useNotificationStore();
const accounts = ref<Account[]>([]);
const showCreateModal = ref(false);
const form = reactive({ code: '', name: '', type: 'asset' as Account['type'], description: '' });

const columns = [
    { key: 'code', label: 'Code', sortable: true },
    { key: 'name', label: 'Name', sortable: true },
    { key: 'type', label: 'Type', sortable: true },
    { key: 'balance', label: 'Balance', sortable: true },
    { key: 'is_active', label: 'Status', sortable: true },
];

function typeClass(type: string): string {
    const classes: Record<string, string> = {
        asset: 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
        liability: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
        equity: 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
        revenue: 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200',
        expense: 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
    };
    return classes[type] || 'bg-gray-100 text-gray-800';
}

async function loadAccounts() {
    try {
        const response = await apiClient.get('/accounts');
        accounts.value = response.data;
    } catch {
        accounts.value = [];
    }
}

async function createAccount() {
    try {
        await apiClient.post('/accounts', form);
        showCreateModal.value = false;
        notify.success('Account created successfully');
        await loadAccounts();
    } catch {
        notify.error('Failed to create account');
    }
}

onMounted(loadAccounts);
</script>