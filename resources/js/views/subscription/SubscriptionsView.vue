<template>
    <div>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100 mb-6">Subscriptions</h1>

        <div v-if="dashboard" class="grid grid-cols-5 gap-4 mb-6">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
                <div class="text-sm text-gray-500 dark:text-gray-400">MRR</div>
                <div class="text-2xl font-bold text-green-600">${{ dashboard.mrr.toLocaleString(undefined, { minimumFractionDigits: 2 }) }}</div>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
                <div class="text-sm text-gray-500 dark:text-gray-400">Active</div>
                <div class="text-2xl font-bold text-blue-600">{{ dashboard.active_count }}</div>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
                <div class="text-sm text-gray-500 dark:text-gray-400">Trialing</div>
                <div class="text-2xl font-bold text-yellow-600">{{ dashboard.trialing_count }}</div>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
                <div class="text-sm text-gray-500 dark:text-gray-400">Cancelled (3mo)</div>
                <div class="text-2xl font-bold text-red-600">{{ dashboard.cancelled_recent }}</div>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
                <div class="text-sm text-gray-500 dark:text-gray-400">Churn Rate</div>
                <div class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ dashboard.churn_rate_percent }}%</div>
            </div>
        </div>

        <div class="flex items-center gap-4 mb-4">
            <button @click="tab = 'plans'" :class="tab === 'plans' ? 'bg-blue-600 text-white' : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 border border-gray-300 dark:border-gray-600'" class="px-4 py-2 text-sm rounded-md">Plans</button>
            <button @click="tab = 'subscriptions'" :class="tab === 'subscriptions' ? 'bg-blue-600 text-white' : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 border border-gray-300 dark:border-gray-600'" class="px-4 py-2 text-sm rounded-md">Subscriptions</button>
            <button @click="tab = 'usage'" :class="tab === 'usage' ? 'bg-blue-600 text-white' : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 border border-gray-300 dark:border-gray-600'" class="px-4 py-2 text-sm rounded-md">Usage</button>
        </div>

        <div v-if="tab === 'plans'">
            <div class="grid grid-cols-3 gap-6 mb-6">
                <div v-for="plan in plans" :key="plan.id" class="bg-white dark:bg-gray-800 rounded-lg shadow p-5 border-2" :class="plan.is_active ? 'border-transparent' : 'border-gray-200 opacity-60'">
                    <div class="flex items-center justify-between mb-3">
                        <h3 class="text-lg font-bold text-gray-900 dark:text-gray-100">{{ plan.name }}</h3>
                        <span class="text-xs px-2 py-1 rounded-full" :class="plan.is_active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500'">{{ plan.is_active ? 'Active' : 'Inactive' }}</span>
                    </div>
                    <div class="text-3xl font-bold text-gray-900 dark:text-gray-100 mb-1">${{ plan.price }}<span class="text-sm font-normal text-gray-500">/{{ plan.billing_interval === 'monthly' ? 'mo' : plan.billing_interval === 'quarterly' ? 'qtr' : 'yr' }}</span></div>
                    <div class="text-sm text-gray-500 mb-3">{{ plan.billing_interval }}</div>
                    <div v-if="plan.trial_days" class="text-xs text-blue-600 mb-2">{{ plan.trial_days }}-day free trial</div>
                    <div v-if="plan.max_users" class="text-xs text-gray-500">Up to {{ plan.max_users }} users</div>
                    <div v-if="plan.features" class="mt-3 space-y-1">
                        <div v-for="f in plan.features" :key="f" class="text-xs text-gray-600 dark:text-gray-400">✓ {{ f }}</div>
                    </div>
                </div>
            </div>
        </div>

        <div v-if="tab === 'subscriptions'">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs text-gray-500">Number</th>
                            <th class="px-4 py-3 text-left text-xs text-gray-500">Customer</th>
                            <th class="px-4 py-3 text-left text-xs text-gray-500">Plan</th>
                            <th class="px-4 py-3 text-left text-xs text-gray-500">Status</th>
                            <th class="px-4 py-3 text-left text-xs text-gray-500">Period End</th>
                            <th class="px-4 py-3 text-right text-xs text-gray-500">Qty</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        <tr v-for="s in subscriptions" :key="s.id" class="hover:bg-gray-50 dark:hover:bg-gray-700">
                            <td class="px-4 py-3 font-medium text-gray-900 dark:text-gray-100">{{ s.subscription_number }}</td>
                            <td class="px-4 py-3 text-gray-700 dark:text-gray-300">{{ s.customer?.name }}</td>
                            <td class="px-4 py-3 text-gray-700 dark:text-gray-300">{{ s.plan?.name }}</td>
                            <td class="px-4 py-3"><span class="text-xs px-2 py-1 rounded-full" :class="statusClass(s.status)">{{ s.status }}</span></td>
                            <td class="px-4 py-3 text-gray-700 dark:text-gray-300">{{ s.current_period_end }}</td>
                            <td class="px-4 py-3 text-right text-gray-700 dark:text-gray-300">{{ s.quantity }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div v-if="tab === 'usage'">
            <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">Select a subscription to view usage details.</p>
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs text-gray-500">Subscription</th>
                            <th class="px-4 py-3 text-left text-xs text-gray-500">Type</th>
                            <th class="px-4 py-3 text-right text-xs text-gray-500">Quantity</th>
                            <th class="px-4 py-3 text-right text-xs text-gray-500">Cost</th>
                            <th class="px-4 py-3 text-left text-xs text-gray-500">Date</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        <tr v-for="u in usageRecords" :key="u.id" class="hover:bg-gray-50 dark:hover:bg-gray-700">
                            <td class="px-4 py-3 text-gray-900 dark:text-gray-100">{{ u.subscription?.subscription_number }}</td>
                            <td class="px-4 py-3 text-gray-700 dark:text-gray-300">{{ u.usage_type }}</td>
                            <td class="px-4 py-3 text-right text-gray-700 dark:text-gray-300">{{ u.quantity.toLocaleString() }}</td>
                            <td class="px-4 py-3 text-right text-gray-700 dark:text-gray-300">${{ u.total_cost.toFixed(2) }}</td>
                            <td class="px-4 py-3 text-gray-700 dark:text-gray-300">{{ new Date(u.recorded_at).toLocaleDateString() }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';
import apiClient from '@/api/axios';
import type { SubscriptionPlan, SubscriptionRecord, SubscriptionUsageRecord, SubscriptionDashboard } from '@/types';

const tab = ref('plans');
const plans = ref<SubscriptionPlan[]>([]);
const subscriptions = ref<SubscriptionRecord[]>([]);
const usageRecords = ref<SubscriptionUsageRecord[]>([]);
const dashboard = ref<SubscriptionDashboard | null>(null);

function statusClass(status: string): string {
    const map: Record<string, string> = {
        active: 'bg-green-100 text-green-700',
        trialing: 'bg-yellow-100 text-yellow-700',
        past_due: 'bg-orange-100 text-orange-700',
        cancelled: 'bg-red-100 text-red-700',
        suspended: 'bg-gray-100 text-gray-500',
    };
    return map[status] ?? 'bg-gray-100 text-gray-500';
}

async function loadData() {
    try {
        const [plansRes, subsRes, usageRes, dashRes] = await Promise.all([
            apiClient.get('/subscription/plans'),
            apiClient.get('/subscription/subscriptions?per_page=50'),
            apiClient.get('/subscription/usage?per_page=50'),
            apiClient.get('/subscription/subscriptions/dashboard'),
        ]);
        plans.value = plansRes.data?.data ?? [];
        subscriptions.value = subsRes.data?.data ?? [];
        usageRecords.value = usageRes.data?.data ?? [];
        dashboard.value = dashRes.data?.data ?? null;
    } catch { /* ignore */ }
}

onMounted(loadData);
</script>
