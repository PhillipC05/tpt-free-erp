<template>
    <div>
        <div class="flex items-center justify-between mb-6">
            <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">Time Entries</h1>
            <button @click="openCreate" class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-md transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                Log Time
            </button>
        </div>

        <!-- Summary bar -->
        <div v-if="entries.length" class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
            <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 px-4 py-3">
                <p class="text-xs text-gray-500 dark:text-gray-400">Total Hours</p>
                <p class="text-xl font-bold text-gray-900 dark:text-gray-100">{{ totalHours.toFixed(2) }}</p>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 px-4 py-3">
                <p class="text-xs text-gray-500 dark:text-gray-400">Billable Hours</p>
                <p class="text-xl font-bold text-green-600 dark:text-green-400">{{ billableHours.toFixed(2) }}</p>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 px-4 py-3">
                <p class="text-xs text-gray-500 dark:text-gray-400">Non-Billable Hours</p>
                <p class="text-xl font-bold text-gray-500 dark:text-gray-400">{{ (totalHours - billableHours).toFixed(2) }}</p>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 px-4 py-3">
                <p class="text-xs text-gray-500 dark:text-gray-400">Entries</p>
                <p class="text-xl font-bold text-gray-900 dark:text-gray-100">{{ entries.length }}</p>
            </div>
        </div>

        <DataTable :columns="columns" :data="entries" searchable>
            <template #cell-is_billable="{ value }">
                <span :class="value ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-300' : 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400'" class="text-xs font-medium px-2 py-0.5 rounded-full">
                    {{ value ? 'Billable' : 'Non-billable' }}
                </span>
            </template>
            <template #cell-task="{ value }">
                <span class="text-sm text-gray-700 dark:text-gray-300">{{ value?.title ?? '—' }}</span>
            </template>
            <template #cell-user="{ value }">
                <span class="text-sm text-gray-700 dark:text-gray-300">{{ value?.name ?? '—' }}</span>
            </template>
            <template #cell-hours="{ value }">
                <span class="font-mono text-sm">{{ Number(value).toFixed(2) }}h</span>
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
            <div class="relative bg-white dark:bg-gray-800 rounded-xl shadow-xl w-full max-w-lg">
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100">{{ editing ? 'Edit Time Entry' : 'Log Time' }}</h2>
                    <button @click="closeModal" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <form @submit.prevent="save" class="px-6 py-4 space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Task <span class="text-red-500">*</span></label>
                        <select v-model="form.task_id" required class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-sm text-gray-900 dark:text-gray-100 px-3 py-2 focus:outline-none focus:ring-1 focus:ring-blue-500">
                            <option value="">Select task...</option>
                            <option v-for="t in tasks" :key="t.id" :value="t.id">{{ t.title }}</option>
                        </select>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Date <span class="text-red-500">*</span></label>
                            <input type="date" v-model="form.date" required class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-sm text-gray-900 dark:text-gray-100 px-3 py-2 focus:outline-none focus:ring-1 focus:ring-blue-500" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Hours <span class="text-red-500">*</span></label>
                            <input type="number" v-model="form.hours" min="0.25" max="24" step="0.25" required class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-sm text-gray-900 dark:text-gray-100 px-3 py-2 focus:outline-none focus:ring-1 focus:ring-blue-500" placeholder="0.00" />
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Description</label>
                        <textarea v-model="form.description" rows="3" class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-sm text-gray-900 dark:text-gray-100 px-3 py-2 focus:outline-none focus:ring-1 focus:ring-blue-500 resize-none" placeholder="What did you work on?"></textarea>
                    </div>

                    <div class="flex items-center gap-3">
                        <input type="checkbox" id="is_billable" v-model="form.is_billable" class="w-4 h-4 text-blue-600 rounded" />
                        <label for="is_billable" class="text-sm text-gray-700 dark:text-gray-300">Billable time</label>
                    </div>

                    <div v-if="formError" class="text-sm text-red-600 dark:text-red-400">{{ formError }}</div>

                    <div class="flex justify-end gap-3 pt-2">
                        <button type="button" @click="closeModal" class="px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-md transition-colors">Cancel</button>
                        <button type="submit" :disabled="saving" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 disabled:opacity-60 text-white text-sm font-medium rounded-md transition-colors">
                            {{ saving ? 'Saving...' : (editing ? 'Save Changes' : 'Log Time') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Delete confirm -->
        <div v-if="deleteTarget" class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-black/50" @click="deleteTarget = null" />
            <div class="relative bg-white dark:bg-gray-800 rounded-xl shadow-xl w-full max-w-sm p-6 text-center">
                <p class="text-gray-900 dark:text-gray-100 font-medium mb-1">Delete time entry?</p>
                <p class="text-sm text-gray-500 dark:text-gray-400 mb-6">{{ deleteTarget.hours }}h on {{ deleteTarget.date }}</p>
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

const entries = ref<any[]>([]);
const tasks = ref<any[]>([]);
const showModal = ref(false);
const editing = ref<any>(null);
const deleteTarget = ref<any>(null);
const saving = ref(false);
const formError = ref('');

const blankForm = () => ({
    task_id: '',
    user_id: '',
    date: new Date().toISOString().split('T')[0],
    hours: '',
    description: '',
    is_billable: true,
});

const form = ref(blankForm());

const columns = [
    { key: 'date', label: 'Date', sortable: true },
    { key: 'task', label: 'Task', sortable: false },
    { key: 'hours', label: 'Hours', sortable: true },
    { key: 'description', label: 'Description', sortable: false },
    { key: 'is_billable', label: 'Billable', sortable: true },
    { key: 'user', label: 'User', sortable: false },
];

const totalHours = computed(() => entries.value.reduce((s, e) => s + Number(e.hours ?? 0), 0));
const billableHours = computed(() => entries.value.filter(e => e.is_billable).reduce((s, e) => s + Number(e.hours ?? 0), 0));

onMounted(async () => {
    await Promise.all([loadEntries(), loadTasks()]);
});

async function loadEntries() {
    try {
        const res = await api.get('/projects/time-entries');
        entries.value = res.data?.data ?? res.data ?? [];
    } catch {
        notify.error('Failed to load time entries');
    }
}

async function loadTasks() {
    try {
        const res = await api.get('/projects/tasks');
        tasks.value = res.data?.data ?? res.data ?? [];
    } catch {
        // silently ignore — tasks list is optional for display
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
        task_id: row.task_id ?? row.task?.id ?? '',
        user_id: row.user_id ?? row.user?.id ?? '',
        date: row.date ?? '',
        hours: row.hours ?? '',
        description: row.description ?? '',
        is_billable: row.is_billable ?? true,
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
        const payload = { ...form.value, hours: Number(form.value.hours) };
        // use current user if no user_id set
        if (!payload.user_id) delete (payload as any).user_id;

        if (editing.value) {
            const res = await api.put(`/projects/time-entries/${editing.value.id}`, payload);
            const idx = entries.value.findIndex(e => e.id === editing.value.id);
            if (idx >= 0) entries.value[idx] = res.data?.data ?? res.data;
            notify.success('Time entry updated');
        } else {
            const res = await api.post('/projects/time-entries', payload);
            entries.value.unshift(res.data?.data ?? res.data);
            notify.success('Time logged');
        }
        closeModal();
    } catch (e: any) {
        formError.value = e?.response?.data?.message ?? 'Failed to save time entry.';
    } finally {
        saving.value = false;
    }
}

function confirmDelete(row: any) {
    deleteTarget.value = row;
}

async function doDelete() {
    if (!deleteTarget.value) return;
    saving.value = true;
    try {
        await api.delete(`/projects/time-entries/${deleteTarget.value.id}`);
        entries.value = entries.value.filter(e => e.id !== deleteTarget.value.id);
        notify.success('Time entry deleted');
        deleteTarget.value = null;
    } catch {
        notify.error('Failed to delete time entry');
    } finally {
        saving.value = false;
    }
}
</script>
