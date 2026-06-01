<template>
    <div>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100 mb-6">Transactions</h1>
        <DataTable
            :columns="columns"
            :data="transactions"
            searchable
        >
            <template #header>
                <button @click="showCreateModal = true" class="px-4 py-2 bg-blue-600 text-white text-sm rounded-md hover:bg-blue-700">
                    Add Transaction
                </button>
            </template>
            <template #cell-account_id="{ row }">
                {{ (row as Record<string, unknown>).account ? ((row as Record<string, unknown>).account as Record<string, unknown>).name : '' }}
            </template>
            <template #cell-amount="{ value }">
                ${{ Number(value).toLocaleString() }}
            </template>
        </DataTable>
    </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';
import DataTable from '@/components/DataTable.vue';
import apiClient from '@/api/axios';
import type { Transaction } from '@/types';

const transactions = ref<Transaction[]>([]);

const columns = [
    { key: 'id', label: 'ID', sortable: true },
    { key: 'account_id', label: 'Account', sortable: true },
    { key: 'type', label: 'Type', sortable: true },
    { key: 'amount', label: 'Amount', sortable: true },
    { key: 'description', label: 'Description' },
    { key: 'date', label: 'Date', sortable: true },
];

async function loadTransactions() {
    try {
        const response = await apiClient.get('/finance/transactions');
        transactions.value = response.data.data ?? response.data;
    } catch {
        transactions.value = [];
    }
}

onMounted(loadTransactions);
</script>