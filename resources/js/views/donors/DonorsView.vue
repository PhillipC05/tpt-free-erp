<template>
    <div>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100 mb-6">Donors</h1>

        <DataTable :columns="columns" :data="donors" searchable>
            <template #header>
                <button @click="openCreate" class="px-4 py-2 bg-blue-600 text-white text-sm rounded-md hover:bg-blue-700">
                    New Donor
                </button>
            </template>
            <template #cell-type="{ value }">
                <span class="px-2 py-1 text-xs rounded-full font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                    {{ formatType(value as string) }}
                </span>
            </template>
            <template #cell-status="{ value }">
                <span :class="statusClass(value as string)" class="px-2 py-1 text-xs rounded-full font-medium">
                    {{ formatStatus(value as string) }}
                </span>
            </template>
            <template #cell-total_contributed="{ value }">
                ${{ Number(value ?? 0).toLocaleString() }}
            </template>
            <template #cell-actions="{ row }">
                <div class="flex gap-2">
                    <button @click="openEdit(row as any)" class="text-xs text-blue-600 dark:text-blue-400 hover:underline">Edit</button>
                    <router-link :to="`/grants?donor_id=${(row as any).id}`" class="text-xs text-green-600 dark:text-green-400 hover:underline">Grants</router-link>
                </div>
            </template>
        </DataTable>

        <ModalDialog v-model="showModal" :title="editingId ? 'Edit Donor' : 'New Donor'">
            <form @submit.prevent="saveDonor" class="space-y-4">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="sm:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Name</label>
                        <input v-model="form.name" type="text" required class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Type</label>
                        <select v-model="form.type" class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100">
                            <option value="individual">Individual</option>
                            <option value="corporate">Corporate</option>
                            <option value="foundation">Foundation</option>
                            <option value="government">Government</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Status</label>
                        <select v-model="form.status" class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Email</label>
                        <input v-model="form.email" type="email" class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Phone</label>
                        <input v-model="form.phone" type="text" class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Contact Person</label>
                        <input v-model="form.contact_person" type="text" class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100" />
                    </div>
                    <div class="sm:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Address</label>
                        <textarea v-model="form.address" rows="2" class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100" />
                    </div>
                    <div class="sm:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Notes</label>
                        <textarea v-model="form.notes" rows="2" class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100" />
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

interface Donor {
    id: number;
    name: string;
    type: string;
    email?: string;
    phone?: string;
    address?: string;
    contact_person?: string;
    total_contributed: number;
    status: string;
    notes?: string;
    grants_count?: number;
}

const donors = ref<Donor[]>([]);
const showModal = ref(false);
const saving = ref(false);
const editingId = ref<number | null>(null);

const defaultForm = () => ({
    name: '', type: 'individual', status: 'active',
    email: '', phone: '', address: '', contact_person: '', notes: '',
});
const form = reactive(defaultForm());

const columns = [
    { key: 'name', label: 'Name', sortable: true },
    { key: 'type', label: 'Type', sortable: true },
    { key: 'email', label: 'Email' },
    { key: 'contact_person', label: 'Contact' },
    { key: 'total_contributed', label: 'Total Contributed', sortable: true },
    { key: 'status', label: 'Status', sortable: true },
    { key: 'actions', label: 'Actions' },
];

function formatType(type: string): string {
    return type.charAt(0).toUpperCase() + type.slice(1);
}

function statusClass(status: string): string {
    const classes: Record<string, string> = {
        active: 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
        inactive: 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200',
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

function openEdit(donor: Donor) {
    editingId.value = donor.id;
    Object.assign(form, {
        name: donor.name,
        type: donor.type,
        status: donor.status,
        email: donor.email ?? '',
        phone: donor.phone ?? '',
        address: donor.address ?? '',
        contact_person: donor.contact_person ?? '',
        notes: donor.notes ?? '',
    });
    showModal.value = true;
}

async function saveDonor() {
    saving.value = true;
    try {
        if (editingId.value) {
            await apiClient.put(`/v1/donors/${editingId.value}`, form);
            notify.success('Donor updated');
        } else {
            await apiClient.post('/v1/donors', form);
            notify.success('Donor created');
        }
        showModal.value = false;
        await load();
    } catch {
        notify.error('Failed to save donor');
    } finally {
        saving.value = false;
    }
}

async function load() {
    try {
        const res = await apiClient.get('/v1/donors');
        donors.value = res.data?.data ?? [];
    } catch {
        donors.value = [];
    }
}

onMounted(load);
</script>
