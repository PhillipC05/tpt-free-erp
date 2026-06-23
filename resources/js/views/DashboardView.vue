<template>
    <div>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100 mb-6">Dashboard</h1>

        <!-- Loading state -->
        <div v-if="loading" class="flex items-center justify-center h-48">
            <div class="w-8 h-8 border-2 border-blue-500 border-t-transparent rounded-full animate-spin" />
        </div>

        <template v-else>
            <!-- KPI Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
                <div v-for="kpi in kpis" :key="kpi.label" class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-500 dark:text-gray-400">{{ kpi.label }}</p>
                            <p class="text-2xl font-bold text-gray-900 dark:text-gray-100 mt-1">{{ kpi.value }}</p>
                            <p v-if="kpi.sub" class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">{{ kpi.sub }}</p>
                        </div>
                        <div :class="kpi.color" class="p-3 rounded-full">
                            <span v-html="kpi.icon" class="w-6 h-6 text-white block" />
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Transactions + Projects row -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                <!-- Recent Transactions -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                    <h2 class="text-base font-semibold text-gray-900 dark:text-gray-100 mb-4">Recent Transactions</h2>
                    <div v-if="recentTransactions.length === 0" class="text-sm text-gray-400 dark:text-gray-500 py-4 text-center">
                        No transactions yet
                    </div>
                    <ul v-else class="divide-y divide-gray-100 dark:divide-gray-700">
                        <li v-for="tx in recentTransactions" :key="tx.id" class="flex items-center justify-between py-2">
                            <div class="min-w-0">
                                <p class="text-sm text-gray-900 dark:text-gray-100 truncate">{{ tx.description }}</p>
                                <p class="text-xs text-gray-400 dark:text-gray-500">{{ tx.account?.name ?? '—' }}</p>
                            </div>
                            <span :class="[
                                'ml-4 text-sm font-mono font-medium flex-shrink-0',
                                tx.type === 'credit' ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400'
                            ]">
                                {{ tx.type === 'credit' ? '+' : '-' }}{{ formatCurrency(tx.amount) }}
                            </span>
                        </li>
                    </ul>
                </div>

                <!-- Project Summary -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                    <h2 class="text-base font-semibold text-gray-900 dark:text-gray-100 mb-4">Projects Overview</h2>
                    <div class="grid grid-cols-2 gap-4">
                        <div v-for="stat in projectStats" :key="stat.label" class="text-center p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                            <p class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ stat.value }}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ stat.label }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Module Quick Access -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <h2 class="text-base font-semibold text-gray-900 dark:text-gray-100 mb-4">Quick Access</h2>
                <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">
                    <router-link
                        v-for="module in modules"
                        :key="module.to"
                        :to="module.to"
                        class="flex flex-col items-center p-4 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors"
                    >
                        <span v-html="module.icon" class="w-8 h-8 text-blue-600 dark:text-blue-400 mb-2 block" />
                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ module.label }}</span>
                    </router-link>
                </div>
            </div>
        </template>
    </div>
</template>

<script setup lang="ts">
import { ref, onMounted, computed } from 'vue';
import api from '@/api/axios';

const loading = ref(true);
const dashData = ref<any>(null);

async function fetchDashboard() {
    try {
        const res = await api.get('/dashboard');
        dashData.value = res.data?.data ?? null;
    } catch {
        // silently ignore — kpis will show zeros
    } finally {
        loading.value = false;
    }
}

onMounted(fetchDashboard);

function formatCurrency(v: number | null | undefined): string {
    if (v == null) return '$0.00';
    return new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(v);
}

const kpis = computed(() => {
    const finance  = dashData.value?.finance  ?? {};
    const hr       = dashData.value?.hr       ?? {};
    const sales    = dashData.value?.sales    ?? {};
    const inv      = dashData.value?.inventory ?? {};

    return [
        {
            label: 'Total Revenue',
            value: formatCurrency(finance.total_revenue ?? 0),
            sub: finance.total_expenses != null ? `Expenses: ${formatCurrency(finance.total_expenses)}` : undefined,
            color: 'bg-green-500',
            icon: '<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>',
        },
        {
            label: 'Active Employees',
            value: hr.active_employees ?? 0,
            sub: hr.on_leave_today != null ? `On leave today: ${hr.on_leave_today}` : undefined,
            color: 'bg-orange-500',
            icon: '<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" /></svg>',
        },
        {
            label: 'Pending Orders',
            value: sales.pending_orders ?? 0,
            sub: sales.total_customers != null ? `${sales.total_customers} customers` : undefined,
            color: 'bg-blue-500',
            icon: '<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" /></svg>',
        },
        {
            label: 'Products',
            value: inv.total_products ?? 0,
            sub: inv.low_stock_products != null ? `${inv.low_stock_products} low stock` : undefined,
            color: 'bg-purple-500',
            icon: '<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" /></svg>',
        },
    ];
});

const recentTransactions = computed(() => {
    return dashData.value?.finance?.recent_transactions?.slice(0, 5) ?? [];
});

const projectStats = computed(() => {
    const p = dashData.value?.projects ?? {};
    return [
        { label: 'Active Projects',  value: p.active_projects  ?? 0 },
        { label: 'Total Projects',   value: p.total_projects   ?? 0 },
        { label: 'Pending Tasks',    value: p.pending_tasks    ?? 0 },
        { label: 'Completed Tasks',  value: p.completed_tasks  ?? 0 },
    ];
});

const modules = [
    { to: '/finance/accounts',       label: 'Finance',      icon: '<svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>' },
    { to: '/inventory/products',     label: 'Inventory',    icon: '<svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" /></svg>' },
    { to: '/hr/employees',           label: 'HR',           icon: '<svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857" /></svg>' },
    { to: '/sales/customers',        label: 'Sales',        icon: '<svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" /></svg>' },
    { to: '/procurement/vendors',    label: 'Procurement',  icon: '<svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2" /></svg>' },
    { to: '/projects/projects',      label: 'Projects',     icon: '<svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" /></svg>' },
];
</script>
