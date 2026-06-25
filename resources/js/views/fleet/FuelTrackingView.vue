<template>
    <div>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100 mb-6">Fuel Tracking</h1>

        <div class="flex items-center gap-4 mb-6">
            <div>
                <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">Start Date</label>
                <input v-model="startDate" type="date" class="px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 text-sm" />
            </div>
            <div>
                <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">End Date</label>
                <input v-model="endDate" type="date" class="px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 text-sm" />
            </div>
            <button @click="loadDashboard" class="px-4 py-2 bg-blue-600 text-white text-sm rounded-md hover:bg-blue-700 mt-4">Refresh</button>
        </div>

        <div v-if="dashboard" class="space-y-6">
            <div class="grid grid-cols-4 gap-4">
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
                    <div class="text-sm text-gray-500 dark:text-gray-400">Total Cost</div>
                    <div class="text-2xl font-bold text-red-600">${{ dashboard.summary.total_cost.toLocaleString(undefined, { minimumFractionDigits: 2 }) }}</div>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
                    <div class="text-sm text-gray-500 dark:text-gray-400">Total Fuel</div>
                    <div class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ dashboard.summary.total_quantity }} L</div>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
                    <div class="text-sm text-gray-500 dark:text-gray-400">Refuels</div>
                    <div class="text-2xl font-bold text-blue-600">{{ dashboard.summary.total_refuels }}</div>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
                    <div class="text-sm text-gray-500 dark:text-gray-400">Avg Cost/Liter</div>
                    <div class="text-2xl font-bold text-green-600">${{ dashboard.summary.avg_cost_per_liter }}</div>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-6">
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
                    <h3 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-4">Monthly Spending</h3>
                    <div class="space-y-2">
                        <div v-for="m in dashboard.monthly_trend" :key="m.month" class="flex items-center gap-3">
                            <span class="text-xs text-gray-500 w-20">{{ m.month }}</span>
                            <div class="flex-1 bg-gray-200 dark:bg-gray-700 rounded-full h-4 overflow-hidden">
                                <div class="bg-blue-500 h-full rounded-full" :style="{ width: getBarWidth(m.cost, maxMonthlyCost) + '%' }"></div>
                            </div>
                            <span class="text-xs font-medium text-gray-700 dark:text-gray-300 w-20 text-right">${{ m.cost.toLocaleString() }}</span>
                        </div>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
                    <h3 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-4">Cost by Fuel Type</h3>
                    <div class="space-y-3">
                        <div v-for="ft in dashboard.cost_by_fuel_type" :key="ft.fuel_type" class="flex items-center justify-between">
                            <span class="text-sm text-gray-700 dark:text-gray-300 capitalize">{{ ft.fuel_type }}</span>
                            <div class="flex items-center gap-3">
                                <span class="text-xs text-gray-500">{{ ft.quantity }}L</span>
                                <span class="text-sm font-medium text-gray-900 dark:text-gray-100">${{ ft.cost.toLocaleString() }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-6">
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
                    <h3 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-4">Top Stations</h3>
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="text-left text-xs text-gray-500 dark:text-gray-400">
                                <th class="pb-2">Station</th>
                                <th class="pb-2 text-right">Visits</th>
                                <th class="pb-2 text-right">Total</th>
                                <th class="pb-2 text-right">Avg Price</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                            <tr v-for="s in dashboard.top_stations" :key="s.station">
                                <td class="py-2 text-gray-900 dark:text-gray-100">{{ s.station }}</td>
                                <td class="py-2 text-right text-gray-600 dark:text-gray-400">{{ s.visits }}</td>
                                <td class="py-2 text-right text-gray-600 dark:text-gray-400">${{ s.total_spent.toLocaleString() }}</td>
                                <td class="py-2 text-right text-gray-600 dark:text-gray-400">${{ Number(s.avg_price).toFixed(4) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
                    <h3 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-4">Recent Fuel Logs</h3>
                    <div class="space-y-2">
                        <div v-for="log in dashboard.recent_logs" :key="log.id" class="flex items-center justify-between py-2 border-b border-gray-100 dark:border-gray-700 last:border-0">
                            <div>
                                <div class="text-sm text-gray-900 dark:text-gray-100">{{ log.vehicle?.make }} {{ log.vehicle?.model }}</div>
                                <div class="text-xs text-gray-500">{{ log.date }} &middot; {{ log.fuel_type }}</div>
                            </div>
                            <div class="text-right">
                                <div class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ log.quantity }}L</div>
                                <div class="text-xs text-gray-500">${{ log.total_cost.toFixed(2) }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="mt-8">
            <div class="flex items-center gap-4 mb-4">
                <h2 class="text-lg font-bold text-gray-900 dark:text-gray-100">Efficiency by Vehicle</h2>
                <select v-model.number="selectedVehicleId" class="px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 text-sm">
                    <option :value="0">Select vehicle...</option>
                    <option v-for="v in consumptionData" :key="v.vehicle_id" :value="v.vehicle_id">{{ v.vehicle?.vehicle_code }} — {{ v.vehicle?.make }} {{ v.vehicle?.model }}</option>
                </select>
            </div>

            <div v-if="selectedVehicleId && efficiencyData" class="grid grid-cols-4 gap-4 mb-4">
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
                    <div class="text-sm text-gray-500 dark:text-gray-400">Distance</div>
                    <div class="text-lg font-bold text-gray-900 dark:text-gray-100">{{ efficiencyData.total_distance_km.toLocaleString() }} km</div>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
                    <div class="text-sm text-gray-500 dark:text-gray-400">Total Fuel</div>
                    <div class="text-lg font-bold text-gray-900 dark:text-gray-100">{{ efficiencyData.total_fuel_quantity }} L</div>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
                    <div class="text-sm text-gray-500 dark:text-gray-400">km/Liter</div>
                    <div class="text-lg font-bold text-green-600">{{ efficiencyData.average_efficiency?.km_per_liter ?? '—' }}</div>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
                    <div class="text-sm text-gray-500 dark:text-gray-400">Cost/km</div>
                    <div class="text-lg font-bold text-blue-600">${{ efficiencyData.average_efficiency?.cost_per_km ?? '—' }}</div>
                </div>
            </div>

            <div v-if="selectedVehicleId && efficiencyData?.efficiency_records?.length" class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs text-gray-500 dark:text-gray-300">Date</th>
                            <th class="px-4 py-3 text-right text-xs text-gray-500 dark:text-gray-300">Distance</th>
                            <th class="px-4 py-3 text-right text-xs text-gray-500 dark:text-gray-300">Fuel</th>
                            <th class="px-4 py-3 text-right text-xs text-gray-500 dark:text-gray-300">km/L</th>
                            <th class="px-4 py-3 text-right text-xs text-gray-500 dark:text-gray-300">L/100km</th>
                            <th class="px-4 py-3 text-right text-xs text-gray-500 dark:text-gray-300">Cost/km</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        <tr v-for="r in efficiencyData.efficiency_records" :key="r.date" class="hover:bg-gray-50 dark:hover:bg-gray-700">
                            <td class="px-4 py-3 text-gray-900 dark:text-gray-100">{{ r.date }}</td>
                            <td class="px-4 py-3 text-right text-gray-700 dark:text-gray-300">{{ r.distance_km }} km</td>
                            <td class="px-4 py-3 text-right text-gray-700 dark:text-gray-300">{{ r.fuel_used }} L</td>
                            <td class="px-4 py-3 text-right font-medium text-green-600">{{ r.km_per_liter }}</td>
                            <td class="px-4 py-3 text-right text-gray-700 dark:text-gray-300">{{ r.liters_per_100km }}</td>
                            <td class="px-4 py-3 text-right text-gray-700 dark:text-gray-300">${{ r.cost_per_km }}</td>
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
import type { FuelTrackingDashboard, FuelEfficiencyData, FuelConsumptionByVehicle } from '@/types';

const startDate = ref(new Date(Date.now() - 90 * 24 * 60 * 60 * 1000).toISOString().split('T')[0]);
const endDate = ref(new Date().toISOString().split('T')[0]);
const dashboard = ref<FuelTrackingDashboard | null>(null);
const consumptionData = ref<FuelConsumptionByVehicle[]>([]);
const selectedVehicleId = ref(0);
const efficiencyData = ref<FuelEfficiencyData | null>(null);

const maxMonthlyCost = computed(() => {
    if (!dashboard.value?.monthly_trend?.length) return 1;
    return Math.max(...dashboard.value.monthly_trend.map(m => m.cost));
});

function getBarWidth(value: number, max: number): number {
    return max > 0 ? Math.max(2, (value / max) * 100) : 0;
}

async function loadDashboard() {
    try {
        const [dashRes, consumeRes] = await Promise.all([
            apiClient.get('/fleet/fuel-tracking/dashboard', { params: { start_date: startDate.value, end_date: endDate.value } }),
            apiClient.get('/fleet/fuel-tracking/consumption', { params: { start_date: startDate.value, end_date: endDate.value } }),
        ]);
        dashboard.value = dashRes.data?.data ?? null;
        consumptionData.value = consumeRes.data?.data ?? [];
    } catch {
        dashboard.value = null;
    }
}

async function loadEfficiency() {
    if (!selectedVehicleId.value) {
        efficiencyData.value = null;
        return;
    }
    try {
        const res = await apiClient.get('/fleet/fuel-tracking/efficiency', { params: { vehicle_id: selectedVehicleId.value } });
        efficiencyData.value = res.data?.data ?? null;
    } catch {
        efficiencyData.value = null;
    }
}

watch(selectedVehicleId, loadEfficiency);

onMounted(loadDashboard);
</script>
