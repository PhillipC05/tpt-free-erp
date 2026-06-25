<template>
    <div>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100 mb-6">Fleet Drivers</h1>

        <DataTable :columns="columns" :data="drivers" searchable>
            <template #header>
                <button @click="showCreateModal = true" class="px-4 py-2 bg-blue-600 text-white text-sm rounded-md hover:bg-blue-700">
                    Register Driver
                </button>
            </template>
            <template #cell-status="{ value }">
                <span :class="{
                    'text-green-600': value === 'active',
                    'text-red-600': value === 'inactive',
                    'text-yellow-600': value === 'suspended',
                }" class="text-xs font-medium capitalize">{{ value }}</span>
            </template>
            <template #cell-license_expiry="{ value }">
                <span :class="isExpiringSoon(value) ? 'text-red-600 font-medium' : ''">
                    {{ new Date(value).toLocaleDateString() }}
                </span>
            </template>
        </DataTable>

        <ModalDialog v-model="showCreateModal" title="Register Driver">
            <form @submit.prevent="createDriver" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Employee ID</label>
                    <input v-model.number="form.employee_id" type="number" required class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 text-sm" />
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">License Number</label>
                    <input v-model="form.license_number" type="text" required class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 text-sm" />
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">License Class</label>
                        <input v-model="form.license_class" type="text" class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 text-sm" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">License Expiry</label>
                        <input v-model="form.license_expiry" type="date" required class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 text-sm" />
                    </div>
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
import type { FleetDriver } from '@/types';
import { useNotificationStore } from '@/stores/notification';

const notify = useNotificationStore();
const drivers = ref<FleetDriver[]>([]);
const showCreateModal = ref(false);
const form = reactive({ employee_id: 0, license_number: '', license_class: '', license_expiry: '' });

const columns = [
    { key: 'employee.first_name', label: 'Name', sortable: false },
    { key: 'license_number', label: 'License #', sortable: true },
    { key: 'license_class', label: 'Class', sortable: true },
    { key: 'license_expiry', label: 'Expiry', sortable: true },
    { key: 'status', label: 'Status', sortable: true },
];

function isExpiringSoon(date: string): boolean {
    const expiry = new Date(date);
    const now = new Date();
    const daysUntil = (expiry.getTime() - now.getTime()) / (1000 * 60 * 60 * 24);
    return daysUntil <= 30 && daysUntil >= 0;
}

async function loadDrivers() {
    try {
        const res = await apiClient.get('/fleet/drivers');
        drivers.value = res.data?.data ?? res.data ?? [];
    } catch {
        drivers.value = [];
    }
}

async function createDriver() {
    try {
        await apiClient.post('/fleet/drivers', form);
        showCreateModal.value = false;
        notify.success('Driver registered');
        Object.assign(form, { employee_id: 0, license_number: '', license_class: '', license_expiry: '' });
        await loadDrivers();
    } catch {
        notify.error('Failed to register driver');
    }
}

onMounted(loadDrivers);
</script>
