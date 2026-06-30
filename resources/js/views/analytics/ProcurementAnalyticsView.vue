<template>
    <div>
        <div class="flex items-center justify-between mb-6">
            <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">Procurement Analytics</h1>
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
                    <div class="text-xs text-gray-500 dark:text-gray-400 mb-1">Total Spend</div>
                    <div class="text-xl font-bold text-gray-900 dark:text-gray-100">${{ totalSpend.toLocaleString() }}</div>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
                    <div class="text-xs text-gray-500 dark:text-gray-400 mb-1">Active Vendors</div>
                    <div class="text-xl font-bold text-blue-600">{{ data.po_value_by_vendor?.length ?? 0 }}</div>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
                    <div class="text-xs text-gray-500 dark:text-gray-400 mb-1">Delivery Performance</div>
                    <div class="text-xl font-bold" :class="data.delivery_performance >= 80 ? 'text-green-600' : 'text-yellow-600'">{{ data.delivery_performance }}%</div>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-6 mb-6">
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
                    <h3 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">PO Value by Vendor</h3>
                    <div class="space-y-3">
                        <div v-for="v in data.po_value_by_vendor" :key="v.id">
                            <div class="flex items-center justify-between text-sm mb-1">
                                <span class="text-gray-900 dark:text-gray-100 truncate">{{ v.name }}</span>
                                <span class="text-gray-500">${{ Number(v.total_spend).toLocaleString() }} ({{ v.po_count }} POs)</span>
                            </div>
                            <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-3 overflow-hidden">
                                <div class="bg-blue-500 h-full rounded-full" :style="{ width: barWidth(v.total_spend, maxVendorSpend) + '%' }"></div>
                            </div>
                        </div>
                        <div v-if="!data.po_value_by_vendor?.length" class="text-sm text-gray-500 text-center py-4">No vendor data</div>
                    </div>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
                    <h3 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">Spend by Category</h3>
                    <div class="space-y-3">
                        <div v-for="c in data.spend_by_category" :key="c.category">
                            <div class="flex items-center justify-between text-sm mb-1">
                                <span class="text-gray-900 dark:text-gray-100">{{ c.category }}</span>
                                <span class="text-gray-500">${{ Number(c.total_spend).toLocaleString() }}</span>
                            </div>
                            <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-3 overflow-hidden">
                                <div class="bg-purple-500 h-full rounded-full" :style="{ width: barWidth(c.total_spend, maxCategorySpend) + '%' }"></div>
                            </div>
                        </div>
                        <div v-if="!data.spend_by_category?.length" class="text-sm text-gray-500 text-center py-4">No category data</div>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4 mb-6">
                <h3 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">Spend by Month</h3>
                <div class="space-y-2">
                    <div v-for="d in data.spend_by_month" :key="d.month" class="flex items-center gap-3">
                        <span class="text-xs text-gray-500 w-14">{{ d.month }}</span>
                        <div class="flex-1 bg-gray-200 dark:bg-gray-700 rounded-full h-4 overflow-hidden">
                            <div class="bg-orange-500 h-full rounded-full" :style="{ width: barWidth(d.spend, maxMonthlySpend) + '%' }"></div>
                        </div>
                        <span class="text-xs font-medium text-gray-700 dark:text-gray-300 w-20 text-right">${{ Number(d.spend).toLocaleString() }}</span>
                        <span class="text-xs text-gray-400 w-10 text-right">{{ d.po_count }} POs</span>
                    </div>
                    <div v-if="!data.spend_by_month?.length" class="text-sm text-gray-500 text-center py-4">No spend data</div>
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
    po_value_by_vendor: [],
    spend_by_category: [],
    delivery_performance: 0,
    spend_by_month: [],
});

const totalSpend = computed(() => data.value.spend_by_month.reduce((sum: number, d: any) => sum + Number(d.spend), 0));
const maxVendorSpend = computed(() => Math.max(1, ...data.value.po_value_by_vendor.map((v: any) => Number(v.total_spend))));
const maxCategorySpend = computed(() => Math.max(1, ...data.value.spend_by_category.map((c: any) => Number(c.total_spend))));
const maxMonthlySpend = computed(() => Math.max(1, ...data.value.spend_by_month.map((d: any) => Number(d.spend))));

function barWidth(value: number, max: number): number {
    return max > 0 ? Math.max(2, (value / max) * 100) : 0;
}

async function loadData() {
    loading.value = true;
    try {
        const res = await apiClient.get('/analytics/procurement', { params: { months: months.value } });
        data.value = res.data?.data ?? data.value;
    } catch { /* ignore */ }
    loading.value = false;
}

onMounted(loadData);
</script>
