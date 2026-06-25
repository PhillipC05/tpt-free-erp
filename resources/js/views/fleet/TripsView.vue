<template>
    <div>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100 mb-6">Fleet Trips</h1>

        <div class="grid grid-cols-4 gap-4 mb-6">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
                <div class="text-sm text-gray-500 dark:text-gray-400">Active</div>
                <div class="text-2xl font-bold text-blue-600">{{ inProgressCount }}</div>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
                <div class="text-sm text-gray-500 dark:text-gray-400">Completed</div>
                <div class="text-2xl font-bold text-green-600">{{ completedCount }}</div>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
                <div class="text-sm text-gray-500 dark:text-gray-400">Scheduled</div>
                <div class="text-2xl font-bold text-yellow-600">{{ scheduledCount }}</div>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
                <div class="text-sm text-gray-500 dark:text-gray-400">Total Distance</div>
                <div class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ totalDistance.toLocaleString() }} km</div>
            </div>
        </div>

        <DataTable :columns="columns" :data="trips" searchable>
            <template #header>
                <button @click="showCreateModal = true" class="px-4 py-2 bg-blue-600 text-white text-sm rounded-md hover:bg-blue-700">
                    Plan Trip
                </button>
            </template>
            <template #cell-status="{ value }">
                <span :class="{
                    'text-blue-600': value === 'in_progress',
                    'text-green-600': value === 'completed',
                    'text-yellow-600': value === 'scheduled',
                    'text-red-600': value === 'cancelled',
                }" class="text-xs font-medium capitalize">{{ value?.replace('_', ' ') }}</span>
            </template>
            <template #cell-distance="{ value }">
                {{ value ? Number(value).toLocaleString() + ' km' : '—' }}
            </template>
            <template #cell-actions="{ row }">
                <div class="flex gap-1">
                    <button v-if="row.status === 'scheduled'" @click="startTrip(row.id)" class="text-xs px-2 py-1 bg-green-100 text-green-700 rounded hover:bg-green-200">Start</button>
                    <button v-if="row.status === 'in_progress'" @click="showCompleteModal(row)" class="text-xs px-2 py-1 bg-blue-100 text-blue-700 rounded hover:bg-blue-200">Complete</button>
                    <button v-if="row.status !== 'completed' && row.status !== 'cancelled'" @click="cancelTrip(row.id)" class="text-xs px-2 py-1 bg-red-100 text-red-700 rounded hover:bg-red-200">Cancel</button>
                </div>
            </template>
        </DataTable>

        <ModalDialog v-model="showCreateModal" title="Plan Trip">
            <form @submit.prevent="createTrip" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Start Location</label>
                    <input v-model="form.start_location" type="text" required class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 text-sm" />
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Purpose</label>
                    <input v-model="form.purpose" type="text" class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 text-sm" />
                </div>
                <div class="flex justify-end gap-2">
                    <button type="button" @click="showCreateModal = false" class="px-4 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-md text-gray-700 dark:text-gray-300">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white text-sm rounded-md hover:bg-blue-700">Save</button>
                </div>
            </form>
        </ModalDialog>

        <ModalDialog v-model="showCompleteTripModal" title="Complete Trip">
            <form @submit.prevent="completeTrip" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">End Location</label>
                    <input v-model="completeForm.end_location" type="text" required class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 text-sm" />
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">End Odometer</label>
                    <input v-model.number="completeForm.end_odometer" type="number" required min="0" class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 text-sm" />
                </div>
                <div class="flex justify-end gap-2">
                    <button type="button" @click="showCompleteTripModal = false" class="px-4 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-md text-gray-700 dark:text-gray-300">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-green-600 text-white text-sm rounded-md hover:bg-green-700">Complete</button>
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
import type { FleetTrip } from '@/types';
import { useNotificationStore } from '@/stores/notification';

const notify = useNotificationStore();
const trips = ref<FleetTrip[]>([]);
const showCreateModal = ref(false);
const showCompleteTripModal = ref(false);
const completingTripId = ref<number | null>(null);
const form = reactive({ start_location: '', purpose: '' });
const completeForm = reactive({ end_location: '', end_odometer: 0 });

const inProgressCount = computed(() => trips.value.filter(t => t.status === 'in_progress').length);
const completedCount = computed(() => trips.value.filter(t => t.status === 'completed').length);
const scheduledCount = computed(() => trips.value.filter(t => t.status === 'scheduled').length);
const totalDistance = computed(() => trips.value.reduce((sum, t) => sum + (t.distance || 0), 0));

const columns = [
    { key: 'trip_number', label: 'Trip #', sortable: true },
    { key: 'vehicle.license_plate', label: 'Vehicle', sortable: false },
    { key: 'driver.employee.first_name', label: 'Driver', sortable: false },
    { key: 'start_location', label: 'From', sortable: false },
    { key: 'end_location', label: 'To', sortable: false },
    { key: 'distance', label: 'Distance', sortable: true },
    { key: 'status', label: 'Status', sortable: true },
];

async function loadTrips() {
    try {
        const res = await apiClient.get('/fleet/trips');
        trips.value = res.data?.data ?? res.data ?? [];
    } catch {
        trips.value = [];
    }
}

async function createTrip() {
    try {
        await apiClient.post('/fleet/trips', form);
        showCreateModal.value = false;
        notify.success('Trip planned');
        form.start_location = '';
        form.purpose = '';
        await loadTrips();
    } catch {
        notify.error('Failed to plan trip');
    }
}

async function startTrip(id: number) {
    try {
        await apiClient.post(`/fleet/trips/${id}/start`);
        notify.success('Trip started');
        await loadTrips();
    } catch {
        notify.error('Failed to start trip');
    }
}

function showCompleteModal(trip: FleetTrip) {
    completingTripId.value = trip.id;
    completeForm.end_location = '';
    completeForm.end_odometer = trip.start_odometer;
    showCompleteTripModal.value = true;
}

async function completeTrip() {
    if (!completingTripId.value) return;
    try {
        await apiClient.post(`/fleet/trips/${completingTripId.value}/complete`, completeForm);
        showCompleteTripModal.value = false;
        notify.success('Trip completed');
        await loadTrips();
    } catch {
        notify.error('Failed to complete trip');
    }
}

async function cancelTrip(id: number) {
    if (!confirm('Cancel this trip?')) return;
    try {
        await apiClient.post(`/fleet/trips/${id}/cancel`);
        notify.success('Trip cancelled');
        await loadTrips();
    } catch {
        notify.error('Failed to cancel trip');
    }
}

onMounted(loadTrips);
</script>
