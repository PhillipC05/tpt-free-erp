<template>
    <div>
        <div class="flex items-center justify-between mb-6">
            <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">Dashboard</h1>
            <select v-model="period" class="px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 text-sm" @change="loadData">
                <option value="week">Last Week</option>
                <option value="month">Last Month</option>
                <option value="quarter">Last Quarter</option>
                <option value="year">Last Year</option>
            </select>
        </div>

        <div v-if="kpis" class="grid grid-cols-5 gap-4 mb-6">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
                <div class="text-xs text-gray-500 dark:text-gray-400 mb-1">Revenue</div>
                <div class="text-xl font-bold text-gray-900 dark:text-gray-100">${{ kpis.revenue.current.toLocaleString() }}</div>
                <div class="text-xs mt-1" :class="kpis.revenue.trend >= 0 ? 'text-green-600' : 'text-red-600'">
                    {{ kpis.revenue.trend >= 0 ? '+' : '' }}{{ kpis.revenue.trend }}%
                </div>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
                <div class="text-xs text-gray-500 dark:text-gray-400 mb-1">Orders</div>
                <div class="text-xl font-bold text-gray-900 dark:text-gray-100">{{ kpis.orders.current.toLocaleString() }}</div>
                <div class="text-xs mt-1" :class="kpis.orders.trend >= 0 ? 'text-green-600' : 'text-red-600'">
                    {{ kpis.orders.trend >= 0 ? '+' : '' }}{{ kpis.orders.trend }}%
                </div>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
                <div class="text-xs text-gray-500 dark:text-gray-400 mb-1">MRR</div>
                <div class="text-xl font-bold text-green-600">${{ kpis.mrr.toLocaleString() }}</div>
                <div class="text-xs text-gray-500 mt-1">{{ kpis.active_subscriptions }} active subs</div>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
                <div class="text-xs text-gray-500 dark:text-gray-400 mb-1">Fleet Cost</div>
                <div class="text-xl font-bold text-orange-600">${{ kpis.fleet_costs.toLocaleString() }}</div>
                <div class="text-xs text-gray-500 mt-1">{{ kpis.fleet_distance_km.toLocaleString() }} km driven</div>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
                <div class="text-xs text-gray-500 dark:text-gray-400 mb-1">New Customers</div>
                <div class="text-xl font-bold text-blue-600">{{ kpis.new_customers }}</div>
                <div class="text-xs text-gray-500 mt-1">{{ kpis.pending_orders }} pending orders</div>
            </div>
        </div>

        <div class="grid grid-cols-4 gap-4 mb-6">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
                <div class="text-xs text-gray-500 dark:text-gray-400 mb-1">Active Employees</div>
                <div class="text-lg font-bold text-gray-900 dark:text-gray-100">{{ modules?.hr?.active_employees ?? 0 }}</div>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
                <div class="text-xs text-gray-500 dark:text-gray-400 mb-1">Products</div>
                <div class="text-lg font-bold text-gray-900 dark:text-gray-100">{{ modules?.inventory?.total_products ?? 0 }}</div>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
                <div class="text-xs text-gray-500 dark:text-gray-400 mb-1">Active Vehicles</div>
                <div class="text-lg font-bold text-gray-900 dark:text-gray-100">{{ kpis?.active_vehicles ?? 0 }}</div>
                <div v-if="kpis?.vehicles_in_maintenance" class="text-xs text-yellow-600">{{ kpis.vehicles_in_maintenance }} in maintenance</div>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
                <div class="text-xs text-gray-500 dark:text-gray-400 mb-1">Pending Leave</div>
                <div class="text-lg font-bold text-gray-900 dark:text-gray-100">{{ modules?.hr?.pending_leave ?? 0 }}</div>
            </div>
        </div>

        <div class="grid grid-cols-2 gap-6 mb-6">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
                <h3 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">Revenue Trend</h3>
                <div class="space-y-2">
                    <div v-for="d in charts?.revenue_trend ?? []" :key="d.month" class="flex items-center gap-3">
                        <span class="text-xs text-gray-500 w-14">{{ d.month }}</span>
                        <div class="flex-1 bg-gray-200 dark:bg-gray-700 rounded-full h-4 overflow-hidden">
                            <div class="bg-green-500 h-full rounded-full" :style="{ width: barWidth(d.value, maxRevenue) + '%' }"></div>
                        </div>
                        <span class="text-xs font-medium text-gray-700 dark:text-gray-300 w-20 text-right">${{ d.value.toLocaleString() }}</span>
                    </div>
                </div>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
                <h3 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">Orders Trend</h3>
                <div class="space-y-2">
                    <div v-for="d in charts?.orders_trend ?? []" :key="d.month" class="flex items-center gap-3">
                        <span class="text-xs text-gray-500 w-14">{{ d.month }}</span>
                        <div class="flex-1 bg-gray-200 dark:bg-gray-700 rounded-full h-4 overflow-hidden">
                            <div class="bg-blue-500 h-full rounded-full" :style="{ width: barWidth(d.value, maxOrders) + '%' }"></div>
                        </div>
                        <span class="text-xs font-medium text-gray-700 dark:text-gray-300 w-20 text-right">{{ d.value }}</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-2 gap-6 mb-6">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
                <h3 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">Fleet Cost Trend</h3>
                <div class="space-y-2">
                    <div v-for="d in charts?.fleet_fuel_cost_trend ?? []" :key="d.month" class="flex items-center gap-3">
                        <span class="text-xs text-gray-500 w-14">{{ d.month }}</span>
                        <div class="flex-1 bg-gray-200 dark:bg-gray-700 rounded-full h-4 overflow-hidden">
                            <div class="bg-orange-500 h-full rounded-full" :style="{ width: barWidth(d.value, maxFleetCost) + '%' }"></div>
                        </div>
                        <span class="text-xs font-medium text-gray-700 dark:text-gray-300 w-20 text-right">${{ d.value.toLocaleString() }}</span>
                    </div>
                </div>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
                <h3 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">Top Products</h3>
                <div class="space-y-2">
                    <div v-for="p in (charts?.top_products ?? []).slice(0, 6)" :key="p.name" class="flex items-center justify-between">
                        <span class="text-sm text-gray-900 dark:text-gray-100 truncate">{{ p.name }}</span>
                        <span class="text-xs font-medium text-gray-700 dark:text-gray-300">${{ p.total_revenue.toLocaleString() }}</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
            <h3 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">Recent Activity</h3>
            <div class="divide-y divide-gray-100 dark:divide-gray-700">
                <div v-for="(item, i) in activity.slice(0, 15)" :key="i" class="flex items-start gap-3 py-2">
                    <span class="w-2 h-2 mt-2 rounded-full flex-shrink-0" :class="activityColor(item.type)"></span>
                    <div class="flex-1 min-w-0">
                        <div class="text-sm text-gray-900 dark:text-gray-100">{{ item.label }}</div>
                        <div class="text-xs text-gray-500 truncate">{{ item.detail }}</div>
                    </div>
                    <span class="text-xs text-gray-400 flex-shrink-0">{{ timeAgo(item.created_at) }}</span>
                </div>
                <div v-if="activity.length === 0" class="py-4 text-center text-gray-500 text-sm">No recent activity</div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import apiClient from '@/api/axios';
import type { KpiData, ChartData, ActivityItem, ModuleSummary } from '@/types';

const period = ref('month');
const kpis = ref<KpiData | null>(null);
const charts = ref<ChartData | null>(null);
const activity = ref<ActivityItem[]>([]);
const modules = ref<ModuleSummary | null>(null);

const maxRevenue = computed(() => Math.max(1, ...(charts.value?.revenue_trend ?? []).map(d => d.value)));
const maxOrders = computed(() => Math.max(1, ...(charts.value?.orders_trend ?? []).map(d => d.value)));
const maxFleetCost = computed(() => Math.max(1, ...(charts.value?.fleet_fuel_cost_trend ?? []).map(d => d.value)));

function barWidth(value: number, max: number): number {
    return max > 0 ? Math.max(2, (value / max) * 100) : 0;
}

function timeAgo(dateStr: string): string {
    const diff = Date.now() - new Date(dateStr).getTime();
    const mins = Math.floor(diff / 60000);
    if (mins < 60) return `${mins}m ago`;
    const hours = Math.floor(mins / 60);
    if (hours < 24) return `${hours}h ago`;
    return `${Math.floor(hours / 24)}d ago`;
}

function activityColor(type: string): string {
    const map: Record<string, string> = {
        order: 'bg-blue-500',
        trip: 'bg-green-500',
        maintenance: 'bg-orange-500',
        notification: 'bg-purple-500',
    };
    return map[type] ?? 'bg-gray-500';
}

async function loadData() {
    try {
        const [kpiRes, chartRes, actRes, modRes] = await Promise.all([
            apiClient.get('/analytics/kpis', { params: { period: period.value } }),
            apiClient.get('/analytics/charts', { params: { months: 6 } }),
            apiClient.get('/analytics/activity', { params: { limit: 20 } }),
            apiClient.get('/analytics/modules'),
        ]);
        kpis.value = kpiRes.data?.data ?? null;
        charts.value = chartRes.data?.data ?? null;
        activity.value = actRes.data?.data ?? [];
        modules.value = modRes.data?.data ?? null;
    } catch { /* ignore */ }
}

onMounted(loadData);
</script>
