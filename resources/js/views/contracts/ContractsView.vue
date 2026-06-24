<template>
    <div>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100 mb-6">Contracts</h1>

        <DataTable :columns="columns" :data="contracts" searchable>
            <template #header>
                <button @click="openCreate" class="px-4 py-2 bg-blue-600 text-white text-sm rounded-md hover:bg-blue-700">
                    New Contract
                </button>
            </template>
            <template #cell-status="{ value }">
                <span :class="statusClass(value as string)" class="px-2 py-1 text-xs rounded-full font-medium">
                    {{ formatStatus(value as string) }}
                </span>
            </template>
            <template #cell-value="{ value }">
                ${{ Number(value ?? 0).toLocaleString() }}
            </template>
            <template #cell-actions="{ row }">
                <div class="flex gap-2">
                    <button @click="openEdit(row as any)" class="text-xs text-blue-600 dark:text-blue-400 hover:underline">Edit</button>
                    <button
                        v-if="['draft', 'review'].includes((row as any).status)"
                        @click="signContract(row as any)"
                        class="text-xs text-green-600 dark:text-green-400 hover:underline"
                    >Sign</button>
                    <button @click="openDocs(row as any)" class="text-xs text-gray-500 dark:text-gray-400 hover:text-blue-600 dark:hover:text-blue-400">Docs</button>
                </div>
            </template>
        </DataTable>

        <!-- Document Attachment Modal -->
        <ModalDialog v-model="showDocsModal" :title="`Documents — ${selectedContractTitle}`">
            <DocumentAttachmentPanel
                v-if="selectedContractId"
                documentable-type="App\Models\Contracts\Contract"
                :documentable-id="selectedContractId"
            />
        </ModalDialog>

        <ModalDialog v-model="showModal" :title="editingId ? 'Edit Contract' : 'New Contract'">
            <form @submit.prevent="saveContract" class="space-y-4">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="sm:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Title</label>
                        <input v-model="form.title" type="text" required class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Contract Number</label>
                        <input v-model="form.contract_number" type="text" class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Type</label>
                        <select v-model="form.type" class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100">
                            <option value="sale">Sale</option>
                            <option value="purchase">Purchase</option>
                            <option value="employment">Employment</option>
                            <option value="service">Service</option>
                            <option value="lease">Lease</option>
                            <option value="nda">NDA</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Status</label>
                        <select v-model="form.status" class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100">
                            <option value="draft">Draft</option>
                            <option value="review">Review</option>
                            <option value="signed">Signed</option>
                            <option value="active">Active</option>
                            <option value="expired">Expired</option>
                            <option value="terminated">Terminated</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Value ($)</label>
                        <input v-model.number="form.value" type="number" min="0" step="0.01" class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Currency</label>
                        <input v-model="form.currency" type="text" maxlength="3" placeholder="NZD" class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 uppercase" />
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
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Description</label>
                        <textarea v-model="form.description" rows="3" class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100" />
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
import DocumentAttachmentPanel from '@/components/DocumentAttachmentPanel.vue';
import apiClient from '@/api/axios';
import { useNotificationStore } from '@/stores/notification';

const notify = useNotificationStore();

interface Contract {
    id: number;
    title: string;
    contract_number?: string;
    type: string;
    status: string;
    value?: number;
    currency?: string;
    start_date?: string;
    end_date?: string;
    description?: string;
}

const contracts = ref<Contract[]>([]);
const showModal = ref(false);
const saving = ref(false);
const editingId = ref<number | null>(null);

const showDocsModal = ref(false);
const selectedContractId = ref<number | null>(null);
const selectedContractTitle = ref('');

function openDocs(row: any) {
    selectedContractId.value = row.id;
    selectedContractTitle.value = row.title ?? row.contract_number ?? String(row.id);
    showDocsModal.value = true;
}

const defaultForm = () => ({
    title: '', contract_number: '', type: 'sale', status: 'draft',
    value: 0, currency: 'NZD', start_date: '', end_date: '', description: '',
});
const form = reactive(defaultForm());

const columns = [
    { key: 'title', label: 'Title', sortable: true },
    { key: 'contract_number', label: 'Number', sortable: true },
    { key: 'type', label: 'Type', sortable: true },
    { key: 'status', label: 'Status', sortable: true },
    { key: 'value', label: 'Value', sortable: true },
    { key: 'start_date', label: 'Start', sortable: true },
    { key: 'end_date', label: 'End', sortable: true },
    { key: 'actions', label: 'Actions' },
];

function statusClass(status: string): string {
    const classes: Record<string, string> = {
        draft: 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200',
        review: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
        signed: 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
        active: 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
        expired: 'bg-slate-100 text-slate-800 dark:bg-slate-700 dark:text-slate-200',
        terminated: 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
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

function openEdit(contract: Contract) {
    editingId.value = contract.id;
    Object.assign(form, contract);
    showModal.value = true;
}

async function saveContract() {
    saving.value = true;
    try {
        if (editingId.value) {
            await apiClient.put(`/v1/contracts/${editingId.value}`, form);
            notify.success('Contract updated');
        } else {
            await apiClient.post('/v1/contracts', form);
            notify.success('Contract created');
        }
        showModal.value = false;
        await load();
    } catch {
        notify.error('Failed to save contract');
    } finally {
        saving.value = false;
    }
}

async function signContract(contract: Contract) {
    if (!confirm(`Sign contract "${contract.title}"?`)) return;
    try {
        await apiClient.post(`/v1/contracts/${contract.id}/sign`);
        notify.success('Contract signed');
        await load();
    } catch {
        notify.error('Failed to sign contract');
    }
}

async function load() {
    try {
        const res = await apiClient.get('/v1/contracts');
        contracts.value = res.data?.data ?? [];
    } catch {
        contracts.value = [];
    }
}

onMounted(load);
</script>
