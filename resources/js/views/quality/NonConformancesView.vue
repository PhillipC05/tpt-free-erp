<template>
    <div>
        <div class="flex items-center justify-between mb-6">
            <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">Non-Conformances</h1>
            <button @click="openCreate" class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-md transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                New NC
            </button>
        </div>

        <!-- Status summary chips -->
        <div class="flex flex-wrap gap-2 mb-6">
            <button
                v-for="s in statusFilters"
                :key="s.value"
                @click="statusFilter = statusFilter === s.value ? '' : s.value"
                :class="['text-xs font-medium px-3 py-1 rounded-full border transition-colors', statusFilter === s.value ? s.activeClass : 'border-gray-300 dark:border-gray-600 text-gray-600 dark:text-gray-400 hover:border-gray-400']"
            >{{ s.label }} ({{ countByStatus(s.value) }})</button>
        </div>

        <DataTable :columns="columns" :data="filteredRecords" searchable>
            <template #cell-status="{ value, row }">
                <div class="flex items-center gap-2">
                    <span :class="statusClass(value as string)" class="px-2 py-0.5 text-xs rounded-full font-medium capitalize">{{ value }}</span>
                    <select
                        :value="value"
                        @change="quickStatus(row, ($event.target as HTMLSelectElement).value)"
                        class="text-xs border border-gray-300 dark:border-gray-600 rounded px-1 py-0.5 bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-300"
                    >
                        <option v-for="opt in statusOptions" :key="opt" :value="opt">{{ opt }}</option>
                    </select>
                </div>
            </template>
            <template #cell-severity="{ value }">
                <span :class="severityClass(value as string)" class="px-2 py-0.5 text-xs rounded-full font-medium capitalize">{{ value }}</span>
            </template>
            <template #cell-nc_number="{ value }">
                <span class="font-mono text-xs text-gray-600 dark:text-gray-400">{{ value }}</span>
            </template>
            <template #actions="{ row }">
                <div class="flex items-center gap-2">
                    <button @click="openEdit(row)" class="text-xs text-blue-600 dark:text-blue-400 hover:underline">Edit</button>
                    <button @click="confirmDelete(row)" class="text-xs text-red-600 dark:text-red-400 hover:underline">Delete</button>
                </div>
            </template>
        </DataTable>

        <!-- Create / Edit Modal -->
        <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-black/50" @click="closeModal" />
            <div class="relative bg-white dark:bg-gray-800 rounded-xl shadow-xl w-full max-w-2xl max-h-[90vh] overflow-y-auto">
                <div class="sticky top-0 flex items-center justify-between px-6 py-4 border-b border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100">{{ editing ? 'Edit Non-Conformance' : 'New Non-Conformance' }}</h2>
                    <button @click="closeModal" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <form @submit.prevent="save" class="px-6 py-4 space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">NC Number <span class="text-red-500">*</span></label>
                            <input type="text" v-model="form.nc_number" required maxlength="50" class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-sm text-gray-900 dark:text-gray-100 px-3 py-2 focus:outline-none focus:ring-1 focus:ring-blue-500" placeholder="NC-2024-001" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Severity <span class="text-red-500">*</span></label>
                            <select v-model="form.severity" required class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-sm text-gray-900 dark:text-gray-100 px-3 py-2 focus:outline-none focus:ring-1 focus:ring-blue-500">
                                <option value="">Select severity...</option>
                                <option value="minor">Minor</option>
                                <option value="major">Major</option>
                                <option value="critical">Critical</option>
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Description <span class="text-red-500">*</span></label>
                        <textarea v-model="form.description" rows="3" required class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-sm text-gray-900 dark:text-gray-100 px-3 py-2 focus:outline-none focus:ring-1 focus:ring-blue-500 resize-none" placeholder="Describe the non-conformance..."></textarea>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Status</label>
                            <select v-model="form.status" class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-sm text-gray-900 dark:text-gray-100 px-3 py-2 focus:outline-none focus:ring-1 focus:ring-blue-500">
                                <option value="open">Open</option>
                                <option value="investigating">Investigating</option>
                                <option value="resolved">Resolved</option>
                                <option value="closed">Closed</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Target Resolution Date</label>
                            <input type="date" v-model="form.target_resolution_date" class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-sm text-gray-900 dark:text-gray-100 px-3 py-2 focus:outline-none focus:ring-1 focus:ring-blue-500" />
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Root Cause</label>
                        <textarea v-model="form.root_cause" rows="2" class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-sm text-gray-900 dark:text-gray-100 px-3 py-2 focus:outline-none focus:ring-1 focus:ring-blue-500 resize-none" placeholder="What caused this issue?"></textarea>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Corrective Action</label>
                        <textarea v-model="form.corrective_action" rows="2" class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-sm text-gray-900 dark:text-gray-100 px-3 py-2 focus:outline-none focus:ring-1 focus:ring-blue-500 resize-none" placeholder="What actions will be taken?"></textarea>
                    </div>

                    <div v-if="formError" class="text-sm text-red-600 dark:text-red-400">{{ formError }}</div>

                    <div class="flex justify-end gap-3 pt-2">
                        <button type="button" @click="closeModal" class="px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-md transition-colors">Cancel</button>
                        <button type="submit" :disabled="saving" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 disabled:opacity-60 text-white text-sm font-medium rounded-md transition-colors">
                            {{ saving ? 'Saving...' : (editing ? 'Save Changes' : 'Create NC') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Delete confirm -->
        <div v-if="deleteTarget" class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-black/50" @click="deleteTarget = null" />
            <div class="relative bg-white dark:bg-gray-800 rounded-xl shadow-xl w-full max-w-sm p-6 text-center">
                <p class="text-gray-900 dark:text-gray-100 font-medium mb-1">Delete non-conformance?</p>
                <p class="text-sm text-gray-500 dark:text-gray-400 mb-6">{{ deleteTarget.nc_number }} — {{ deleteTarget.description?.substring(0, 60) }}</p>
                <div class="flex justify-center gap-3">
                    <button @click="deleteTarget = null" class="px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-md transition-colors">Cancel</button>
                    <button @click="doDelete" :disabled="saving" class="px-4 py-2 bg-red-600 hover:bg-red-700 disabled:opacity-60 text-white text-sm font-medium rounded-md transition-colors">Delete</button>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import DataTable from '@/components/DataTable.vue';
import api from '@/api/axios';
import { useNotificationStore } from '@/stores/notification';

const notify = useNotificationStore();

const records = ref<any[]>([]);
const showModal = ref(false);
const editing = ref<any>(null);
const deleteTarget = ref<any>(null);
const saving = ref(false);
const formError = ref('');
const statusFilter = ref('');

const statusOptions = ['open', 'investigating', 'resolved', 'closed'];

const statusFilters = [
    { value: 'open', label: 'Open', activeClass: 'bg-red-100 border-red-300 text-red-700 dark:bg-red-900/30 dark:border-red-700 dark:text-red-300' },
    { value: 'investigating', label: 'Investigating', activeClass: 'bg-yellow-100 border-yellow-300 text-yellow-700 dark:bg-yellow-900/30 dark:border-yellow-700 dark:text-yellow-300' },
    { value: 'resolved', label: 'Resolved', activeClass: 'bg-blue-100 border-blue-300 text-blue-700 dark:bg-blue-900/30 dark:border-blue-700 dark:text-blue-300' },
    { value: 'closed', label: 'Closed', activeClass: 'bg-green-100 border-green-300 text-green-700 dark:bg-green-900/30 dark:border-green-700 dark:text-green-300' },
];

const blankForm = () => ({
    nc_number: '',
    description: '',
    severity: '',
    status: 'open',
    root_cause: '',
    corrective_action: '',
    target_resolution_date: '',
});

const form = ref(blankForm());

const columns = [
    { key: 'nc_number', label: 'NC #', sortable: true },
    { key: 'description', label: 'Description', sortable: false },
    { key: 'severity', label: 'Severity', sortable: true },
    { key: 'status', label: 'Status', sortable: true },
    { key: 'target_resolution_date', label: 'Target Date', sortable: true },
];

const filteredRecords = computed(() =>
    statusFilter.value ? records.value.filter(r => r.status === statusFilter.value) : records.value
);

function countByStatus(s: string) {
    return records.value.filter(r => r.status === s).length;
}

function statusClass(status: string): string {
    const map: Record<string, string> = {
        open: 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300',
        investigating: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300',
        resolved: 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300',
        closed: 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300',
    };
    return map[status] ?? 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300';
}

function severityClass(severity: string): string {
    const map: Record<string, string> = {
        minor: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300',
        major: 'bg-orange-100 text-orange-800 dark:bg-orange-900/30 dark:text-orange-300',
        critical: 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300',
    };
    return map[severity] ?? 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300';
}

onMounted(async () => {
    await loadRecords();
});

async function loadRecords() {
    try {
        const res = await api.get('/quality/non-conformances');
        records.value = res.data?.data ?? res.data ?? [];
    } catch {
        notify.error('Failed to load non-conformances');
    }
}

function openCreate() {
    editing.value = null;
    form.value = blankForm();
    formError.value = '';
    showModal.value = true;
}

function openEdit(row: any) {
    editing.value = row;
    form.value = {
        nc_number: row.nc_number ?? '',
        description: row.description ?? '',
        severity: row.severity ?? '',
        status: row.status ?? 'open',
        root_cause: row.root_cause ?? '',
        corrective_action: row.corrective_action ?? '',
        target_resolution_date: row.target_resolution_date ?? '',
    };
    formError.value = '';
    showModal.value = true;
}

function closeModal() {
    showModal.value = false;
    editing.value = null;
}

async function save() {
    saving.value = true;
    formError.value = '';
    try {
        const payload = { ...form.value };
        if (!payload.target_resolution_date) delete (payload as any).target_resolution_date;
        if (!payload.root_cause) delete (payload as any).root_cause;
        if (!payload.corrective_action) delete (payload as any).corrective_action;

        if (editing.value) {
            const res = await api.put(`/quality/non-conformances/${editing.value.id}`, payload);
            const idx = records.value.findIndex(r => r.id === editing.value.id);
            if (idx >= 0) records.value[idx] = res.data?.data ?? res.data;
            notify.success('Non-conformance updated');
        } else {
            const res = await api.post('/quality/non-conformances', payload);
            records.value.unshift(res.data?.data ?? res.data);
            notify.success('Non-conformance created');
        }
        closeModal();
    } catch (e: any) {
        formError.value = e?.response?.data?.message ?? 'Failed to save non-conformance.';
    } finally {
        saving.value = false;
    }
}

async function quickStatus(row: any, newStatus: string) {
    if (row.status === newStatus) return;
    try {
        await api.put(`/quality/non-conformances/${row.id}/status`, { status: newStatus });
        row.status = newStatus;
        notify.success('Status updated');
    } catch {
        notify.error('Failed to update status');
    }
}

function confirmDelete(row: any) {
    deleteTarget.value = row;
}

async function doDelete() {
    if (!deleteTarget.value) return;
    saving.value = true;
    try {
        await api.delete(`/quality/non-conformances/${deleteTarget.value.id}`);
        records.value = records.value.filter(r => r.id !== deleteTarget.value.id);
        notify.success('Non-conformance deleted');
        deleteTarget.value = null;
    } catch {
        notify.error('Failed to delete non-conformance');
    } finally {
        saving.value = false;
    }
}
</script>
