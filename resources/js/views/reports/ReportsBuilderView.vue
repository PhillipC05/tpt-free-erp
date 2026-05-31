<template>
    <div>
        <div class="mb-6 flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">Reports Builder</h1>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Generate cross-module reports with custom date ranges and filters</p>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
            <!-- Config panel -->
            <div class="lg:col-span-1 space-y-4">
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
                    <h2 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3 uppercase tracking-wider">Report Type</h2>
                    <div class="space-y-1">
                        <button
                            v-for="report in availableReports"
                            :key="report.id"
                            @click="selectReport(report)"
                            :class="[
                                'w-full text-left px-3 py-2 rounded-md text-sm transition-colors',
                                selectedReport?.id === report.id
                                    ? 'bg-blue-100 dark:bg-blue-900 text-blue-700 dark:text-blue-300 font-medium'
                                    : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700'
                            ]"
                        >
                            <span class="mr-2">{{ report.icon }}</span>{{ report.label }}
                        </button>
                    </div>
                </div>

                <!-- Filters -->
                <div v-if="selectedReport" class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
                    <h2 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3 uppercase tracking-wider">Filters</h2>

                    <div class="space-y-3">
                        <div>
                            <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">Date From</label>
                            <input
                                v-model="filters.startDate"
                                type="date"
                                class="w-full text-sm border border-gray-300 dark:border-gray-600 rounded px-2 py-1.5 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100"
                            />
                        </div>
                        <div>
                            <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">Date To</label>
                            <input
                                v-model="filters.endDate"
                                type="date"
                                class="w-full text-sm border border-gray-300 dark:border-gray-600 rounded px-2 py-1.5 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100"
                            />
                        </div>

                        <div v-for="filter in selectedReport.filters" :key="filter.key">
                            <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">{{ filter.label }}</label>
                            <select
                                v-if="filter.type === 'select'"
                                v-model="filters.extra[filter.key]"
                                class="w-full text-sm border border-gray-300 dark:border-gray-600 rounded px-2 py-1.5 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100"
                            >
                                <option value="">All</option>
                                <option v-for="opt in filter.options" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
                            </select>
                        </div>

                        <div class="flex gap-2 pt-2">
                            <button
                                @click="generateReport"
                                :disabled="loading"
                                class="flex-1 px-3 py-2 bg-blue-600 text-white text-sm rounded-md hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed"
                            >
                                {{ loading ? 'Generating...' : 'Generate' }}
                            </button>
                            <button
                                v-if="reportData.length"
                                @click="exportCsv"
                                class="px-3 py-2 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 text-sm rounded-md hover:bg-gray-50 dark:hover:bg-gray-700"
                                title="Export CSV"
                            >
                                ↓
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Quick date presets -->
                <div v-if="selectedReport" class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
                    <h2 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3 uppercase tracking-wider">Quick Range</h2>
                    <div class="grid grid-cols-2 gap-1">
                        <button
                            v-for="preset in datePresets"
                            :key="preset.label"
                            @click="applyPreset(preset)"
                            class="text-xs px-2 py-1.5 border border-gray-200 dark:border-gray-600 rounded text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700"
                        >
                            {{ preset.label }}
                        </button>
                    </div>
                </div>
            </div>

            <!-- Results panel -->
            <div class="lg:col-span-3">
                <!-- Placeholder when no report selected -->
                <div v-if="!selectedReport" class="bg-white dark:bg-gray-800 rounded-lg shadow p-12 text-center">
                    <div class="text-4xl mb-4">📊</div>
                    <h3 class="text-lg font-medium text-gray-700 dark:text-gray-300 mb-2">Select a report type</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Choose a report from the panel on the left, set your filters, and click Generate.</p>
                </div>

                <!-- Loading state -->
                <div v-else-if="loading" class="bg-white dark:bg-gray-800 rounded-lg shadow p-12 text-center">
                    <div class="animate-spin text-4xl mb-4">⏳</div>
                    <p class="text-gray-500 dark:text-gray-400 text-sm">Generating report...</p>
                </div>

                <!-- Error state -->
                <div v-else-if="error" class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                    <div class="flex items-center gap-3 text-red-600 dark:text-red-400">
                        <span class="text-xl">⚠️</span>
                        <p class="text-sm">{{ error }}</p>
                    </div>
                </div>

                <!-- Summary cards -->
                <template v-else-if="reportData.length">
                    <div v-if="summaryCards.length" class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-4">
                        <div
                            v-for="card in summaryCards"
                            :key="card.label"
                            class="bg-white dark:bg-gray-800 rounded-lg shadow p-4"
                        >
                            <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ card.label }}</p>
                            <p class="text-xl font-bold text-gray-900 dark:text-gray-100 mt-1">{{ card.value }}</p>
                        </div>
                    </div>

                    <!-- Data table -->
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
                        <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                            <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300">
                                {{ selectedReport.label }}
                                <span class="ml-2 text-xs font-normal text-gray-400">({{ reportData.length }} rows)</span>
                            </h3>
                            <span class="text-xs text-gray-400">{{ filters.startDate }} — {{ filters.endDate }}</span>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm">
                                <thead class="bg-gray-50 dark:bg-gray-700">
                                    <tr>
                                        <th
                                            v-for="col in reportColumns"
                                            :key="col.key"
                                            @click="sortBy(col.key)"
                                            class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider cursor-pointer hover:text-gray-700 dark:hover:text-gray-200 select-none"
                                        >
                                            {{ col.label }}
                                            <span v-if="sort.key === col.key" class="ml-1">{{ sort.asc ? '↑' : '↓' }}</span>
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                    <tr
                                        v-for="(row, idx) in sortedData"
                                        :key="idx"
                                        class="hover:bg-gray-50 dark:hover:bg-gray-750"
                                    >
                                        <td
                                            v-for="col in reportColumns"
                                            :key="col.key"
                                            class="px-4 py-2 text-gray-900 dark:text-gray-100 whitespace-nowrap"
                                        >
                                            <span v-if="col.type === 'currency'">{{ formatCurrency(row[col.key]) }}</span>
                                            <span v-else-if="col.type === 'date'">{{ formatDate(row[col.key]) }}</span>
                                            <span
                                                v-else-if="col.type === 'badge'"
                                                :class="badgeClass(row[col.key])"
                                                class="px-2 py-0.5 rounded-full text-xs font-medium"
                                            >{{ row[col.key] }}</span>
                                            <span v-else>{{ row[col.key] ?? '—' }}</span>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </template>

                <!-- Empty state after generate -->
                <div v-else-if="generated && !reportData.length" class="bg-white dark:bg-gray-800 rounded-lg shadow p-12 text-center">
                    <div class="text-4xl mb-4">🔍</div>
                    <p class="text-gray-500 dark:text-gray-400 text-sm">No data found for the selected filters.</p>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { ref, computed, reactive } from 'vue';
import apiClient from '@/api/axios';

interface ReportFilter { key: string; label: string; type: 'select'; options: { value: string; label: string }[] }
interface ReportDefinition {
    id: string; label: string; icon: string;
    endpoint: string;
    columns: { key: string; label: string; type?: 'currency' | 'date' | 'badge' | 'text' }[];
    filters: ReportFilter[];
    summaryFn?: (rows: Record<string, unknown>[]) => { label: string; value: string }[];
}

const availableReports: ReportDefinition[] = [
    {
        id: 'finance-balance-sheet',
        label: 'Balance Sheet',
        icon: '📋',
        endpoint: '/finance/reports/balance-sheet',
        filters: [],
        columns: [
            { key: 'account_type', label: 'Type' },
            { key: 'account_name', label: 'Account' },
            { key: 'balance', label: 'Balance', type: 'currency' },
        ],
    },
    {
        id: 'finance-income',
        label: 'Income Statement',
        icon: '📈',
        endpoint: '/finance/reports/income',
        filters: [],
        columns: [
            { key: 'category', label: 'Category' },
            { key: 'account_name', label: 'Account' },
            { key: 'amount', label: 'Amount', type: 'currency' },
        ],
        summaryFn: (rows) => {
            const revenue = rows.filter(r => r['category'] === 'Revenue').reduce((s, r) => s + Number(r['amount'] ?? 0), 0);
            const expenses = rows.filter(r => r['category'] === 'Expense').reduce((s, r) => s + Number(r['amount'] ?? 0), 0);
            return [
                { label: 'Total Revenue', value: formatCurrency(revenue) },
                { label: 'Total Expenses', value: formatCurrency(expenses) },
                { label: 'Net Income', value: formatCurrency(revenue - expenses) },
            ];
        },
    },
    {
        id: 'sales-report',
        label: 'Sales Summary',
        icon: '🛒',
        endpoint: '/sales/reports/sales',
        filters: [
            { key: 'status', label: 'Order Status', type: 'select', options: [
                { value: 'confirmed', label: 'Confirmed' },
                { value: 'delivered', label: 'Delivered' },
                { value: 'cancelled', label: 'Cancelled' },
            ]},
        ],
        columns: [
            { key: 'order_number', label: 'Order #' },
            { key: 'customer_name', label: 'Customer' },
            { key: 'order_date', label: 'Date', type: 'date' },
            { key: 'total_amount', label: 'Total', type: 'currency' },
            { key: 'status', label: 'Status', type: 'badge' },
        ],
        summaryFn: (rows) => {
            const total = rows.reduce((s, r) => s + Number(r['total_amount'] ?? 0), 0);
            return [
                { label: 'Total Orders', value: String(rows.length) },
                { label: 'Total Revenue', value: formatCurrency(total) },
                { label: 'Avg Order Value', value: rows.length ? formatCurrency(total / rows.length) : '$0' },
            ];
        },
    },
    {
        id: 'customer-report',
        label: 'Customer Analysis',
        icon: '👥',
        endpoint: '/sales/reports/customer',
        filters: [],
        columns: [
            { key: 'customer_name', label: 'Customer' },
            { key: 'total_orders', label: 'Orders' },
            { key: 'total_revenue', label: 'Revenue', type: 'currency' },
            { key: 'last_order_date', label: 'Last Order', type: 'date' },
        ],
    },
    {
        id: 'procurement-purchases',
        label: 'Purchase Orders',
        icon: '📦',
        endpoint: '/procurement/reports/purchases',
        filters: [
            { key: 'status', label: 'Status', type: 'select', options: [
                { value: 'sent', label: 'Sent' },
                { value: 'confirmed', label: 'Confirmed' },
                { value: 'received', label: 'Received' },
            ]},
        ],
        columns: [
            { key: 'po_number', label: 'PO Number' },
            { key: 'vendor_name', label: 'Vendor' },
            { key: 'order_date', label: 'Date', type: 'date' },
            { key: 'total_amount', label: 'Total', type: 'currency' },
            { key: 'status', label: 'Status', type: 'badge' },
        ],
        summaryFn: (rows) => {
            const total = rows.reduce((s, r) => s + Number(r['total_amount'] ?? 0), 0);
            return [
                { label: 'Total POs', value: String(rows.length) },
                { label: 'Total Spend', value: formatCurrency(total) },
            ];
        },
    },
    {
        id: 'projects-report',
        label: 'Project Status',
        icon: '🗂️',
        endpoint: '/projects/reports/projects',
        filters: [
            { key: 'status', label: 'Status', type: 'select', options: [
                { value: 'planning', label: 'Planning' },
                { value: 'active', label: 'Active' },
                { value: 'on_hold', label: 'On Hold' },
                { value: 'completed', label: 'Completed' },
            ]},
        ],
        columns: [
            { key: 'code', label: 'Code' },
            { key: 'name', label: 'Project Name' },
            { key: 'status', label: 'Status', type: 'badge' },
            { key: 'budget', label: 'Budget', type: 'currency' },
            { key: 'actual_cost', label: 'Actual Cost', type: 'currency' },
            { key: 'start_date', label: 'Start', type: 'date' },
        ],
    },
];

const datePresets = [
    { label: 'This Month',  start: () => startOfMonth(new Date()), end: () => today() },
    { label: 'Last Month',  start: () => startOfMonth(subtractMonths(new Date(), 1)), end: () => endOfMonth(subtractMonths(new Date(), 1)) },
    { label: 'This Quarter', start: () => startOfQuarter(new Date()), end: () => today() },
    { label: 'This Year',   start: () => `${new Date().getFullYear()}-01-01`, end: () => today() },
    { label: 'Last Year',   start: () => `${new Date().getFullYear() - 1}-01-01`, end: () => `${new Date().getFullYear() - 1}-12-31` },
    { label: 'Last 30 Days', start: () => subtractDays(new Date(), 30), end: () => today() },
];

// State
const selectedReport = ref<ReportDefinition | null>(null);
const reportData = ref<Record<string, unknown>[]>([]);
const loading = ref(false);
const error = ref('');
const generated = ref(false);
const sort = reactive({ key: '', asc: true });
const filters = reactive({
    startDate: `${new Date().getFullYear()}-01-01`,
    endDate: today(),
    extra: {} as Record<string, string>,
});

const reportColumns = computed(() => selectedReport.value?.columns ?? []);

const summaryCards = computed(() => {
    if (!selectedReport.value?.summaryFn || !reportData.value.length) return [];
    return selectedReport.value.summaryFn(reportData.value);
});

const sortedData = computed(() => {
    if (!sort.key) return reportData.value;
    return [...reportData.value].sort((a, b) => {
        const av = a[sort.key], bv = b[sort.key];
        const cmp = String(av ?? '').localeCompare(String(bv ?? ''), undefined, { numeric: true });
        return sort.asc ? cmp : -cmp;
    });
});

function selectReport(report: ReportDefinition) {
    selectedReport.value = report;
    reportData.value = [];
    generated.value = false;
    error.value = '';
    filters.extra = {};
    sort.key = '';
}

async function generateReport() {
    if (!selectedReport.value) return;
    loading.value = true;
    error.value = '';
    generated.value = false;

    try {
        const params: Record<string, string> = {
            start_date: filters.startDate,
            end_date: filters.endDate,
            ...Object.fromEntries(Object.entries(filters.extra).filter(([, v]) => v !== '')),
        };
        const res = await apiClient.get(selectedReport.value.endpoint, { params });
        const raw = res.data?.data ?? res.data ?? [];
        reportData.value = Array.isArray(raw) ? raw : [raw];
        generated.value = true;
    } catch (e: unknown) {
        const msg = (e as { response?: { data?: { message?: string } } })?.response?.data?.message;
        error.value = msg ?? 'Failed to generate report. The report endpoint may not be implemented yet.';
        reportData.value = [];
    } finally {
        loading.value = false;
    }
}

function sortBy(key: string) {
    if (sort.key === key) { sort.asc = !sort.asc; } else { sort.key = key; sort.asc = true; }
}

function exportCsv() {
    if (!reportData.value.length) return;
    const cols = reportColumns.value;
    const header = cols.map(c => c.label).join(',');
    const rows = sortedData.value.map(row =>
        cols.map(c => JSON.stringify(row[c.key] ?? '')).join(',')
    );
    const csv = [header, ...rows].join('\n');
    const blob = new Blob([csv], { type: 'text/csv' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = `${selectedReport.value?.id ?? 'report'}-${filters.startDate}-${filters.endDate}.csv`;
    a.click();
    URL.revokeObjectURL(url);
}

function applyPreset(preset: { label: string; start: () => string; end: () => string }) {
    filters.startDate = preset.start();
    filters.endDate = preset.end();
}

function badgeClass(value: unknown): string {
    const v = String(value ?? '').toLowerCase();
    const green = ['active', 'completed', 'delivered', 'paid', 'received', 'confirmed', 'done', 'pass'];
    const red   = ['cancelled', 'terminated', 'overdue', 'disposed', 'fail', 'closed'];
    const yellow = ['pending', 'draft', 'on_hold', 'in_progress', 'planning', 'sent', 'investigating'];
    if (green.some(s => v.includes(s))) return 'bg-green-100 dark:bg-green-900 text-green-700 dark:text-green-300';
    if (red.some(s => v.includes(s)))   return 'bg-red-100 dark:bg-red-900 text-red-700 dark:text-red-300';
    if (yellow.some(s => v.includes(s))) return 'bg-yellow-100 dark:bg-yellow-900 text-yellow-700 dark:text-yellow-300';
    return 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300';
}

// Date helpers
function today(): string { return new Date().toISOString().slice(0, 10); }
function startOfMonth(d: Date): string { return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-01`; }
function endOfMonth(d: Date): string { const e = new Date(d.getFullYear(), d.getMonth() + 1, 0); return e.toISOString().slice(0, 10); }
function subtractMonths(d: Date, n: number): Date { const r = new Date(d); r.setMonth(r.getMonth() - n); return r; }
function subtractDays(d: Date, n: number): string { const r = new Date(d); r.setDate(r.getDate() - n); return r.toISOString().slice(0, 10); }
function startOfQuarter(d: Date): string { const q = Math.floor(d.getMonth() / 3); return `${d.getFullYear()}-${String(q * 3 + 1).padStart(2, '0')}-01`; }
function formatDate(v: unknown): string { if (!v) return '—'; try { return new Date(String(v)).toLocaleDateString(); } catch { return String(v); } }
function formatCurrency(v: unknown): string {
    const n = Number(v ?? 0);
    return isNaN(n) ? '—' : new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD', minimumFractionDigits: 0 }).format(n);
}
</script>
