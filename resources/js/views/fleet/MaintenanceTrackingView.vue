<template>
    <div>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100 mb-6">Maintenance Tracking</h1>

        <div v-if="dashboard" class="space-y-6">
            <div class="grid grid-cols-4 gap-4">
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
                    <div class="text-sm text-gray-500 dark:text-gray-400">Overdue</div>
                    <div class="text-2xl font-bold text-red-600">{{ dashboard.summary.overdue_count }}</div>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
                    <div class="text-sm text-gray-500 dark:text-gray-400">Upcoming (30d)</div>
                    <div class="text-2xl font-bold text-yellow-600">{{ dashboard.summary.upcoming_count }}</div>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
                    <div class="text-sm text-gray-500 dark:text-gray-400">In Progress</div>
                    <div class="text-2xl font-bold text-blue-600">{{ dashboard.summary.in_progress_count }}</div>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
                    <div class="text-sm text-gray-500 dark:text-gray-400">Spent (12mo)</div>
                    <div class="text-2xl font-bold text-green-600">${{ dashboard.summary.total_spent_year.toLocaleString() }}</div>
                </div>
            </div>

            <div v-if="dashboard.overdue_records.length" class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg p-4">
                <h3 class="text-sm font-semibold text-red-800 dark:text-red-300 mb-3">Overdue Maintenance</h3>
                <div class="space-y-2">
                    <div v-for="r in dashboard.overdue_records" :key="r.id" class="flex items-center justify-between">
                        <div>
                            <span class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ r.vehicle?.vehicle_code }} — {{ r.title }}</span>
                            <span class="text-xs text-red-600 ml-2">Due {{ r.scheduled_date }}</span>
                        </div>
                        <span class="text-xs px-2 py-1 rounded-full" :class="typeClass(r.type)">{{ r.type }}</span>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-6">
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
                    <h3 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-4">Monthly Maintenance Cost</h3>
                    <div class="space-y-2">
                        <div v-for="m in dashboard.monthly_cost" :key="m.month" class="flex items-center gap-3">
                            <span class="text-xs text-gray-500 w-16">{{ m.month }}</span>
                            <div class="flex-1 bg-gray-200 dark:bg-gray-700 rounded-full h-4 overflow-hidden">
                                <div class="bg-blue-500 h-full rounded-full" :style="{ width: getBarWidth(m.cost, maxMonthlyCost) + '%' }"></div>
                            </div>
                            <span class="text-xs font-medium text-gray-700 dark:text-gray-300 w-20 text-right">${{ m.cost.toLocaleString() }}</span>
                        </div>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
                    <h3 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-4">Cost by Type</h3>
                    <div class="space-y-3">
                        <div v-for="t in dashboard.cost_by_type" :key="t.type" class="flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <span class="text-xs px-2 py-1 rounded-full" :class="typeClass(t.type)">{{ t.type }}</span>
                                <span class="text-xs text-gray-500">{{ t.count }}x</span>
                            </div>
                            <span class="text-sm font-medium text-gray-900 dark:text-gray-100">${{ t.total_cost.toLocaleString() }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-6">
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
                    <h3 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-4">Cost by Vehicle</h3>
                    <div class="space-y-2">
                        <div v-for="v in dashboard.cost_by_vehicle" :key="v.vehicle_id" class="flex items-center justify-between py-1">
                            <span class="text-sm text-gray-900 dark:text-gray-100">{{ v.vehicle?.vehicle_code }} — {{ v.vehicle?.make }} {{ v.vehicle?.model }}</span>
                            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">${{ v.total_cost.toLocaleString() }}</span>
                        </div>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
                    <h3 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-4">Recent Completed</h3>
                    <div class="space-y-2">
                        <div v-for="r in dashboard.recent_completed" :key="r.id" class="flex items-center justify-between py-2 border-b border-gray-100 dark:border-gray-700 last:border-0">
                            <div>
                                <div class="text-sm text-gray-900 dark:text-gray-100">{{ r.vehicle?.vehicle_code }} — {{ r.title }}</div>
                                <div class="text-xs text-gray-500">{{ r.completed_date }} &middot; {{ r.type }}</div>
                            </div>
                            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">${{ r.cost?.toLocaleString() ?? '—' }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="mt-8">
            <h2 class="text-lg font-bold text-gray-900 dark:text-gray-100 mb-4">Vehicle Maintenance History</h2>
            <select v-model.number="selectedVehicleId" class="px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 text-sm mb-4">
                <option :value="0">Select vehicle...</option>
                <option v-for="v in vehicles" :key="v.id" :value="v.id">{{ v.vehicle_code }} — {{ v.make }} {{ v.model }}</option>
            </select>

            <div v-if="vehicleHistory" class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
                <div class="grid grid-cols-4 gap-4 p-4 border-b border-gray-200 dark:border-gray-700">
                    <div>
                        <div class="text-xs text-gray-500">Total Cost</div>
                        <div class="text-sm font-bold text-gray-900 dark:text-gray-100">${{ vehicleHistory.total_cost.toLocaleString() }}</div>
                    </div>
                    <div>
                        <div class="text-xs text-gray-500">Last Service</div>
                        <div class="text-sm font-bold text-gray-900 dark:text-gray-100">{{ vehicleHistory.last_service_date ?? 'Never' }}</div>
                    </div>
                    <div>
                        <div class="text-xs text-gray-500">Avg Interval</div>
                        <div class="text-sm font-bold text-gray-900 dark:text-gray-100">{{ vehicleHistory.avg_interval_days ? vehicleHistory.avg_interval_days + ' days' : 'N/A' }}</div>
                    </div>
                    <div>
                        <div class="text-xs text-gray-500">Records</div>
                        <div class="text-sm font-bold text-gray-900 dark:text-gray-100">{{ vehicleHistory.records.length }}</div>
                    </div>
                </div>
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs text-gray-500">Date</th>
                            <th class="px-4 py-3 text-left text-xs text-gray-500">Title</th>
                            <th class="px-4 py-3 text-left text-xs text-gray-500">Type</th>
                            <th class="px-4 py-3 text-left text-xs text-gray-500">Status</th>
                            <th class="px-4 py-3 text-right text-xs text-gray-500">Cost</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        <tr v-for="r in vehicleHistory.records" :key="r.id" class="hover:bg-gray-50 dark:hover:bg-gray-700">
                            <td class="px-4 py-3 text-gray-900 dark:text-gray-100">{{ r.scheduled_date ?? r.completed_date ?? '—' }}</td>
                            <td class="px-4 py-3 text-gray-900 dark:text-gray-100">{{ r.title }}</td>
                            <td class="px-4 py-3"><span class="text-xs px-2 py-1 rounded-full" :class="typeClass(r.type)">{{ r.type }}</span></td>
                            <td class="px-4 py-3"><span class="text-xs capitalize" :class="statusClass(r.status)">{{ r.status }}</span></td>
                            <td class="px-4 py-3 text-right text-gray-700 dark:text-gray-300">{{ r.cost != null ? '$' + Number(r.cost).toLocaleString() : '—' }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, watch } from 'vue';
import apiClient from '@/api/axios';
import type { MaintenanceTrackingDashboard, MaintenanceVehicleHistory, FleetVehicle } from '@/types';

const dashboard = ref<MaintenanceTrackingDashboard | null>(null);
const vehicles = ref<FleetVehicle[]>([]);
const selectedVehicleId = ref(0);
const vehicleHistory = ref<MaintenanceVehicleHistory | null>(null);

const maxMonthlyCost = computed(() => {
    if (!dashboard.value?.monthly_cost?.length) return 1;
    return Math.max(...dashboard.value.monthly_cost.map(m => m.cost));
});

function getBarWidth(value: number, max: number): number {
    return max > 0 ? Math.max(2, (value / max) * 100) : 0;
}

function typeClass(type: string): string {
    const map: Record<string, string> = {
        preventive: 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-300',
        corrective: 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-300',
        emergency: 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-300',
        inspection: 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300',
    };
    return map[type] ?? 'bg-gray-100 text-gray-700';
}

function statusClass(status: string): string {
    const map: Record<string, string> = {
        completed: 'text-green-600',
        scheduled: 'text-blue-600',
        in_progress: 'text-yellow-600',
        cancelled: 'text-red-600',
    };
    return map[status] ?? 'text-gray-600';
}

async function loadDashboard() {
    try {
        const res = await apiClient.get('/fleet/maintenance-tracking/dashboard');
        dashboard.value = res.data?.data ?? null;
    } catch {
        dashboard.value = null;
    }
}

async function loadVehicles() {
    try {
        const res = await apiClient.get('/fleet/vehicles');
        vehicles.value = res.data?.data ?? [];
    } catch {
        vehicles.value = [];
    }
}

async function loadVehicleHistory() {
    if (!selectedVehicleId.value) {
        vehicleHistory.value = null;
        return;
    }
    try {
        const res = await apiClient.get('/fleet/maintenance-tracking/history', { params: { vehicle_id: selectedVehicleId.value } });
        vehicleHistory.value = res.data?.data ?? null;
    } catch {
        vehicleHistory.value = null;
    }
}

watch(selectedVehicleId, loadVehicleHistory);

onMounted(() => {
    loadDashboard();
    loadVehicles();
});
</script>
