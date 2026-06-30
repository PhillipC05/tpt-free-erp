<template>
    <div>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100 mb-6">Grants</h1>

        <DataTable :columns="columns" :data="grants" searchable>
            <template #header>
                <button @click="openCreate" class="px-4 py-2 bg-blue-600 text-white text-sm rounded-md hover:bg-blue-700">
                    New Grant
                </button>
            </template>
            <template #cell-status="{ value }">
                <span :class="statusClass(value as string)" class="px-2 py-1 text-xs rounded-full font-medium">
                    {{ formatStatus(value as string) }}
                </span>
            </template>
            <template #cell-amount="{ value }">
                ${{ Number(value ?? 0).toLocaleString() }}
            </template>
            <template #cell-spent_amount="{ value }">
                ${{ Number(value ?? 0).toLocaleString() }}
            </template>
            <template #cell-actions="{ row }">
                <div class="flex gap-2">
                    <button @click="openEdit(row as any)" class="text-xs text-blue-600 dark:text-blue-400 hover:underline">Edit</button>
                    <button @click="openDisbursements(row as any)" class="text-xs text-purple-600 dark:text-purple-400 hover:underline">Disburse</button>
                    <button
                        v-if="['active', 'approved'].includes((row as any).status)"
                        @click="closeGrant(row as any)"
                        class="text-xs text-orange-600 dark:text-orange-400 hover:underline"
                    >Close</button>
                </div>
            </template>
        </DataTable>

        <!-- Create/Edit Modal -->
        <ModalDialog v-model="showModal" :title="editingId ? 'Edit Grant' : 'New Grant'">
            <form @submit.prevent="saveGrant" class="space-y-4">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="sm:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Title</label>
                        <input v-model="form.title" type="text" required class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Grant Number</label>
                        <input v-model="form.grant_number" type="text" class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Donor</label>
                        <select v-model="form.donor_id" class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100">
                            <option value="">None</option>
                            <option v-for="d in donorList" :key="d.id" :value="d.id">{{ d.name }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Amount ($)</label>
                        <input v-model.number="form.amount" type="number" min="0" step="0.01" required class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Status</label>
                        <select v-model="form.status" class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100">
                            <option value="draft">Draft</option>
                            <option value="submitted">Submitted</option>
                            <option value="approved">Approved</option>
                            <option value="active">Active</option>
                            <option value="closed">Closed</option>
                            <option value="rejected">Rejected</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Start Date</label>
                        <input v-model="form.start_date" type="date" class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">End Date</label>
                        <input v-model="form.end_date" type="date" class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100" />
                    </div>
                    <div class="sm:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Purpose</label>
                        <textarea v-model="form.purpose" rows="2" class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100" />
                    </div>
                    <div class="sm:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Requirements</label>
                        <textarea v-model="form.requirements" rows="2" class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100" />
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

        <!-- Disbursements Modal -->
        <ModalDialog v-model="showDisburseModal" :title="`Disbursements — ${selectedGrantTitle}`">
            <div v-if="selectedGrant" class="space-y-4">
                <div class="flex items-center justify-between text-sm text-gray-600 dark:text-gray-400">
                    <span>Funded: ${{ Number(selectedGrant.funded_amount ?? 0).toLocaleString() }}</span>
                    <span>Spent: ${{ Number(selectedGrant.spent_amount ?? 0).toLocaleString() }}</span>
                    <span class="font-medium text-gray-900 dark:text-gray-100">
                        Remaining: ${{ (Number(selectedGrant.funded_amount ?? 0) - Number(selectedGrant.spent_amount ?? 0)).toLocaleString() }}
                    </span>
                </div>

                <div v-if="disbursements.length === 0" class="py-4 text-center text-sm text-gray-500 dark:text-gray-400">
                    No disbursements recorded yet.
                </div>
                <div v-else class="divide-y divide-gray-200 dark:divide-gray-700">
                    <div v-for="d in disbursements" :key="d.id" class="py-2 flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-900 dark:text-gray-100">${{ Number(d.amount).toLocaleString() }}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ d.description || 'No description' }}</p>
                        </div>
                        <span class="text-xs text-gray-400 dark:text-gray-500">{{ d.disbursement_date }}</span>
                    </div>
                </div>

                <form v-if="selectedGrant?.status === 'active'" @submit.prevent="addDisbursement" class="border-t border-gray-200 dark:border-gray-700 pt-4 space-y-3">
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Amount ($)</label>
                            <input v-model.number="disbursementForm.amount" type="number" min="0.01" step="0.01" required class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 text-sm" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Date</label>
                            <input v-model="disbursementForm.disbursement_date" type="date" required class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 text-sm" />
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Description</label>
                        <input v-model="disbursementForm.description" type="text" class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 text-sm" />
                    </div>
                    <button type="submit" :disabled="savingDisbursement" class="w-full px-4 py-2 bg-green-600 text-white text-sm rounded-md hover:bg-green-700 disabled:opacity-50">
                        {{ savingDisbursement ? 'Recording...' : 'Record Disbursement' }}
                    </button>
                </form>
            </div>
        </ModalDialog>
    </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted } from 'vue';
import { useRoute } from 'vue-router';
import DataTable from '@/components/DataTable.vue';
import ModalDialog from '@/components/ModalDialog.vue';
import apiClient from '@/api/axios';
import { useNotificationStore } from '@/stores/notification';

const notify = useNotificationStore();
const route = useRoute();

interface Grant {
    id: number;
    title: string;
    grant_number?: string;
    amount: number;
    status: string;
    donor_id?: number;
    donor?: { id: number; name: string };
    start_date?: string;
    end_date?: string;
    purpose?: string;
    requirements?: string;
    funded_amount: number;
    spent_amount: number;
}

interface DonorOption {
    id: number;
    name: string;
}

interface Disbursement {
    id: number;
    amount: number;
    description?: string;
    disbursement_date: string;
}

const grants = ref<Grant[]>([]);
const donorList = ref<DonorOption[]>([]);
const showModal = ref(false);
const saving = ref(false);
const editingId = ref<number | null>(null);

const showDisburseModal = ref(false);
const selectedGrant = ref<Grant | null>(null);
const selectedGrantTitle = ref('');
const disbursements = ref<Disbursement[]>([]);
const savingDisbursement = ref(false);

const defaultForm = () => ({
    title: '', grant_number: '', amount: 0, donor_id: '',
    status: 'draft', start_date: '', end_date: '',
    purpose: '', requirements: '',
});
const form = reactive(defaultForm());

const disbursementForm = reactive({
    amount: 0,
    description: '',
    disbursement_date: new Date().toISOString().split('T')[0],
});

const columns = [
    { key: 'title', label: 'Title', sortable: true },
    { key: 'grant_number', label: 'Number', sortable: true },
    { key: 'amount', label: 'Amount', sortable: true },
    { key: 'status', label: 'Status', sortable: true },
    { key: 'start_date', label: 'Start' },
    { key: 'end_date', label: 'End' },
    { key: 'actions', label: 'Actions' },
];

function statusClass(status: string): string {
    const classes: Record<string, string> = {
        draft: 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200',
        submitted: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
        approved: 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
        active: 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
        closed: 'bg-slate-100 text-slate-800 dark:bg-slate-700 dark:text-slate-200',
        rejected: 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
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

function openEdit(grant: Grant) {
    editingId.value = grant.id;
    Object.assign(form, {
        title: grant.title,
        grant_number: grant.grant_number ?? '',
        amount: grant.amount,
        donor_id: grant.donor_id ?? '',
        status: grant.status,
        start_date: grant.start_date ?? '',
        end_date: grant.end_date ?? '',
        purpose: grant.purpose ?? '',
        requirements: grant.requirements ?? '',
    });
    showModal.value = true;
}

async function openDisbursements(grant: Grant) {
    selectedGrant.value = grant;
    selectedGrantTitle.value = grant.title;
    disbursementForm.amount = 0;
    disbursementForm.description = '';
    disbursementForm.disbursement_date = new Date().toISOString().split('T')[0];
    showDisburseModal.value = true;
    await loadDisbursements(grant.id);
}

async function loadDisbursements(grantId: number) {
    try {
        const res = await apiClient.get(`/v1/grants/${grantId}/disbursements`);
        disbursements.value = res.data?.data ?? [];
    } catch {
        disbursements.value = [];
    }
}

async function saveGrant() {
    saving.value = true;
    try {
        const payload = { ...form };
        if (!payload.donor_id) delete payload.donor_id;
        if (editingId.value) {
            await apiClient.put(`/v1/grants/${editingId.value}`, payload);
            notify.success('Grant updated');
        } else {
            await apiClient.post('/v1/grants', payload);
            notify.success('Grant created');
        }
        showModal.value = false;
        await load();
    } catch {
        notify.error('Failed to save grant');
    } finally {
        saving.value = false;
    }
}

async function addDisbursement() {
    if (!selectedGrant.value) return;
    savingDisbursement.value = true;
    try {
        await apiClient.post(`/v1/grants/${selectedGrant.value.id}/disbursements`, disbursementForm);
        notify.success('Disbursement recorded');
        disbursementForm.amount = 0;
        disbursementForm.description = '';
        await loadDisbursements(selectedGrant.value.id);
        await load();
    } catch {
        notify.error('Failed to record disbursement');
    } finally {
        savingDisbursement.value = false;
    }
}

async function closeGrant(grant: Grant) {
    if (!confirm(`Close grant "${grant.title}"?`)) return;
    try {
        await apiClient.post(`/v1/grants/${grant.id}/close`);
        notify.success('Grant closed');
        await load();
    } catch {
        notify.error('Failed to close grant');
    }
}

async function load() {
    try {
        const params: Record<string, string> = {};
        if (route.query.donor_id) params.donor_id = route.query.donor_id as string;
        const res = await apiClient.get('/v1/grants', { params });
        grants.value = res.data?.data ?? [];
    } catch {
        grants.value = [];
    }
}

async function loadDonors() {
    try {
        const res = await apiClient.get('/v1/donors', { params: { per_page: 100 } });
        donorList.value = (res.data?.data ?? []).map((d: any) => ({ id: d.id, name: d.name }));
    } catch {
        donorList.value = [];
    }
}

onMounted(() => {
    load();
    loadDonors();
});
</script>
