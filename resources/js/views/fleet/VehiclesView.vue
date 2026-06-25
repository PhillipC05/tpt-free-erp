<template>
    <div>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100 mb-6">Fleet Vehicles</h1>

        <div class="grid grid-cols-4 gap-4 mb-6">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
                <div class="text-sm text-gray-500 dark:text-gray-400">Active</div>
                <div class="text-2xl font-bold text-green-600">{{ activeCount }}</div>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
                <div class="text-sm text-gray-500 dark:text-gray-400">Maintenance</div>
                <div class="text-2xl font-bold text-yellow-600">{{ maintenanceCount }}</div>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
                <div class="text-sm text-gray-500 dark:text-gray-400">Inactive</div>
                <div class="text-2xl font-bold text-red-600">{{ inactiveCount }}</div>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
                <div class="text-sm text-gray-500 dark:text-gray-400">Total</div>
                <div class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ vehicles.length }}</div>
            </div>
        </div>

        <DataTable :columns="columns" :data="vehicles" searchable>
            <template #header>
                <button @click="showCreateModal = true" class="px-4 py-2 bg-blue-600 text-white text-sm rounded-md hover:bg-blue-700">
                    Add Vehicle
                </button>
            </template>
            <template #cell-status="{ value }">
                <span :class="{
                    'text-green-600': value === 'active',
                    'text-red-600': value === 'inactive',
                    'text-yellow-600': value === 'maintenance',
                    'text-gray-600': value === 'retired',
                }" class="text-xs font-medium capitalize">{{ value }}</span>
            </template>
            <template #cell-current_odometer="{ value }">
                {{ Number(value).toLocaleString() }} km
            </template>
        </DataTable>

        <ModalDialog v-model="showCreateModal" title="Add Vehicle">
            <form @submit.prevent="createVehicle" class="space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Vehicle Code</label>
                        <input v-model="form.vehicle_code" type="text" required placeholder="e.g. VH-001" class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 text-sm" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">License Plate</label>
                        <input v-model="form.license_plate" type="text" required class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 text-sm" />
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Make</label>
                        <input v-model="form.make" type="text" required placeholder="e.g. Toyota" class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 text-sm" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Model</label>
                        <input v-model="form.model" type="text" required placeholder="e.g. Corolla" class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 text-sm" />
                    </div>
                </div>
                <div class="grid grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Year</label>
                        <input v-model.number="form.year" type="number" required min="1900" :max="new Date().getFullYear() + 1" class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 text-sm" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Type</label>
                        <select v-model="form.type" class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 text-sm">
                            <option value="car">Car</option>
                            <option value="truck">Truck</option>
                            <option value="van">Van</option>
                            <option value="motorcycle">Motorcycle</option>
                            <option value="bus">Bus</option>
                            <option value="trailer">Trailer</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Fuel Type</label>
                        <select v-model="form.fuel_type" class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 text-sm">
                            <option value="gasoline">Gasoline</option>
                            <option value="diesel">Diesel</option>
                            <option value="electric">Electric</option>
                            <option value="hybrid">Hybrid</option>
                        </select>
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">VIN</label>
                    <input v-model="form.vin" type="text" maxlength="17" class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 text-sm" />
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
import { ref, reactive, computed, onMounted } from 'vue';
import DataTable from '@/components/DataTable.vue';
import ModalDialog from '@/components/ModalDialog.vue';
import apiClient from '@/api/axios';
import type { FleetVehicle } from '@/types';
import { useNotificationStore } from '@/stores/notification';

const notify = useNotificationStore();
const vehicles = ref<FleetVehicle[]>([]);
const showCreateModal = ref(false);
const form = reactive({
    vehicle_code: '', make: '', model: '', year: new Date().getFullYear(),
    license_plate: '', vin: '', type: 'car', fuel_type: 'gasoline',
});

const activeCount = computed(() => vehicles.value.filter(v => v.status === 'active').length);
const maintenanceCount = computed(() => vehicles.value.filter(v => v.status === 'maintenance').length);
const inactiveCount = computed(() => vehicles.value.filter(v => v.status === 'inactive' || v.status === 'retired').length);

const columns = [
    { key: 'vehicle_code', label: 'Code', sortable: true },
    { key: 'make', label: 'Make', sortable: true },
    { key: 'model', label: 'Model', sortable: true },
    { key: 'year', label: 'Year', sortable: true },
    { key: 'license_plate', label: 'Plate', sortable: true },
    { key: 'type', label: 'Type', sortable: true },
    { key: 'current_odometer', label: 'Odometer', sortable: true },
    { key: 'status', label: 'Status', sortable: true },
];

async function loadVehicles() {
    try {
        const res = await apiClient.get('/fleet/vehicles');
        vehicles.value = res.data?.data ?? res.data ?? [];
    } catch {
        vehicles.value = [];
    }
}

async function createVehicle() {
    try {
        await apiClient.post('/fleet/vehicles', form);
        showCreateModal.value = false;
        notify.success('Vehicle added successfully');
        Object.assign(form, { vehicle_code: '', make: '', model: '', year: new Date().getFullYear(), license_plate: '', vin: '', type: 'car', fuel_type: 'gasoline' });
        await loadVehicles();
    } catch {
        notify.error('Failed to add vehicle');
    }
}

onMounted(loadVehicles);
</script>
