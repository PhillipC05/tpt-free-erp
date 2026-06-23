<template>
    <div>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100 mb-6">Campaigns</h1>

        <DataTable :columns="columns" :data="campaigns" searchable>
            <template #header>
                <button @click="openCreate" class="px-4 py-2 bg-blue-600 text-white text-sm rounded-md hover:bg-blue-700">
                    New Campaign
                </button>
            </template>
            <template #cell-type="{ value }">
                <span class="px-2 py-1 text-xs rounded-full font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                    {{ value }}
                </span>
            </template>
            <template #cell-status="{ value }">
                <span :class="statusClass(value as string)" class="px-2 py-1 text-xs rounded-full font-medium">
                    {{ formatStatus(value as string) }}
                </span>
            </template>
            <template #cell-budget="{ value }">
                ${{ Number(value).toLocaleString() }}
            </template>
            <template #cell-actual_spend="{ value }">
                ${{ Number(value ?? 0).toLocaleString() }}
            </template>
            <template #cell-actions="{ row }">
                <div class="flex gap-2">
                    <button @click="openEdit(row as any)" class="text-xs text-blue-600 dark:text-blue-400 hover:underline">Edit</button>
                    <button @click="deleteCampaign(row as any)" class="text-xs text-red-600 dark:text-red-400 hover:underline">Delete</button>
                </div>
            </template>
        </DataTable>

        <ModalDialog v-model="showModal" :title="editingId ? 'Edit Campaign' : 'New Campaign'">
            <form @submit.prevent="saveCampaign" class="space-y-4">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Name</label>
                        <input v-model="form.name" type="text" required class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Code</label>
                        <input v-model="form.code" type="text" class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Type</label>
                        <select v-model="form.type" class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100">
                            <option value="email">Email</option>
                            <option value="social">Social</option>
                            <option value="paid_ads">Paid Ads</option>
                            <option value="event">Event</option>
                            <option value="content">Content</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Status</label>
                        <select v-model="form.status" class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100">
                            <option value="draft">Draft</option>
                            <option value="active">Active</option>
                            <option value="paused">Paused</option>
                            <option value="completed">Completed</option>
                            <option value="archived">Archived</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Budget ($)</label>
                        <input v-model.number="form.budget" type="number" min="0" step="0.01" class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Start Date</label>
                        <input v-model="form.start_date" type="date" class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">End Date</label>
                        <input v-model="form.end_date" type="date" class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100" />
                    </div>
                </div>
                <div class="flex justify-end gap-2">
                    <button type="button" @click="showModal = false" class="px-4 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-md text-gray-700 dark:text-gray-300">Cancel</button>
                    <button type="submit" :disabled="saving" class="px-4 py-2 bg-blue-600 text-white text-sm rounded-md hover:bg-blue-700 disabled:opacity-50">
                        {{ saving ? 'Saving...' : 'Save' }}
                    </button>
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
import { useNotificationStore } from '@/stores/notification';

const notify = useNotificationStore();

interface Campaign {
    id: number;
    name: string;
    code?: string;
    type: string;
    status: string;
    budget: number;
    actual_spend?: number;
    start_date?: string;
    end_date?: string;
}

const campaigns = ref<Campaign[]>([]);
const showModal = ref(false);
const saving = ref(false);
const editingId = ref<number | null>(null);

const defaultForm = () => ({
    name: '', code: '', type: 'email', status: 'draft',
    budget: 0, start_date: '', end_date: '',
});
const form = reactive(defaultForm());

const columns = [
    { key: 'name', label: 'Name', sortable: true },
    { key: 'type', label: 'Type', sortable: true },
    { key: 'status', label: 'Status', sortable: true },
    { key: 'budget', label: 'Budget', sortable: true },
    { key: 'actual_spend', label: 'Actual Spend', sortable: true },
    { key: 'start_date', label: 'Start', sortable: true },
    { key: 'end_date', label: 'End', sortable: true },
    { key: 'actions', label: 'Actions' },
];

function statusClass(status: string): string {
    const classes: Record<string, string> = {
        draft: 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200',
        active: 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
        paused: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
        completed: 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
        archived: 'bg-slate-100 text-slate-800 dark:bg-slate-700 dark:text-slate-200',
    };
    return classes[status] || 'bg-gray-100 text-gray-800';
}

function formatStatus(status: string): string {
    return status.charAt(0).toUpperCase() + status.slice(1);
}

function openCreate() {
    editingId.value = null;
    Object.assign(form, defaultForm());
    showModal.value = true;
}

function openEdit(campaign: Campaign) {
    editingId.value = campaign.id;
    Object.assign(form, campaign);
    showModal.value = true;
}

async function saveCampaign() {
    saving.value = true;
    try {
        if (editingId.value) {
            await apiClient.put(`/v1/marketing/campaigns/${editingId.value}`, form);
            notify.success('Campaign updated');
        } else {
            await apiClient.post('/v1/marketing/campaigns', form);
            notify.success('Campaign created');
        }
        showModal.value = false;
        await load();
    } catch {
        notify.error('Failed to save campaign');
    } finally {
        saving.value = false;
    }
}

async function deleteCampaign(campaign: Campaign) {
    if (!confirm(`Delete campaign "${campaign.name}"?`)) return;
    try {
        await apiClient.delete(`/v1/marketing/campaigns/${campaign.id}`);
        notify.success('Campaign deleted');
        await load();
    } catch {
        notify.error('Failed to delete campaign');
    }
}

async function load() {
    try {
        const res = await apiClient.get('/v1/marketing/campaigns');
        campaigns.value = res.data?.data ?? [];
    } catch {
        campaigns.value = [];
    }
}

onMounted(load);
</script>
