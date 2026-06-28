<template>
    <div>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100 mb-6">Payroll Management</h1>

        <div v-if="summaryData" class="grid grid-cols-5 gap-4 mb-6">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
                <div class="text-xs text-gray-500 dark:text-gray-400 mb-1">Total Net Salary</div>
                <div class="text-xl font-bold text-green-600">${{ summaryData.total_net_salary.toLocaleString() }}</div>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
                <div class="text-xs text-gray-500 dark:text-gray-400 mb-1">Total Tax</div>
                <div class="text-xl font-bold text-red-600">${{ summaryData.total_tax.toLocaleString() }}</div>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
                <div class="text-xs text-gray-500 dark:text-gray-400 mb-1">Total Overtime</div>
                <div class="text-xl font-bold text-orange-600">${{ summaryData.total_overtime.toLocaleString() }}</div>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
                <div class="text-xs text-gray-500 dark:text-gray-400 mb-1">Paid</div>
                <div class="text-xl font-bold text-blue-600">{{ summaryData.paid }}</div>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
                <div class="text-xs text-gray-500 dark:text-gray-400 mb-1">Pending</div>
                <div class="text-xl font-bold text-yellow-600">{{ summaryData.pending }}</div>
            </div>
        </div>

        <div class="flex items-center justify-between mb-4">
            <div class="flex items-center gap-3">
                <select v-model="filterStatus" class="px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 text-sm">
                    <option value="">All Status</option>
                    <option value="draft">Draft</option>
                    <option value="processed">Processed</option>
                    <option value="approved">Approved</option>
                    <option value="paid">Paid</option>
                </select>
            </div>
            <div class="flex items-center gap-3">
                <button @click="showBatchModal = true" class="px-4 py-2 bg-green-600 text-white text-sm rounded-md hover:bg-green-700">Batch Generate</button>
                <button @click="showCreateModal = true" class="px-4 py-2 bg-blue-600 text-white text-sm rounded-md hover:bg-blue-700">Create Payroll</button>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
            <table class="min-w-full text-sm">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs text-gray-500">Number</th>
                        <th class="px-4 py-3 text-left text-xs text-gray-500">Employee</th>
                        <th class="px-4 py-3 text-left text-xs text-gray-500">Period</th>
                        <th class="px-4 py-3 text-right text-xs text-gray-500">Basic</th>
                        <th class="px-4 py-3 text-right text-xs text-gray-500">OT</th>
                        <th class="px-4 py-3 text-right text-xs text-gray-500">Tax</th>
                        <th class="px-4 py-3 text-right text-xs text-gray-500">Net</th>
                        <th class="px-4 py-3 text-left text-xs text-gray-500">Status</th>
                        <th class="px-4 py-3 text-right text-xs text-gray-500">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    <tr v-for="p in payrolls" :key="p.id" class="hover:bg-gray-50 dark:hover:bg-gray-700">
                        <td class="px-4 py-3 text-gray-900 dark:text-gray-100 font-mono text-xs">{{ p.payroll_number }}</td>
                        <td class="px-4 py-3 text-gray-700 dark:text-gray-300">{{ p.employee?.first_name }} {{ p.employee?.last_name }}</td>
                        <td class="px-4 py-3 text-gray-700 dark:text-gray-300 text-xs">{{ p.period_start }} — {{ p.period_end }}</td>
                        <td class="px-4 py-3 text-right text-gray-700 dark:text-gray-300">${{ Number(p.basic_salary).toLocaleString() }}</td>
                        <td class="px-4 py-3 text-right" :class="p.overtime > 0 ? 'text-orange-600' : 'text-gray-400'">{{ p.overtime > 0 ? '$' + Number(p.overtime).toLocaleString() : '—' }}</td>
                        <td class="px-4 py-3 text-right text-red-600">${{ Number(p.tax_amount).toLocaleString() }}</td>
                        <td class="px-4 py-3 text-right font-medium text-green-600">${{ Number(p.net_salary).toLocaleString() }}</td>
                        <td class="px-4 py-3"><span :class="statusClass(p.status)" class="text-xs px-2 py-1 rounded-full capitalize">{{ p.status }}</span></td>
                        <td class="px-4 py-3 text-right">
                            <div class="flex gap-1 justify-end">
                                <button v-if="p.status === 'draft'" @click="processPayroll(p.id)" class="text-xs px-2 py-1 bg-blue-100 text-blue-700 rounded hover:bg-blue-200">Process</button>
                                <button v-if="p.status === 'processed'" @click="approvePayroll(p.id)" class="text-xs px-2 py-1 bg-green-100 text-green-700 rounded hover:bg-green-200">Approve</button>
                                <button v-if="p.status === 'approved'" @click="payPayroll(p.id)" class="text-xs px-2 py-1 bg-purple-100 text-purple-700 rounded hover:bg-purple-200">Mark Paid</button>
                            </div>
                        </td>
                    </tr>
                    <tr v-if="payrolls.length === 0">
                        <td colspan="9" class="px-4 py-8 text-center text-gray-500">No payroll records found</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div v-if="summaryData?.by_department?.length" class="mt-6 bg-white dark:bg-gray-800 rounded-lg shadow p-5">
            <h3 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">Cost by Department</h3>
            <div class="space-y-2">
                <div v-for="d in summaryData.by_department" :key="d.department_name ?? 'none'" class="flex items-center gap-3">
                    <span class="text-xs text-gray-500 w-32 truncate">{{ d.department_name ?? 'Unassigned' }}</span>
                    <div class="flex-1 bg-gray-200 dark:bg-gray-700 rounded-full h-4 overflow-hidden">
                        <div class="bg-blue-500 h-full rounded-full" :style="{ width: barWidth(d.total_cost, maxDeptCost) + '%' }"></div>
                    </div>
                    <span class="text-xs font-medium text-gray-700 dark:text-gray-300 w-20 text-right">${{ Number(d.total_cost).toLocaleString() }}</span>
                </div>
            </div>
        </div>

        <ModalDialog v-model="showBatchModal" title="Batch Generate Payroll">
            <form @submit.prevent="batchGenerate" class="space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Period Start</label>
                        <input v-model="batchForm.period_start" type="date" required class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 text-sm" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Period End</label>
                        <input v-model="batchForm.period_end" type="date" required class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 text-sm" />
                    </div>
                </div>
                <p class="text-xs text-gray-500">This will generate payslips for all active employees. Existing records for this period will be skipped.</p>
                <div class="flex justify-end gap-2">
                    <button type="button" @click="showBatchModal = false" class="px-4 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-md text-gray-700 dark:text-gray-300">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-green-600 text-white text-sm rounded-md hover:bg-green-700">Generate</button>
                </div>
            </form>
        </ModalDialog>
    </div>
</template>

<script setup lang="ts">
import { ref, reactive, watch, onMounted } from 'vue';
import ModalDialog from '@/components/ModalDialog.vue';
import apiClient from '@/api/axios';
import { useNotificationStore } from '@/stores/notification';

const notify = useNotificationStore();
const payrolls = ref<any[]>([]);
const summaryData = ref<any>(null);
const filterStatus = ref('');
const showBatchModal = ref(false);
const showCreateModal = ref(false);
const batchForm = reactive({
    period_start: new Date(new Date().getFullYear(), new Date().getMonth() - 1, 1).toISOString().split('T')[0],
    period_end: new Date(new Date().getFullYear(), new Date().getMonth(), 0).toISOString().split('T')[0],
});

const maxDeptCost = computed(() => Math.max(1, ...summaryData.value?.by_department?.map((d: any) => d.total_cost) ?? [1]));

import { computed } from 'vue';

function barWidth(value: number, max: number): number {
    return max > 0 ? Math.max(2, (value / max) * 100) : 0;
}

function statusClass(status: string): string {
    const map: Record<string, string> = {
        draft: 'bg-gray-100 text-gray-700',
        processed: 'bg-blue-100 text-blue-700',
        approved: 'bg-yellow-100 text-yellow-700',
        paid: 'bg-green-100 text-green-700',
        cancelled: 'bg-red-100 text-red-700',
    };
    return map[status] ?? 'bg-gray-100 text-gray-500';
}

async function loadPayrolls() {
    try {
        const params: Record<string, string> = {};
        if (filterStatus.value) params.status = filterStatus.value;
        const res = await apiClient.get('/hr/payroll', { params });
        payrolls.value = res.data?.data ?? [];
    } catch { payrolls.value = []; }
}

async function loadSummary() {
    try {
        const res = await apiClient.get('/hr/payroll/summary');
        summaryData.value = res.data?.data ?? null;
    } catch { summaryData.value = null; }
}

async function processPayroll(id: number) {
    try {
        await apiClient.post(`/hr/payroll/${id}/process`);
        notify.success('Payroll processed');
        await loadPayrolls();
        await loadSummary();
    } catch { notify.error('Failed to process'); }
}

async function approvePayroll(id: number) {
    try {
        await apiClient.post(`/hr/payroll/${id}/approve`);
        notify.success('Payroll approved');
        await loadPayrolls();
        await loadSummary();
    } catch { notify.error('Failed to approve'); }
}

async function payPayroll(id: number) {
    try {
        await apiClient.post(`/hr/payroll/${id}/mark-paid`, { payment_method: 'bank_transfer' });
        notify.success('Marked as paid');
        await loadPayrolls();
        await loadSummary();
    } catch { notify.error('Failed to mark paid'); }
}

async function batchGenerate() {
    try {
        const res = await apiClient.post('/hr/payroll/batch-generate', batchForm);
        notify.success(res.data?.message ?? 'Payrolls generated');
        showBatchModal.value = false;
        await loadPayrolls();
        await loadSummary();
    } catch { notify.error('Failed to generate'); }
}

watch(filterStatus, loadPayrolls);

onMounted(() => {
    loadPayrolls();
    loadSummary();
});
</script>
