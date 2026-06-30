<template>
    <div>
        <div class="flex items-center justify-between mb-6">
            <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">Sales Analytics</h1>
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
            <div class="grid grid-cols-3 gap-4 mb-6">
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
                    <div class="text-xs text-gray-500 dark:text-gray-400 mb-1">Pipeline Value</div>
                    <div class="text-xl font-bold text-green-600">${{ totalPipeline.toLocaleString() }}</div>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
                    <div class="text-xs text-gray-500 dark:text-gray-400 mb-1">Total Deals</div>
                    <div class="text-xl font-bold text-gray-900 dark:text-gray-100">{{ totalDeals.toLocaleString() }}</div>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
                    <div class="text-xs text-gray-500 dark:text-gray-400 mb-1">Total Revenue</div>
                    <div class="text-xl font-bold text-blue-600">${{ totalRevenue.toLocaleString() }}</div>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-6 mb-6">
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
                    <h3 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">Pipeline by Stage</h3>
                    <div class="space-y-3">
                        <div v-for="s in data.pipeline_value" :key="s.stage">
                            <div class="flex items-center justify-between text-sm mb-1">
                                <span class="text-gray-900 dark:text-gray-100 capitalize">{{ s.stage.replace('_', ' ') }}</span>
                                <span class="text-gray-500">${{ Number(s.total_value).toLocaleString() }} ({{ s.deal_count }})</span>
                            </div>
                            <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-3 overflow-hidden">
                                <div class="bg-purple-500 h-full rounded-full" :style="{ width: barWidth(s.total_value, maxPipeline) + '%' }"></div>
                            </div>
                        </div>
                        <div v-if="!data.pipeline_value?.length" class="text-sm text-gray-500 text-center py-4">No pipeline data</div>
                    </div>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
                    <h3 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">Conversion Rates</h3>
                    <div class="space-y-2">
                        <div v-for="c in data.conversion_rates" :key="c.stage" class="flex items-center gap-3">
                            <span class="text-xs text-gray-500 w-24 capitalize">{{ c.stage.replace('_', ' ') }}</span>
                            <div class="flex-1 bg-gray-200 dark:bg-gray-700 rounded-full h-4 overflow-hidden">
                                <div class="bg-blue-500 h-full rounded-full" :style="{ width: c.conversion_rate + '%' }"></div>
                            </div>
                            <span class="text-xs font-medium text-gray-700 dark:text-gray-300 w-12 text-right">{{ c.conversion_rate }}%</span>
                        </div>
                        <div v-if="!data.conversion_rates?.length" class="text-sm text-gray-500 text-center py-4">No conversion data</div>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-6 mb-6">
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
                    <h3 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">Top Customers</h3>
                    <div class="space-y-2">
                        <div v-for="(c, i) in data.top_customers" :key="c.id" class="flex items-center gap-3">
                            <span class="text-xs font-medium text-gray-400 w-5">{{ i + 1 }}</span>
                            <div class="flex-1 min-w-0">
                                <div class="text-sm text-gray-900 dark:text-gray-100 truncate">{{ c.name }}</div>
                                <div class="text-xs text-gray-500">{{ c.order_count }} orders &middot; Avg ${{ Number(c.avg_order_value).toLocaleString() }}</div>
                            </div>
                            <span class="text-xs font-medium text-green-600">${{ Number(c.total_revenue).toLocaleString() }}</span>
                        </div>
                        <div v-if="!data.top_customers?.length" class="text-sm text-gray-500 text-center py-4">No customer data</div>
                    </div>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
                    <h3 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">Revenue by Month</h3>
                    <div class="space-y-2">
                        <div v-for="d in data.revenue_by_month" :key="d.month" class="flex items-center gap-3">
                            <span class="text-xs text-gray-500 w-14">{{ d.month }}</span>
                            <div class="flex-1 bg-gray-200 dark:bg-gray-700 rounded-full h-4 overflow-hidden">
                                <div class="bg-green-500 h-full rounded-full" :style="{ width: barWidth(d.revenue, maxRevenue) + '%' }"></div>
                            </div>
                            <span class="text-xs font-medium text-gray-700 dark:text-gray-300 w-20 text-right">${{ Number(d.revenue).toLocaleString() }}</span>
                        </div>
                        <div v-if="!data.revenue_by_month?.length" class="text-sm text-gray-500 text-center py-4">No revenue data</div>
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
    pipeline_value: [],
    conversion_rates: [],
    top_customers: [],
    revenue_by_month: [],
});

const totalPipeline = computed(() => data.value.pipeline_value.reduce((sum: number, s: any) => sum + Number(s.total_value), 0));
const totalDeals = computed(() => data.value.pipeline_value.reduce((sum: number, s: any) => sum + s.deal_count, 0));
const totalRevenue = computed(() => data.value.revenue_by_month.reduce((sum: number, d: any) => sum + Number(d.revenue), 0));
const maxPipeline = computed(() => Math.max(1, ...data.value.pipeline_value.map((s: any) => Number(s.total_value))));
const maxRevenue = computed(() => Math.max(1, ...data.value.revenue_by_month.map((d: any) => Number(d.revenue))));

function barWidth(value: number, max: number): number {
    return max > 0 ? Math.max(2, (value / max) * 100) : 0;
}

async function loadData() {
    loading.value = true;
    try {
        const res = await apiClient.get('/analytics/sales', { params: { months: months.value } });
        data.value = res.data?.data ?? data.value;
    } catch { /* ignore */ }
    loading.value = false;
}

onMounted(loadData);
</script>
