<template>
    <div>
        <div class="flex items-center justify-between mb-6">
            <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">Inventory Analytics</h1>
            <select v-model="months" class="px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 text-sm" @change="loadData">
                <option :value="3">Last 3 Months</option>
                <option :value="6">Last 6 Months</option>
                <option :value="12">Last 12 Months</option>
            </select>
        </div>

        <div v-if="loading" class="flex items-center justify-center py-12">
            <div class="w-8 h-8 border-2 border-blue-500 border-t-transparent rounded-full animate-spin" />
        </div>

        <template v-else>
            <div class="grid grid-cols-2 gap-6 mb-6">
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
                    <h3 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">Stock Levels by Warehouse</h3>
                    <div class="space-y-3">
                        <div v-for="w in data.stock_levels_by_warehouse" :key="w.warehouse_id">
                            <div class="flex items-center justify-between text-sm mb-1">
                                <span class="text-gray-900 dark:text-gray-100">{{ w.warehouse_name }}</span>
                                <span class="text-gray-500">${{ Number(w.total_value).toLocaleString() }}</span>
                            </div>
                            <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-3 overflow-hidden">
                                <div class="bg-blue-500 h-full rounded-full" :style="{ width: barWidth(w.total_value, maxValue) + '%' }"></div>
                            </div>
                            <div class="text-xs text-gray-500 mt-0.5">{{ w.product_count }} products &middot; {{ Number(w.total_quantity).toLocaleString() }} units</div>
                        </div>
                        <div v-if="!data.stock_levels_by_warehouse?.length" class="text-sm text-gray-500 text-center py-4">No warehouse data</div>
                    </div>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
                    <h3 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">Top Products by Revenue</h3>
                    <div class="space-y-2">
                        <div v-for="(p, i) in data.top_products_by_revenue" :key="p.id" class="flex items-center gap-3">
                            <span class="text-xs font-medium text-gray-400 w-5">{{ i + 1 }}</span>
                            <div class="flex-1 min-w-0">
                                <div class="text-sm text-gray-900 dark:text-gray-100 truncate">{{ p.name }}</div>
                                <div class="text-xs text-gray-500">{{ Number(p.total_quantity).toLocaleString() }} units</div>
                            </div>
                            <span class="text-xs font-medium text-green-600">${{ Number(p.total_revenue).toLocaleString() }}</span>
                        </div>
                        <div v-if="!data.top_products_by_revenue?.length" class="text-sm text-gray-500 text-center py-4">No sales data</div>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-6 mb-6">
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
                    <h3 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">Low Stock Alerts</h3>
                    <div class="space-y-2">
                        <div v-for="p in data.low_stock_alerts" :key="p.id" class="flex items-center justify-between text-sm">
                            <div class="min-w-0">
                                <span class="text-gray-900 dark:text-gray-100 truncate block">{{ p.name }}</span>
                                <span class="text-xs text-gray-500">{{ p.sku }}</span>
                            </div>
                            <div class="text-right ml-2">
                                <span class="text-red-600 font-medium">{{ Number(p.current_stock).toLocaleString() }}</span>
                                <span class="text-gray-400"> / {{ Number(p.min_stock_level).toLocaleString() }}</span>
                            </div>
                        </div>
                        <div v-if="!data.low_stock_alerts?.length" class="text-sm text-green-600 text-center py-4">All stock levels healthy</div>
                    </div>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
                    <h3 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">Stock Movement History</h3>
                    <div class="space-y-2">
                        <div v-for="d in data.stock_movement_history" :key="d.month + d.type" class="flex items-center gap-3">
                            <span class="text-xs text-gray-500 w-14">{{ d.month }}</span>
                            <span class="text-xs px-2 py-0.5 rounded-full w-16 text-center" :class="movementBadge(d.type)">{{ d.type }}</span>
                            <div class="flex-1 bg-gray-200 dark:bg-gray-700 rounded-full h-3 overflow-hidden">
                                <div class="h-full rounded-full" :class="movementColor(d.type)" :style="{ width: barWidth(d.total_quantity, maxMovement) + '%' }"></div>
                            </div>
                            <span class="text-xs font-medium text-gray-700 dark:text-gray-300 w-16 text-right">{{ Number(d.total_quantity).toLocaleString() }}</span>
                        </div>
                        <div v-if="!data.stock_movement_history?.length" class="text-sm text-gray-500 text-center py-4">No movement data</div>
                    </div>
                </div>
            </div>
        </template>
    </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import apiClient from '@/api/axios';

const months = ref(6);
const loading = ref(true);
const data = ref<any>({
    stock_levels_by_warehouse: [],
    top_products_by_revenue: [],
    low_stock_alerts: [],
    stock_movement_history: [],
});

const maxValue = computed(() => Math.max(1, ...data.value.stock_levels_by_warehouse.map((w: any) => Number(w.total_value))));
const maxMovement = computed(() => Math.max(1, ...data.value.stock_movement_history.map((d: any) => Number(d.total_quantity))));

function barWidth(value: number, max: number): number {
    return max > 0 ? Math.max(2, (value / max) * 100) : 0;
}

function movementBadge(type: string): string {
    const map: Record<string, string> = { in: 'bg-green-100 text-green-800', out: 'bg-red-100 text-red-800', transfer: 'bg-blue-100 text-blue-800', adjustment: 'bg-yellow-100 text-yellow-800' };
    return map[type] ?? 'bg-gray-100 text-gray-800';
}

function movementColor(type: string): string {
    const map: Record<string, string> = { in: 'bg-green-500', out: 'bg-red-500', transfer: 'bg-blue-500', adjustment: 'bg-yellow-500' };
    return map[type] ?? 'bg-gray-500';
}

async function loadData() {
    loading.value = true;
    try {
        const res = await apiClient.get('/analytics/inventory', { params: { months: months.value } });
        data.value = res.data?.data ?? data.value;
    } catch { /* ignore */ }
    loading.value = false;
}

onMounted(loadData);
</script>
