<template>
    <div>
        <div class="flex items-center justify-between mb-6">
            <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">Finance Analytics</h1>
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
                    <h3 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">Revenue by Account</h3>
                    <div class="space-y-2">
                        <div v-for="d in data.revenue_by_account" :key="d.id" class="flex items-center gap-3">
                            <span class="text-xs text-gray-500 w-20 truncate">{{ d.name }}</span>
                            <div class="flex-1 bg-gray-200 dark:bg-gray-700 rounded-full h-4 overflow-hidden">
                                <div class="bg-green-500 h-full rounded-full" :style="{ width: barWidth(d.value, maxRevenue) + '%' }"></div>
                            </div>
                            <span class="text-xs font-medium text-gray-700 dark:text-gray-300 w-24 text-right">${{ Number(d.value).toLocaleString() }}</span>
                        </div>
                        <div v-if="!data.revenue_by_account?.length" class="text-sm text-gray-500 text-center py-4">No revenue accounts</div>
                    </div>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
                    <h3 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">Expenses by Account</h3>
                    <div class="space-y-2">
                        <div v-for="d in data.expense_by_category" :key="d.id" class="flex items-center gap-3">
                            <span class="text-xs text-gray-500 w-20 truncate">{{ d.name }}</span>
                            <div class="flex-1 bg-gray-200 dark:bg-gray-700 rounded-full h-4 overflow-hidden">
                                <div class="bg-red-500 h-full rounded-full" :style="{ width: barWidth(d.value, maxExpense) + '%' }"></div>
                            </div>
                            <span class="text-xs font-medium text-gray-700 dark:text-gray-300 w-24 text-right">${{ Number(d.value).toLocaleString() }}</span>
                        </div>
                        <div v-if="!data.expense_by_category?.length" class="text-sm text-gray-500 text-center py-4">No expense accounts</div>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-6 mb-6">
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
                    <h3 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">Budget Utilization</h3>
                    <div class="space-y-3">
                        <div v-for="b in data.budget_utilization" :key="b.id">
                            <div class="flex items-center justify-between text-sm mb-1">
                                <span class="text-gray-900 dark:text-gray-100 truncate">{{ b.name }}</span>
                                <span class="text-gray-500">{{ b.utilization_percent }}%</span>
                            </div>
                            <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-3 overflow-hidden">
                                <div
                                    class="h-full rounded-full transition-all"
                                    :class="b.utilization_percent > 100 ? 'bg-red-500' : b.utilization_percent > 80 ? 'bg-yellow-500' : 'bg-blue-500'"
                                    :style="{ width: Math.min(b.utilization_percent, 100) + '%' }"
                                ></div>
                            </div>
                            <div class="flex justify-between text-xs text-gray-500 mt-0.5">
                                <span>${{ Number(b.actual_amount).toLocaleString() }} spent</span>
                                <span>${{ Number(b.budgeted_amount).toLocaleString() }} budgeted</span>
                            </div>
                        </div>
                        <div v-if="!data.budget_utilization?.length" class="text-sm text-gray-500 text-center py-4">No active budgets</div>
                    </div>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
                    <h3 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">Cash Flow Trend</h3>
                    <div class="space-y-2">
                        <div v-for="d in data.cash_flow_trend" :key="d.month" class="flex items-center gap-3">
                            <span class="text-xs text-gray-500 w-14">{{ d.month }}</span>
                            <div class="flex-1 space-y-1">
                                <div class="flex items-center gap-2">
                                    <div class="flex-1 bg-gray-200 dark:bg-gray-700 rounded-full h-3 overflow-hidden">
                                        <div class="bg-green-500 h-full rounded-full" :style="{ width: barWidth(d.credits, maxCashFlow) + '%' }"></div>
                                    </div>
                                    <span class="text-xs text-green-600 w-16 text-right">${{ Number(d.credits).toLocaleString() }}</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <div class="flex-1 bg-gray-200 dark:bg-gray-700 rounded-full h-3 overflow-hidden">
                                        <div class="bg-red-500 h-full rounded-full" :style="{ width: barWidth(d.debits, maxCashFlow) + '%' }"></div>
                                    </div>
                                    <span class="text-xs text-red-600 w-16 text-right">${{ Number(d.debits).toLocaleString() }}</span>
                                </div>
                            </div>
                        </div>
                        <div v-if="!data.cash_flow_trend?.length" class="text-sm text-gray-500 text-center py-4">No cash flow data</div>
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
    revenue_by_account: [],
    expense_by_category: [],
    budget_utilization: [],
    cash_flow_trend: [],
});

const maxRevenue = computed(() => Math.max(1, ...data.value.revenue_by_account.map((d: any) => Number(d.value))));
const maxExpense = computed(() => Math.max(1, ...data.value.expense_by_category.map((d: any) => Number(d.value))));
const maxCashFlow = computed(() => Math.max(1, ...data.value.cash_flow_trend.flatMap((d: any) => [Number(d.credits), Number(d.debits)])));

function barWidth(value: number, max: number): number {
    return max > 0 ? Math.max(2, (value / max) * 100) : 0;
}

async function loadData() {
    loading.value = true;
    try {
        const res = await apiClient.get('/analytics/finance', { params: { months: months.value } });
        data.value = res.data?.data ?? data.value;
    } catch { /* ignore */ }
    loading.value = false;
}

onMounted(loadData);
</script>
