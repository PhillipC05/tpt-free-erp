<template>
    <div>
        <div class="flex items-center justify-between mb-6">
            <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">POS Transactions</h1>
            <button @click="router.push('/pos/register')" class="px-4 py-2 bg-green-600 text-white text-sm rounded-md hover:bg-green-700">
                Open Register
            </button>
        </div>

        <div class="grid grid-cols-4 gap-4 mb-6">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
                <div class="text-sm text-gray-500 dark:text-gray-400">Completed</div>
                <div class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ summary.completed_transactions }}</div>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
                <div class="text-sm text-gray-500 dark:text-gray-400">Revenue</div>
                <div class="text-2xl font-bold text-green-600">${{ Number(summary.total_revenue).toFixed(2) }}</div>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
                <div class="text-sm text-gray-500 dark:text-gray-400">Tax</div>
                <div class="text-2xl font-bold text-gray-900 dark:text-gray-100">${{ Number(summary.total_tax).toFixed(2) }}</div>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
                <div class="text-sm text-gray-500 dark:text-gray-400">Voided</div>
                <div class="text-2xl font-bold text-red-600">{{ summary.voided_transactions }}</div>
            </div>
        </div>

        <DataTable :columns="columns" :data="transactions" searchable>
            <template #cell-status="{ value }">
                <span :class="{
                    'text-blue-600': value === 'open',
                    'text-green-600': value === 'completed',
                    'text-red-600': value === 'voided',
                    'text-yellow-600': value === 'refunded'
                }" class="text-xs font-medium capitalize">{{ value }}</span>
            </template>
            <template #cell-total_amount="{ value }">
                ${{ Number(value).toFixed(2) }}
            </template>
            <template #cell-created_at="{ value }">
                {{ new Date(value).toLocaleString() }}
            </template>
        </DataTable>
    </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import DataTable from '@/components/DataTable.vue';
import apiClient from '@/api/axios';
import type { PosTransaction, PosSummary } from '@/types';

const router = useRouter();
const transactions = ref<PosTransaction[]>([]);
const summary = ref<PosSummary>({
    completed_transactions: 0,
    voided_transactions: 0,
    total_revenue: 0,
    total_tax: 0,
    total_discounts: 0,
    payment_breakdown: [],
});

const columns = [
    { key: 'transaction_number', label: 'Transaction #', sortable: true },
    { key: 'status', label: 'Status', sortable: true },
    { key: 'total_amount', label: 'Total', sortable: true },
    { key: 'terminal.name', label: 'Terminal', sortable: false },
    { key: 'customer.name', label: 'Customer', sortable: false },
    { key: 'created_at', label: 'Date', sortable: true },
];

async function loadData() {
    try {
        const [txRes, sumRes] = await Promise.all([
            apiClient.get('/pos/transactions'),
            apiClient.get('/pos/transactions/summary'),
        ]);
        transactions.value = txRes.data?.data ?? txRes.data ?? [];
        summary.value = sumRes.data?.data ?? summary.value;
    } catch {
        transactions.value = [];
    }
}

onMounted(loadData);
</script>
