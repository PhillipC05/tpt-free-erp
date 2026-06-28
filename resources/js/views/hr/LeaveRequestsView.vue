<template>
    <div>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100 mb-6">Leave Management</h1>

        <div class="grid grid-cols-4 gap-4 mb-6">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
                <div class="text-xs text-gray-500 dark:text-gray-400 mb-1">Pending</div>
                <div class="text-2xl font-bold text-yellow-600">{{ pendingCount }}</div>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
                <div class="text-xs text-gray-500 dark:text-gray-400 mb-1">Approved (Month)</div>
                <div class="text-2xl font-bold text-green-600">{{ approvedCount }}</div>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
                <div class="text-xs text-gray-500 dark:text-gray-400 mb-1">Rejected</div>
                <div class="text-2xl font-bold text-red-600">{{ rejectedCount }}</div>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
                <div class="text-xs text-gray-500 dark:text-gray-400 mb-1">On Leave Today</div>
                <div class="text-2xl font-bold text-blue-600">{{ onLeaveToday }}</div>
            </div>
        </div>

        <div class="flex items-center gap-4 mb-4">
            <button @click="tab = 'requests'" :class="tab === 'requests' ? 'bg-blue-600 text-white' : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 border border-gray-300 dark:border-gray-600'" class="px-4 py-2 text-sm rounded-md">All Requests</button>
            <button @click="tab = 'pending'" :class="tab === 'pending' ? 'bg-blue-600 text-white' : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 border border-gray-300 dark:border-gray-600'" class="px-4 py-2 text-sm rounded-md">Pending Approval</button>
            <button @click="tab = 'balance'" :class="tab === 'balance' ? 'bg-blue-600 text-white' : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 border border-gray-300 dark:border-gray-600'" class="px-4 py-2 text-sm rounded-md">My Balance</button>
            <button @click="tab = 'calendar'" :class="tab === 'calendar' ? 'bg-blue-600 text-white' : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 border border-gray-300 dark:border-gray-600'" class="px-4 py-2 text-sm rounded-md">Calendar</button>
        </div>

        <div v-if="tab === 'requests'" class="space-y-4">
            <div class="flex justify-end">
                <button @click="showCreateModal = true" class="px-4 py-2 bg-blue-600 text-white text-sm rounded-md hover:bg-blue-700">Request Leave</button>
            </div>
            <DataTable :columns="requestColumns" :data="allRequests" searchable>
                <template #cell-status="{ value }">
                    <span :class="statusClass(value as string)" class="text-xs px-2 py-1 rounded-full font-medium capitalize">{{ value }}</span>
                </template>
                <template #cell-employee="{ row }">
                    {{ row.employee?.first_name }} {{ row.employee?.last_name }}
                </template>
                <template #cell-total_days="{ value }">
                    {{ value }}d
                </template>
            </DataTable>
        </div>

        <div v-if="tab === 'pending'" class="space-y-4">
            <div v-if="pendingRequests.length === 0" class="bg-white dark:bg-gray-800 rounded-lg shadow p-8 text-center text-gray-500">No pending requests</div>
            <div v-for="r in pendingRequests" :key="r.id" class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
                <div class="flex items-start justify-between">
                    <div>
                        <div class="font-medium text-gray-900 dark:text-gray-100">{{ r.employee?.first_name }} {{ r.employee?.last_name }}</div>
                        <div class="text-sm text-gray-500">{{ r.leave_type }} &middot; {{ r.total_days }} days</div>
                        <div class="text-xs text-gray-400 mt-1">{{ r.start_date }} — {{ r.end_date }}</div>
                        <div v-if="r.reason" class="text-xs text-gray-500 mt-1 italic">"{{ r.reason }}"</div>
                    </div>
                    <div class="flex gap-2">
                        <button @click="approveLeave(r.id)" class="px-3 py-1 text-xs bg-green-600 text-white rounded hover:bg-green-700">Approve</button>
                        <button @click="openRejectModal(r)" class="px-3 py-1 text-xs bg-red-600 text-white rounded hover:bg-red-700">Reject</button>
                    </div>
                </div>
            </div>
        </div>

        <div v-if="tab === 'balance'" class="space-y-4">
            <div class="flex items-center gap-4 mb-4">
                <select v-model.number="balanceEmployeeId" class="px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 text-sm">
                    <option :value="0">Select employee...</option>
                    <option v-for="e in employees" :key="e.id" :value="e.id">{{ e.first_name }} {{ e.last_name }}</option>
                </select>
            </div>

            <div v-if="balanceData" class="bg-white dark:bg-gray-800 rounded-lg shadow p-5">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="font-medium text-gray-900 dark:text-gray-100">Leave Balance — {{ balanceData.year }}</h3>
                    <div class="text-sm text-gray-500">Total: {{ balanceData.total_used }}/{{ balanceData.total_entitlement }} days used</div>
                </div>
                <div class="grid grid-cols-3 gap-4">
                    <div v-for="(info, type) in balanceData.balances" :key="type" class="border border-gray-200 dark:border-gray-700 rounded-lg p-3">
                        <div class="text-sm font-medium text-gray-900 dark:text-gray-100 capitalize mb-2">{{ type }}</div>
                        <div class="flex justify-between text-xs text-gray-500 mb-1">
                            <span>{{ info.used }} used</span>
                            <span>{{ info.remaining }} left</span>
                        </div>
                        <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                            <div class="h-2 rounded-full" :class="info.entitlement > 0 && info.used / info.entitlement > 0.8 ? 'bg-red-500' : 'bg-blue-500'" :style="{ width: (info.entitlement > 0 ? (info.used / info.entitlement) * 100 : 0) + '%' }"></div>
                        </div>
                        <div class="text-xs text-gray-400 mt-1">{{ info.entitlement }} entitlement</div>
                    </div>
                </div>
            </div>
        </div>

        <div v-if="tab === 'calendar'" class="space-y-4">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-5">
                <h3 class="font-medium text-gray-900 dark:text-gray-100 mb-4">Approved Leave — {{ currentMonth }}</h3>
                <div v-if="calendarLeaves.length === 0" class="text-sm text-gray-500">No approved leave this month</div>
                <div v-else class="space-y-2">
                    <div v-for="c in calendarLeaves" :key="c.id" class="flex items-center justify-between py-2 border-b border-gray-100 dark:border-gray-700 last:border-0">
                        <div>
                            <span class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ c.employee }}</span>
                            <span class="text-xs text-gray-500 ml-2 capitalize">{{ c.leave_type }}</span>
                        </div>
                        <div class="text-right text-xs text-gray-500">
                            {{ c.start_date }} — {{ c.end_date }}
                            <span class="text-gray-400 ml-1">({{ c.total_days }}d)</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <ModalDialog v-model="showCreateModal" title="Request Leave">
            <form @submit.prevent="createLeaveRequest" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Employee</label>
                    <select v-model.number="createForm.employee_id" required class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 text-sm">
                        <option value="">Select employee...</option>
                        <option v-for="e in employees" :key="e.id" :value="e.id">{{ e.first_name }} {{ e.last_name }}</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Leave Type</label>
                    <select v-model="createForm.leave_type" required class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 text-sm">
                        <option value="annual">Annual</option>
                        <option value="sick">Sick</option>
                        <option value="personal">Personal</option>
                        <option value="maternity">Maternity</option>
                        <option value="paternity">Paternity</option>
                        <option value="unpaid">Unpaid</option>
                        <option value="other">Other</option>
                    </select>
                </div>
                <div class="grid grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Start</label>
                        <input v-model="createForm.start_date" type="date" required class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 text-sm" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">End</label>
                        <input v-model="createForm.end_date" type="date" required class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 text-sm" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Days</label>
                        <input v-model.number="createForm.total_days" type="number" min="0.5" step="0.5" required class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 text-sm" />
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Reason</label>
                    <textarea v-model="createForm.reason" rows="2" class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 text-sm"></textarea>
                </div>
                <div class="flex justify-end gap-2">
                    <button type="button" @click="showCreateModal = false" class="px-4 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-md text-gray-700 dark:text-gray-300">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white text-sm rounded-md hover:bg-blue-700">Submit</button>
                </div>
            </form>
        </ModalDialog>

        <ModalDialog v-model="showRejectModal" title="Reject Leave Request">
            <form @submit.prevent="rejectLeave" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Reason for rejection</label>
                    <textarea v-model="rejectReason" required rows="3" class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 text-sm"></textarea>
                </div>
                <div class="flex justify-end gap-2">
                    <button type="button" @click="showRejectModal = false" class="px-4 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-md text-gray-700 dark:text-gray-300">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-red-600 text-white text-sm rounded-md hover:bg-red-700">Reject</button>
                </div>
            </form>
        </ModalDialog>
    </div>
</template>

<script setup lang="ts">
import { ref, reactive, computed, watch, onMounted } from 'vue';
import DataTable from '@/components/DataTable.vue';
import ModalDialog from '@/components/ModalDialog.vue';
import apiClient from '@/api/axios';
import type { Employee, LeaveRequest } from '@/types';
import { useNotificationStore } from '@/stores/notification';

const notify = useNotificationStore();
const tab = ref('requests');
const allRequests = ref<LeaveRequest[]>([]);
const pendingRequests = ref<LeaveRequest[]>([]);
const employees = ref<Employee[]>([]);
const balanceEmployeeId = ref(0);
const balanceData = ref<any>(null);
const calendarLeaves = ref<any[]>([]);
const showCreateModal = ref(false);
const showRejectModal = ref(false);
const rejectingId = ref<number | null>(null);
const rejectReason = ref('');

const createForm = reactive({ employee_id: '', leave_type: 'annual', start_date: '', end_date: '', total_days: 5, reason: '' });

const pendingCount = computed(() => pendingRequests.value.length);
const approvedCount = computed(() => allRequests.value.filter(r => r.status === 'approved').length);
const rejectedCount = computed(() => allRequests.value.filter(r => r.status === 'rejected').length);
const onLeaveToday = computed(() => {
    const today = new Date().toISOString().split('T')[0];
    return allRequests.value.filter(r => r.status === 'approved' && r.start_date <= today && r.end_date >= today).length;
});
const currentMonth = computed(() => new Date().toLocaleString('default', { month: 'long', year: 'numeric' }));

const requestColumns = [
    { key: 'employee', label: 'Employee', sortable: false },
    { key: 'leave_type', label: 'Type', sortable: true },
    { key: 'start_date', label: 'Start', sortable: true },
    { key: 'end_date', label: 'End', sortable: true },
    { key: 'total_days', label: 'Days', sortable: true },
    { key: 'status', label: 'Status', sortable: true },
];

function statusClass(status: string): string {
    const map: Record<string, string> = {
        pending: 'bg-yellow-100 text-yellow-700',
        approved: 'bg-green-100 text-green-700',
        rejected: 'bg-red-100 text-red-700',
        cancelled: 'bg-gray-100 text-gray-500',
    };
    return map[status] ?? 'bg-gray-100 text-gray-500';
}

async function loadAll() {
    try {
        const res = await apiClient.get('/hr/leave-requests?per_page=100');
        allRequests.value = res.data?.data ?? [];
    } catch { allRequests.value = []; }
}

async function loadPending() {
    try {
        const res = await apiClient.get('/hr/leave-requests/team-pending');
        pendingRequests.value = res.data?.data ?? [];
    } catch { pendingRequests.value = []; }
}

async function loadEmployees() {
    try {
        const res = await apiClient.get('/hr/employees?per_page=100');
        employees.value = res.data?.data ?? [];
    } catch { employees.value = []; }
}

async function loadCalendar() {
    try {
        const res = await apiClient.get('/hr/leave-requests/calendar');
        calendarLeaves.value = res.data?.data ?? [];
    } catch { calendarLeaves.value = []; }
}

async function loadBalance() {
    if (!balanceEmployeeId.value) return;
    try {
        const res = await apiClient.get(`/hr/leave-requests/balance?employee_id=${balanceEmployeeId.value}`);
        balanceData.value = res.data?.data ?? null;
    } catch { balanceData.value = null; }
}

async function createLeaveRequest() {
    try {
        await apiClient.post('/hr/leave-requests', createForm);
        showCreateModal.value = false;
        notify.success('Leave request submitted');
        Object.assign(createForm, { employee_id: '', leave_type: 'annual', start_date: '', end_date: '', total_days: 5, reason: '' });
        await loadAll();
        await loadPending();
    } catch {
        notify.error('Failed to submit leave request');
    }
}

async function approveLeave(id: number) {
    try {
        await apiClient.post(`/hr/leave-requests/${id}/approve`);
        notify.success('Leave approved');
        await loadAll();
        await loadPending();
    } catch {
        notify.error('Failed to approve');
    }
}

function openRejectModal(r: LeaveRequest) {
    rejectingId.value = r.id;
    rejectReason.value = '';
    showRejectModal.value = true;
}

async function rejectLeave() {
    if (!rejectingId.value || !rejectReason.value) return;
    try {
        await apiClient.post(`/hr/leave-requests/${rejectingId.value}/reject`, { reason: rejectReason.value });
        showRejectModal.value = false;
        notify.success('Leave rejected');
        await loadAll();
        await loadPending();
    } catch {
        notify.error('Failed to reject');
    }
}

watch(balanceEmployeeId, loadBalance);
watch(tab, (t) => {
    if (t === 'requests') loadAll();
    if (t === 'pending') loadPending();
    if (t === 'calendar') loadCalendar();
});

onMounted(() => {
    loadAll();
    loadPending();
    loadEmployees();
});
</script>
