<template>
    <div>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100 mb-6">Attendance</h1>

        <div class="grid grid-cols-5 gap-4 mb-6">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
                <div class="text-xs text-gray-500 dark:text-gray-400 mb-1">Present</div>
                <div class="text-2xl font-bold text-green-600">{{ todayStatus.present }}</div>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
                <div class="text-xs text-gray-500 dark:text-gray-400 mb-1">Late</div>
                <div class="text-2xl font-bold text-yellow-600">{{ todayStatus.late }}</div>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
                <div class="text-xs text-gray-500 dark:text-gray-400 mb-1">Absent</div>
                <div class="text-2xl font-bold text-red-600">{{ todayStatus.absent }}</div>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
                <div class="text-xs text-gray-500 dark:text-gray-400 mb-1">Total</div>
                <div class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ todayStatus.total }}</div>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
                <div class="text-xs text-gray-500 dark:text-gray-400 mb-1">Rate</div>
                <div class="text-2xl font-bold text-blue-600">{{ todayStatus.attendance_rate }}%</div>
            </div>
        </div>

        <div class="flex items-center gap-4 mb-4">
            <button @click="tab = 'daily'" :class="tab === 'daily' ? 'bg-blue-600 text-white' : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 border border-gray-300 dark:border-gray-600'" class="px-4 py-2 text-sm rounded-md">Daily Log</button>
            <button @click="tab = 'history'" :class="tab === 'history' ? 'bg-blue-600 text-white' : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 border border-gray-300 dark:border-gray-600'" class="px-4 py-2 text-sm rounded-md">History</button>
            <button @click="tab = 'summary'" :class="tab === 'summary' ? 'bg-blue-600 text-white' : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 border border-gray-300 dark:border-gray-600'" class="px-4 py-2 text-sm rounded-md">Summary</button>
        </div>

        <div v-if="tab === 'daily'" class="space-y-4">
            <div class="flex items-center gap-4">
                <input v-model="selectedDate" type="date" class="px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 text-sm" @change="loadDailyLog" />
                <button @click="markAllAbsent" class="px-3 py-2 text-sm text-orange-600 border border-orange-300 rounded-md hover:bg-orange-50">Mark Remaining Absent</button>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs text-gray-500">Employee</th>
                            <th class="px-4 py-3 text-left text-xs text-gray-500">Code</th>
                            <th class="px-4 py-3 text-left text-xs text-gray-500">Clock In</th>
                            <th class="px-4 py-3 text-left text-xs text-gray-500">Clock Out</th>
                            <th class="px-4 py-3 text-left text-xs text-gray-500">Hours</th>
                            <th class="px-4 py-3 text-left text-xs text-gray-500">Status</th>
                            <th class="px-4 py-3 text-right text-xs text-gray-500">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        <tr v-for="r in dailyRecords" :key="r.employee_id" class="hover:bg-gray-50 dark:hover:bg-gray-700">
                            <td class="px-4 py-3 text-gray-900 dark:text-gray-100">{{ r.first_name }} {{ r.last_name }}</td>
                            <td class="px-4 py-3 text-gray-500">{{ r.employee_code }}</td>
                            <td class="px-4 py-3 font-mono text-gray-700 dark:text-gray-300">{{ r.clock_in ?? '—' }}</td>
                            <td class="px-4 py-3 font-mono text-gray-700 dark:text-gray-300">{{ r.clock_out ?? '—' }}</td>
                            <td class="px-4 py-3 text-gray-700 dark:text-gray-300">{{ r.total_hours ? r.total_hours + 'h' : '—' }}</td>
                            <td class="px-4 py-3">
                                <span :class="statusClass(r.status)" class="text-xs px-2 py-1 rounded-full font-medium capitalize">{{ r.status?.replace('_', ' ') }}</span>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <div v-if="!r.clock_in" class="flex gap-1 justify-end">
                                    <button @click="clockIn(r.employee_id)" class="text-xs px-2 py-1 bg-green-100 text-green-700 rounded hover:bg-green-200">Clock In</button>
                                </div>
                                <div v-else-if="!r.clock_out" class="flex gap-1 justify-end">
                                    <button @click="clockOut(r.employee_id)" class="text-xs px-2 py-1 bg-red-100 text-red-700 rounded hover:bg-red-200">Clock Out</button>
                                </div>
                                <span v-else class="text-xs text-gray-400">Done</span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div v-if="tab === 'history'" class="space-y-4">
            <div class="flex items-center gap-4">
                <select v-model.number="filterEmployeeId" class="px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 text-sm">
                    <option :value="0">All Employees</option>
                    <option v-for="e in allEmployees" :key="e.id" :value="e.id">{{ e.first_name }} {{ e.last_name }}</option>
                </select>
            </div>

            <DataTable :columns="historyColumns" :data="filteredHistory" searchable>
                <template #cell-status="{ value }">
                    <span :class="statusClass(value as string)" class="text-xs px-2 py-1 rounded-full font-medium capitalize">{{ (value as string)?.replace('_', ' ') }}</span>
                </template>
                <template #cell-total_hours="{ value }">
                    {{ value ? value + 'h' : '—' }}
                </template>
            </DataTable>
        </div>

        <div v-if="tab === 'summary'" class="space-y-4">
            <div class="flex items-center gap-4">
                <input v-model="summaryStart" type="date" class="px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 text-sm" />
                <span class="text-gray-500">to</span>
                <input v-model="summaryEnd" type="date" class="px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 text-sm" />
                <button @click="loadSummary" class="px-4 py-2 bg-blue-600 text-white text-sm rounded-md hover:bg-blue-700">Load</button>
            </div>

            <div v-if="summaryData" class="grid grid-cols-6 gap-4 mb-4">
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-3 text-center">
                    <div class="text-xs text-gray-500">Rate</div>
                    <div class="text-lg font-bold text-blue-600">{{ summaryData.attendance_rate }}%</div>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-3 text-center">
                    <div class="text-xs text-gray-500">Present</div>
                    <div class="text-lg font-bold text-green-600">{{ summaryData.present }}</div>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-3 text-center">
                    <div class="text-xs text-gray-500">Late</div>
                    <div class="text-lg font-bold text-yellow-600">{{ summaryData.late }}</div>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-3 text-center">
                    <div class="text-xs text-gray-500">Absent</div>
                    <div class="text-lg font-bold text-red-600">{{ summaryData.absent }}</div>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-3 text-center">
                    <div class="text-xs text-gray-500">Total Hours</div>
                    <div class="text-lg font-bold text-gray-900 dark:text-gray-100">{{ summaryData.total_hours }}h</div>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-3 text-center">
                    <div class="text-xs text-gray-500">Days</div>
                    <div class="text-lg font-bold text-gray-900 dark:text-gray-100">{{ summaryData.total_days }}</div>
                </div>
            </div>

            <div v-if="summaryData?.by_employee?.length" class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs text-gray-500">Employee</th>
                            <th class="px-4 py-3 text-right text-xs text-gray-500">Days</th>
                            <th class="px-4 py-3 text-right text-xs text-gray-500">Present</th>
                            <th class="px-4 py-3 text-right text-xs text-gray-500">Late</th>
                            <th class="px-4 py-3 text-right text-xs text-gray-500">Absent</th>
                            <th class="px-4 py-3 text-right text-xs text-gray-500">Hours</th>
                            <th class="px-4 py-3 text-right text-xs text-gray-500">Rate</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        <tr v-for="e in summaryData.by_employee" :key="e.employee_id" class="hover:bg-gray-50 dark:hover:bg-gray-700">
                            <td class="px-4 py-3 text-gray-900 dark:text-gray-100">{{ e.employee?.first_name }} {{ e.employee?.last_name }}</td>
                            <td class="px-4 py-3 text-right text-gray-700 dark:text-gray-300">{{ e.days }}</td>
                            <td class="px-4 py-3 text-right text-green-600">{{ e.present_days }}</td>
                            <td class="px-4 py-3 text-right text-yellow-600">{{ e.late_days }}</td>
                            <td class="px-4 py-3 text-right text-red-600">{{ e.absent_days }}</td>
                            <td class="px-4 py-3 text-right text-gray-700 dark:text-gray-300">{{ e.hours ? Number(e.hours).toFixed(1) + 'h' : '—' }}</td>
                            <td class="px-4 py-3 text-right font-medium text-gray-900 dark:text-gray-100">{{ e.days > 0 ? Math.round((e.present_days / e.days) * 100) : 0 }}%</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import DataTable from '@/components/DataTable.vue';
import apiClient from '@/api/axios';
import type { Employee } from '@/types';
import { useNotificationStore } from '@/stores/notification';

const notify = useNotificationStore();
const tab = ref('daily');
const selectedDate = ref(new Date().toISOString().split('T')[0]);
const dailyRecords = ref<any[]>([]);
const todayStatus = ref({ present: 0, late: 0, absent: 0, total: 0, attendance_rate: 0 });
const allEmployees = ref<Employee[]>([]);
const historyRecords = ref<any[]>([]);
const filterEmployeeId = ref(0);
const summaryStart = ref(new Date(Date.now() - 30 * 86400000).toISOString().split('T')[0]);
const summaryEnd = ref(new Date().toISOString().split('T')[0]);
const summaryData = ref<any>(null);

const historyColumns = [
    { key: 'date', label: 'Date', sortable: true },
    { key: 'employee.first_name', label: 'Employee', sortable: false },
    { key: 'clock_in', label: 'In', sortable: true },
    { key: 'clock_out', label: 'Out', sortable: true },
    { key: 'total_hours', label: 'Hours', sortable: true },
    { key: 'status', label: 'Status', sortable: true },
];

const filteredHistory = computed(() => {
    if (!filterEmployeeId.value) return historyRecords.value;
    return historyRecords.value.filter(r => r.employee_id === filterEmployeeId.value);
});

function statusClass(status: string): string {
    const map: Record<string, string> = {
        present: 'bg-green-100 text-green-700',
        absent: 'bg-red-100 text-red-700',
        late: 'bg-yellow-100 text-yellow-700',
        half_day: 'bg-blue-100 text-blue-700',
        holiday: 'bg-purple-100 text-purple-700',
    };
    return map[status] ?? 'bg-gray-100 text-gray-700';
}

async function loadDailyLog() {
    try {
        const res = await apiClient.get('/hr/attendance/daily-log', { params: { date: selectedDate.value } });
        dailyRecords.value = res.data?.data?.records ?? [];
    } catch {
        dailyRecords.value = [];
    }
}

async function loadTodayStatus() {
    try {
        const res = await apiClient.get('/hr/attendance/today-status');
        todayStatus.value = res.data?.data ?? { present: 0, late: 0, absent: 0, total: 0, attendance_rate: 0 };
    } catch { /* ignore */ }
}

async function loadHistory() {
    try {
        const res = await apiClient.get('/hr/attendance');
        historyRecords.value = res.data?.data ?? [];
    } catch {
        historyRecords.value = [];
    }
}

async function loadEmployees() {
    try {
        const res = await apiClient.get('/hr/employees?per_page=100');
        allEmployees.value = res.data?.data ?? [];
    } catch { /* ignore */ }
}

async function loadSummary() {
    try {
        const res = await apiClient.get('/hr/attendance/summary', {
            params: { start_date: summaryStart.value, end_date: summaryEnd.value },
        });
        summaryData.value = res.data?.data ?? null;
    } catch {
        summaryData.value = null;
    }
}

async function clockIn(empId: number) {
    try {
        await apiClient.post('/hr/attendance/clock-in', { employee_id: empId });
        notify.success('Clocked in');
        await loadDailyLog();
        await loadTodayStatus();
    } catch {
        notify.error('Failed to clock in');
    }
}

async function clockOut(empId: number) {
    try {
        await apiClient.post('/hr/attendance/clock-out', { employee_id: empId });
        notify.success('Clocked out');
        await loadDailyLog();
        await loadTodayStatus();
    } catch {
        notify.error('Failed to clock out');
    }
}

async function markAllAbsent() {
    try {
        await apiClient.post('/hr/attendance/mark-absent', { date: selectedDate.value });
        notify.success('Absent employees marked');
        await loadDailyLog();
    } catch {
        notify.error('Failed to mark absent');
    }
}

onMounted(() => {
    loadTodayStatus();
    loadDailyLog();
    loadEmployees();
    loadHistory();
});
</script>
