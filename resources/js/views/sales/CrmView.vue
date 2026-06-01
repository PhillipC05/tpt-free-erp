<template>
    <div>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100 mb-6">CRM Pipeline</h1>
        <DataTable :columns="columns" :data="deals" searchable>
            <template #header>
                <button @click="showCreateModal = true" class="px-4 py-2 bg-blue-600 text-white text-sm rounded-md hover:bg-blue-700">
                    Add Deal
                </button>
            </template>
            <template #cell-stage="{ value }">
                <span :class="stageClass(value as string)" class="px-2 py-1 text-xs rounded-full font-medium">
                    {{ formatStage(value as string) }}
                </span>
            </template>
            <template #cell-value="{ value }">
                ${{ Number(value).toLocaleString() }}
            </template>
            <template #cell-probability="{ value }">
                {{ value }}%
            </template>
        </DataTable>

        <ModalDialog v-model="showCreateModal" title="Add Deal">
            <form @submit.prevent="createDeal" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Deal Name</label>
                    <input v-model="form.name" type="text" required class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100" />
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Stage</label>
                    <select v-model="form.stage" required class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100">
                        <option value="lead">Lead</option>
                        <option value="prospect">Prospect</option>
                        <option value="proposal">Proposal</option>
                        <option value="negotiation">Negotiation</option>
                        <option value="closed_won">Closed Won</option>
                        <option value="closed_lost">Closed Lost</option>
                    </select>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Value ($)</label>
                        <input v-model.number="form.value" type="number" min="0" step="0.01" class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Probability (%)</label>
                        <input v-model.number="form.probability" type="number" min="0" max="100" class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100" />
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Expected Close Date</label>
                    <input v-model="form.expected_close_date" type="date" class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100" />
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
import type { CrmPipeline } from '@/types';
import { useNotificationStore } from '@/stores/notification';

const notify = useNotificationStore();
const deals = ref<CrmPipeline[]>([]);
const showCreateModal = ref(false);
const form = reactive({ name: '', stage: 'lead' as CrmPipeline['stage'], value: 0, probability: 50, expected_close_date: '' });

const columns = [
    { key: 'name', label: 'Deal Name', sortable: true },
    { key: 'stage', label: 'Stage', sortable: true },
    { key: 'value', label: 'Value', sortable: true },
    { key: 'probability', label: 'Probability', sortable: true },
    { key: 'expected_close_date', label: 'Close Date', sortable: true },
];

function stageClass(stage: string): string {
    const classes: Record<string, string> = {
        lead: 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200',
        prospect: 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
        proposal: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
        negotiation: 'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200',
        closed_won: 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
        closed_lost: 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
    };
    return classes[stage] || 'bg-gray-100 text-gray-800';
}

function formatStage(stage: string): string {
    return stage.replace(/_/g, ' ').replace(/\b\w/g, (c) => c.toUpperCase());
}

async function loadDeals() {
    try {
        const res = await apiClient.get('/sales/crm');
        deals.value = res.data?.data ?? res.data ?? [];
    } catch {
        deals.value = [];
    }
}

async function createDeal() {
    try {
        await apiClient.post('/sales/crm', form);
        showCreateModal.value = false;
        notify.success('Deal added successfully');
        await loadDeals();
    } catch {
        notify.error('Failed to add deal');
    }
}

onMounted(loadDeals);
</script>
