<template>
    <div>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100 mb-6">Leads</h1>

        <DataTable :columns="columns" :data="leads" searchable>
            <template #header>
                <button @click="openCreate" class="px-4 py-2 bg-blue-600 text-white text-sm rounded-md hover:bg-blue-700">
                    New Lead
                </button>
            </template>
            <template #cell-name="{ row }">
                {{ (row as any).first_name }} {{ (row as any).last_name }}
            </template>
            <template #cell-source="{ value }">
                <span class="px-2 py-1 text-xs rounded-full font-medium bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200">
                    {{ value }}
                </span>
            </template>
            <template #cell-status="{ value }">
                <span :class="statusClass(value as string)" class="px-2 py-1 text-xs rounded-full font-medium">
                    {{ formatStatus(value as string) }}
                </span>
            </template>
            <template #cell-interest_score="{ value }">
                <div class="flex items-center gap-2">
                    <div class="flex-1 bg-gray-200 dark:bg-gray-700 rounded-full h-1.5 min-w-16">
                        <div
                            class="bg-blue-600 h-1.5 rounded-full"
                            :style="{ width: `${Math.min(100, Number(value ?? 0))}%` }"
                        />
                    </div>
                    <span class="text-xs text-gray-500 dark:text-gray-400 w-8">{{ value ?? 0 }}</span>
                </div>
            </template>
            <template #cell-actions="{ row }">
                <div class="flex gap-2">
                    <button @click="openEdit(row as any)" class="text-xs text-blue-600 dark:text-blue-400 hover:underline">Edit</button>
                    <button @click="convertLead(row as any)" class="text-xs text-green-600 dark:text-green-400 hover:underline">Convert</button>
                </div>
            </template>
        </DataTable>

        <ModalDialog v-model="showModal" :title="editingId ? 'Edit Lead' : 'New Lead'">
            <form @submit.prevent="saveLead" class="space-y-4">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">First Name</label>
                        <input v-model="form.first_name" type="text" required class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Last Name</label>
                        <input v-model="form.last_name" type="text" required class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Email</label>
                        <input v-model="form.email" type="email" class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Phone</label>
                        <input v-model="form.phone" type="tel" class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Company</label>
                        <input v-model="form.company" type="text" class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Job Title</label>
                        <input v-model="form.job_title" type="text" class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Source</label>
                        <select v-model="form.source" class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100">
                            <option value="website">Website</option>
                            <option value="referral">Referral</option>
                            <option value="cold_outreach">Cold Outreach</option>
                            <option value="social">Social Media</option>
                            <option value="event">Event</option>
                            <option value="ad">Advertisement</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Status</label>
                        <select v-model="form.status" class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100">
                            <option value="new">New</option>
                            <option value="contacted">Contacted</option>
                            <option value="qualified">Qualified</option>
                            <option value="nurturing">Nurturing</option>
                            <option value="converted">Converted</option>
                            <option value="dead">Dead</option>
                        </select>
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Notes</label>
                    <textarea v-model="form.notes" rows="2" class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100" />
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

interface Lead {
    id: number;
    first_name: string;
    last_name: string;
    email?: string;
    phone?: string;
    company?: string;
    job_title?: string;
    source: string;
    status: string;
    interest_score?: number;
    assigned_to?: string;
    notes?: string;
}

const leads = ref<Lead[]>([]);
const showModal = ref(false);
const saving = ref(false);
const editingId = ref<number | null>(null);

const defaultForm = () => ({
    first_name: '', last_name: '', email: '', phone: '',
    company: '', job_title: '', source: 'website', status: 'new', notes: '',
});
const form = reactive(defaultForm());

const columns = [
    { key: 'name', label: 'Name', sortable: false },
    { key: 'company', label: 'Company', sortable: true },
    { key: 'source', label: 'Source', sortable: true },
    { key: 'status', label: 'Status', sortable: true },
    { key: 'interest_score', label: 'Interest Score', sortable: true },
    { key: 'assigned_to', label: 'Assigned To', sortable: true },
    { key: 'actions', label: 'Actions' },
];

function statusClass(status: string): string {
    const classes: Record<string, string> = {
        new: 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
        contacted: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
        qualified: 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
        nurturing: 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200',
        converted: 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900 dark:text-emerald-200',
        dead: 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200',
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

function openEdit(lead: Lead) {
    editingId.value = lead.id;
    Object.assign(form, lead);
    showModal.value = true;
}

async function saveLead() {
    saving.value = true;
    try {
        if (editingId.value) {
            await apiClient.put(`/v1/marketing/leads/${editingId.value}`, form);
            notify.success('Lead updated');
        } else {
            await apiClient.post('/v1/marketing/leads', form);
            notify.success('Lead created');
        }
        showModal.value = false;
        await load();
    } catch {
        notify.error('Failed to save lead');
    } finally {
        saving.value = false;
    }
}

async function convertLead(lead: Lead) {
    if (!confirm(`Convert "${lead.first_name} ${lead.last_name}" to a customer?`)) return;
    try {
        await apiClient.post(`/v1/marketing/leads/${lead.id}/convert`);
        notify.success('Lead converted to customer');
        await load();
    } catch {
        notify.error('Failed to convert lead');
    }
}

async function load() {
    try {
        const res = await apiClient.get('/v1/marketing/leads');
        leads.value = res.data?.data ?? [];
    } catch {
        leads.value = [];
    }
}

onMounted(load);
</script>
