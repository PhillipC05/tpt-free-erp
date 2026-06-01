<template>
    <div>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100 mb-6">Financial Reports</h1>

        <!-- Report type tabs -->
        <div class="flex gap-1 mb-6 border-b border-gray-200 dark:border-gray-700">
            <button
                v-for="tab in tabs"
                :key="tab.id"
                @click="activeTab = tab.id; reportData = null"
                :class="[
                    'px-4 py-2 text-sm font-medium transition-colors border-b-2 -mb-px',
                    activeTab === tab.id
                        ? 'border-blue-600 text-blue-600 dark:text-blue-400'
                        : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300'
                ]"
            >{{ tab.label }}</button>
        </div>

        <!-- Filters row -->
        <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4 mb-6 flex flex-wrap items-end gap-4">
            <template v-if="activeTab === 'balance-sheet' || activeTab === 'trial-balance'">
                <div>
                    <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">As of date</label>
                    <input type="date" v-model="asOfDate" class="block rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-sm text-gray-900 dark:text-gray-100 px-3 py-1.5 focus:outline-none focus:ring-1 focus:ring-blue-500" />
                </div>
            </template>
            <template v-if="activeTab === 'income-statement'">
                <div>
                    <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Start date</label>
                    <input type="date" v-model="startDate" class="block rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-sm text-gray-900 dark:text-gray-100 px-3 py-1.5 focus:outline-none focus:ring-1 focus:ring-blue-500" />
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">End date</label>
                    <input type="date" v-model="endDate" class="block rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-sm text-gray-900 dark:text-gray-100 px-3 py-1.5 focus:outline-none focus:ring-1 focus:ring-blue-500" />
                </div>
            </template>
            <button
                v-if="activeTab !== 'cash-flow'"
                @click="runReport"
                :disabled="loading"
                class="inline-flex items-center gap-2 px-4 py-1.5 bg-blue-600 hover:bg-blue-700 disabled:opacity-60 text-white text-sm font-medium rounded-md transition-colors"
            >
                <svg v-if="loading" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                </svg>
                {{ loading ? 'Generating...' : 'Generate Report' }}
            </button>
            <button
                v-if="reportData"
                @click="exportCsv"
                class="inline-flex items-center gap-2 px-4 py-1.5 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 text-sm font-medium rounded-md transition-colors"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                Export CSV
            </button>
        </div>

        <!-- Error state -->
        <div v-if="error" class="mb-4 p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg text-sm text-red-700 dark:text-red-300">
            {{ error }}
        </div>

        <!-- ── BALANCE SHEET ────────────────────────────────── -->
        <div v-if="activeTab === 'balance-sheet' && reportData">
            <div class="text-center mb-4">
                <p class="text-xs text-gray-500 dark:text-gray-400">As of {{ reportData.date }}</p>
            </div>
            <div class="grid md:grid-cols-2 gap-6">
                <!-- Assets -->
                <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
                    <div class="px-4 py-3 bg-green-50 dark:bg-green-900/20 border-b border-gray-200 dark:border-gray-700">
                        <h3 class="text-sm font-semibold text-green-800 dark:text-green-300">Assets</h3>
                    </div>
                    <table class="w-full text-sm">
                        <tbody>
                            <tr v-for="item in reportData.assets.items" :key="item.account_code"
                                class="border-b border-gray-100 dark:border-gray-700/50">
                                <td class="px-4 py-2 text-gray-600 dark:text-gray-400">{{ item.account_code }}</td>
                                <td class="px-4 py-2 text-gray-900 dark:text-gray-100">{{ item.account_name }}</td>
                                <td class="px-4 py-2 text-right font-mono text-gray-900 dark:text-gray-100">{{ formatCurrency(item.balance) }}</td>
                            </tr>
                        </tbody>
                        <tfoot>
                            <tr class="bg-green-50 dark:bg-green-900/20 font-semibold">
                                <td colspan="2" class="px-4 py-2 text-green-800 dark:text-green-300">Total Assets</td>
                                <td class="px-4 py-2 text-right font-mono text-green-800 dark:text-green-300">{{ formatCurrency(reportData.assets.total) }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <!-- Liabilities + Equity -->
                <div class="space-y-4">
                    <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
                        <div class="px-4 py-3 bg-red-50 dark:bg-red-900/20 border-b border-gray-200 dark:border-gray-700">
                            <h3 class="text-sm font-semibold text-red-800 dark:text-red-300">Liabilities</h3>
                        </div>
                        <table class="w-full text-sm">
                            <tbody>
                                <tr v-for="item in reportData.liabilities.items" :key="item.account_code"
                                    class="border-b border-gray-100 dark:border-gray-700/50">
                                    <td class="px-4 py-2 text-gray-600 dark:text-gray-400">{{ item.account_code }}</td>
                                    <td class="px-4 py-2 text-gray-900 dark:text-gray-100">{{ item.account_name }}</td>
                                    <td class="px-4 py-2 text-right font-mono text-gray-900 dark:text-gray-100">{{ formatCurrency(item.balance) }}</td>
                                </tr>
                            </tbody>
                            <tfoot>
                                <tr class="bg-red-50 dark:bg-red-900/20 font-semibold">
                                    <td colspan="2" class="px-4 py-2 text-red-800 dark:text-red-300">Total Liabilities</td>
                                    <td class="px-4 py-2 text-right font-mono text-red-800 dark:text-red-300">{{ formatCurrency(reportData.liabilities.total) }}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                    <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
                        <div class="px-4 py-3 bg-blue-50 dark:bg-blue-900/20 border-b border-gray-200 dark:border-gray-700">
                            <h3 class="text-sm font-semibold text-blue-800 dark:text-blue-300">Equity</h3>
                        </div>
                        <table class="w-full text-sm">
                            <tbody>
                                <tr v-for="item in reportData.equity.items" :key="item.account_code"
                                    class="border-b border-gray-100 dark:border-gray-700/50">
                                    <td class="px-4 py-2 text-gray-600 dark:text-gray-400">{{ item.account_code }}</td>
                                    <td class="px-4 py-2 text-gray-900 dark:text-gray-100">{{ item.account_name }}</td>
                                    <td class="px-4 py-2 text-right font-mono text-gray-900 dark:text-gray-100">{{ formatCurrency(item.balance) }}</td>
                                </tr>
                            </tbody>
                            <tfoot>
                                <tr class="bg-blue-50 dark:bg-blue-900/20 font-semibold">
                                    <td colspan="2" class="px-4 py-2 text-blue-800 dark:text-blue-300">Total Equity</td>
                                    <td class="px-4 py-2 text-right font-mono text-blue-800 dark:text-blue-300">{{ formatCurrency(reportData.equity.total) }}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                    <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-4 flex justify-between items-center">
                        <span class="text-sm font-semibold text-gray-700 dark:text-gray-300">Total Liabilities & Equity</span>
                        <span class="font-mono font-bold text-gray-900 dark:text-gray-100">{{ formatCurrency(reportData.total_liabilities_equity) }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- ── INCOME STATEMENT ────────────────────────────── -->
        <div v-if="activeTab === 'income-statement' && reportData">
            <div class="text-center mb-4">
                <p class="text-xs text-gray-500 dark:text-gray-400">Period: {{ reportData.period.start }} to {{ reportData.period.end }}</p>
            </div>
            <div class="max-w-2xl mx-auto space-y-4">
                <!-- Revenue -->
                <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
                    <div class="px-4 py-3 bg-green-50 dark:bg-green-900/20 border-b border-gray-200 dark:border-gray-700">
                        <h3 class="text-sm font-semibold text-green-800 dark:text-green-300">Revenue</h3>
                    </div>
                    <table class="w-full text-sm">
                        <tbody>
                            <tr v-for="item in reportData.revenues" :key="item.account_code"
                                class="border-b border-gray-100 dark:border-gray-700/50">
                                <td class="px-4 py-2 text-gray-600 dark:text-gray-400">{{ item.account_code }}</td>
                                <td class="px-4 py-2 text-gray-900 dark:text-gray-100">{{ item.account_name }}</td>
                                <td class="px-4 py-2 text-right font-mono text-gray-900 dark:text-gray-100">{{ formatCurrency(item.balance) }}</td>
                            </tr>
                        </tbody>
                        <tfoot>
                            <tr class="bg-green-50 dark:bg-green-900/20 font-semibold">
                                <td colspan="2" class="px-4 py-2 text-green-800 dark:text-green-300">Total Revenue</td>
                                <td class="px-4 py-2 text-right font-mono text-green-800 dark:text-green-300">{{ formatCurrency(reportData.total_revenue) }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <!-- Expenses -->
                <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
                    <div class="px-4 py-3 bg-red-50 dark:bg-red-900/20 border-b border-gray-200 dark:border-gray-700">
                        <h3 class="text-sm font-semibold text-red-800 dark:text-red-300">Expenses</h3>
                    </div>
                    <table class="w-full text-sm">
                        <tbody>
                            <tr v-for="item in reportData.expenses" :key="item.account_code"
                                class="border-b border-gray-100 dark:border-gray-700/50">
                                <td class="px-4 py-2 text-gray-600 dark:text-gray-400">{{ item.account_code }}</td>
                                <td class="px-4 py-2 text-gray-900 dark:text-gray-100">{{ item.account_name }}</td>
                                <td class="px-4 py-2 text-right font-mono text-gray-900 dark:text-gray-100">{{ formatCurrency(item.balance) }}</td>
                            </tr>
                        </tbody>
                        <tfoot>
                            <tr class="bg-red-50 dark:bg-red-900/20 font-semibold">
                                <td colspan="2" class="px-4 py-2 text-red-800 dark:text-red-300">Total Expenses</td>
                                <td class="px-4 py-2 text-right font-mono text-red-800 dark:text-red-300">{{ formatCurrency(reportData.total_expense) }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <!-- Net Income -->
                <div :class="[
                    'rounded-lg p-4 flex justify-between items-center',
                    reportData.net_income >= 0
                        ? 'bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800'
                        : 'bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800'
                ]">
                    <span :class="['text-sm font-bold', reportData.net_income >= 0 ? 'text-green-800 dark:text-green-300' : 'text-red-800 dark:text-red-300']">
                        Net {{ reportData.net_income >= 0 ? 'Income' : 'Loss' }}
                    </span>
                    <span :class="['font-mono font-bold text-lg', reportData.net_income >= 0 ? 'text-green-800 dark:text-green-300' : 'text-red-800 dark:text-red-300']">
                        {{ formatCurrency(Math.abs(reportData.net_income)) }}
                    </span>
                </div>
            </div>
        </div>

        <!-- ── TRIAL BALANCE ───────────────────────────────── -->
        <div v-if="activeTab === 'trial-balance' && reportData">
            <div class="text-center mb-4">
                <p class="text-xs text-gray-500 dark:text-gray-400">As of {{ reportData.date }}</p>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Code</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Account</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Type</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Debits</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Credits</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Balance</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="row in reportData.accounts" :key="row.account_code"
                            class="border-b border-gray-100 dark:border-gray-700/50 hover:bg-gray-50 dark:hover:bg-gray-700/30">
                            <td class="px-4 py-2 text-gray-500 dark:text-gray-400 font-mono text-xs">{{ row.account_code }}</td>
                            <td class="px-4 py-2 text-gray-900 dark:text-gray-100">{{ row.account_name }}</td>
                            <td class="px-4 py-2">
                                <span :class="['text-xs px-2 py-0.5 rounded-full capitalize', typeClass(row.type)]">{{ row.type }}</span>
                            </td>
                            <td class="px-4 py-2 text-right font-mono text-gray-900 dark:text-gray-100">{{ formatCurrency(row.total_debits) }}</td>
                            <td class="px-4 py-2 text-right font-mono text-gray-900 dark:text-gray-100">{{ formatCurrency(row.total_credits) }}</td>
                            <td :class="['px-4 py-2 text-right font-mono font-medium', row.balance >= 0 ? 'text-gray-900 dark:text-gray-100' : 'text-red-600 dark:text-red-400']">
                                {{ formatCurrency(row.balance) }}
                            </td>
                        </tr>
                    </tbody>
                    <tfoot class="bg-gray-50 dark:bg-gray-700 font-semibold">
                        <tr>
                            <td colspan="3" class="px-4 py-3 text-gray-700 dark:text-gray-300">Totals</td>
                            <td class="px-4 py-3 text-right font-mono text-gray-900 dark:text-gray-100">{{ formatCurrency(reportData.totals.total_debits) }}</td>
                            <td class="px-4 py-3 text-right font-mono text-gray-900 dark:text-gray-100">{{ formatCurrency(reportData.totals.total_credits) }}</td>
                            <td :class="['px-4 py-3 text-right font-mono', Math.abs(reportData.totals.difference) < 0.01 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400']">
                                {{ formatCurrency(reportData.totals.difference) }}
                                <span v-if="Math.abs(reportData.totals.difference) < 0.01" class="text-xs ml-1">✓</span>
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        <!-- ── CASH FLOW (placeholder) ─────────────────────── -->
        <div v-if="activeTab === 'cash-flow'" class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-12 text-center">
            <svg class="w-12 h-12 text-gray-300 dark:text-gray-600 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
            </svg>
            <p class="text-gray-500 dark:text-gray-400 font-medium">Cash Flow Statement</p>
            <p class="text-sm text-gray-400 dark:text-gray-500 mt-1">Coming soon — requires journal entry classification by activity type.</p>
        </div>

        <!-- Empty state when no report run yet -->
        <div v-if="!reportData && !loading && !error && activeTab !== 'cash-flow'" class="bg-white dark:bg-gray-800 rounded-lg border border-dashed border-gray-300 dark:border-gray-600 p-12 text-center">
            <svg class="w-10 h-10 text-gray-300 dark:text-gray-600 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
            <p class="text-sm text-gray-500 dark:text-gray-400">Select a date range and click <strong>Generate Report</strong></p>
        </div>
    </div>
</template>

<script setup lang="ts">
import { ref } from 'vue';
import api from '@/api/axios';

const tabs = [
    { id: 'balance-sheet', label: 'Balance Sheet' },
    { id: 'income-statement', label: 'Income Statement' },
    { id: 'trial-balance', label: 'Trial Balance' },
    { id: 'cash-flow', label: 'Cash Flow' },
];

const activeTab = ref('balance-sheet');
const loading = ref(false);
const error = ref('');
const reportData = ref<any>(null);

const today = new Date().toISOString().split('T')[0];
const yearStart = `${new Date().getFullYear()}-01-01`;

const asOfDate = ref(today);
const startDate = ref(yearStart);
const endDate = ref(today);

const ENDPOINTS: Record<string, string> = {
    'balance-sheet': '/finance/reports/balance-sheet',
    'income-statement': '/finance/reports/income-statement',
    'trial-balance': '/finance/reports/trial-balance',
};

async function runReport() {
    if (!ENDPOINTS[activeTab.value]) return;
    loading.value = true;
    error.value = '';
    reportData.value = null;

    const params: Record<string, string> = {};
    if (activeTab.value === 'income-statement') {
        params.start_date = startDate.value;
        params.end_date = endDate.value;
    } else {
        params.date = asOfDate.value;
    }

    try {
        const res = await api.get(ENDPOINTS[activeTab.value], { params });
        reportData.value = res.data?.data ?? res.data;
    } catch (e: any) {
        error.value = e?.response?.data?.message ?? 'Failed to generate report.';
    } finally {
        loading.value = false;
    }
}

function formatCurrency(val: number): string {
    if (val === undefined || val === null) return '—';
    return new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(val);
}

function typeClass(type: string): string {
    const map: Record<string, string> = {
        asset: 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-300',
        liability: 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-300',
        equity: 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300',
        revenue: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300',
        expense: 'bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-300',
    };
    return map[type] ?? 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300';
}

function exportCsv() {
    if (!reportData.value) return;
    let rows: string[][] = [];

    if (activeTab.value === 'trial-balance') {
        rows.push(['Code', 'Account', 'Type', 'Debits', 'Credits', 'Balance']);
        reportData.value.accounts.forEach((r: any) => {
            rows.push([r.account_code, r.account_name, r.type, r.total_debits, r.total_credits, r.balance]);
        });
        rows.push(['', 'TOTALS', '', reportData.value.totals.total_debits, reportData.value.totals.total_credits, reportData.value.totals.difference]);
    } else if (activeTab.value === 'income-statement') {
        rows.push(['Code', 'Account', 'Amount']);
        rows.push(['', '=== REVENUE ===', '']);
        reportData.value.revenues.forEach((r: any) => rows.push([r.account_code, r.account_name, r.balance]));
        rows.push(['', 'Total Revenue', reportData.value.total_revenue]);
        rows.push(['', '=== EXPENSES ===', '']);
        reportData.value.expenses.forEach((r: any) => rows.push([r.account_code, r.account_name, r.balance]));
        rows.push(['', 'Total Expenses', reportData.value.total_expense]);
        rows.push(['', 'Net Income', reportData.value.net_income]);
    } else if (activeTab.value === 'balance-sheet') {
        rows.push(['Code', 'Account', 'Amount']);
        rows.push(['', '=== ASSETS ===', '']);
        reportData.value.assets.items.forEach((r: any) => rows.push([r.account_code, r.account_name, r.balance]));
        rows.push(['', 'Total Assets', reportData.value.assets.total]);
        rows.push(['', '=== LIABILITIES ===', '']);
        reportData.value.liabilities.items.forEach((r: any) => rows.push([r.account_code, r.account_name, r.balance]));
        rows.push(['', 'Total Liabilities', reportData.value.liabilities.total]);
        rows.push(['', '=== EQUITY ===', '']);
        reportData.value.equity.items.forEach((r: any) => rows.push([r.account_code, r.account_name, r.balance]));
        rows.push(['', 'Total Equity', reportData.value.equity.total]);
    }

    const csv = rows.map(r => r.map(c => `"${String(c).replace(/"/g, '""')}"`).join(',')).join('\n');
    const blob = new Blob([csv], { type: 'text/csv' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = `${activeTab.value}-report.csv`;
    a.click();
    URL.revokeObjectURL(url);
}
</script>
