<template>
    <div>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100 mb-6">HR Overview</h1>

        <div v-if="dashboard" class="space-y-6">
            <div class="grid grid-cols-5 gap-4">
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
                    <div class="text-xs text-gray-500 dark:text-gray-400 mb-1">Active Employees</div>
                    <div class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ dashboard.summary.active_employees }}</div>
                    <div class="text-xs text-gray-500 mt-1">of {{ dashboard.summary.total_employees }} total</div>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
                    <div class="text-xs text-gray-500 dark:text-gray-400 mb-1">New This Month</div>
                    <div class="text-2xl font-bold text-green-600">{{ dashboard.summary.new_this_month }}</div>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
                    <div class="text-xs text-gray-500 dark:text-gray-400 mb-1">Turnover Rate</div>
                    <div class="text-2xl font-bold" :class="dashboard.summary.turnover_rate > 5 ? 'text-red-600' : 'text-green-600'">{{ dashboard.summary.turnover_rate }}%</div>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
                    <div class="text-xs text-gray-500 dark:text-gray-400 mb-1">Attendance Today</div>
                    <div class="text-2xl font-bold text-blue-600">{{ dashboard.attendance.attendance_rate }}%</div>
                    <div class="text-xs text-gray-500 mt-1">{{ dashboard.attendance.present_today }} present</div>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
                    <div class="text-xs text-gray-500 dark:text-gray-400 mb-1">Pending Leave</div>
                    <div class="text-2xl font-bold text-yellow-600">{{ dashboard.leave.pending_requests }}</div>
                    <div class="text-xs text-gray-500 mt-1">{{ dashboard.leave.on_leave_today }} on leave today</div>
                </div>
            </div>

            <div class="grid grid-cols-3 gap-4">
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
                    <div class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">Absent Today</div>
                    <div class="text-lg font-bold text-red-600">{{ dashboard.attendance.absent_today }}</div>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
                    <div class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">Late Today</div>
                    <div class="text-lg font-bold text-orange-600">{{ dashboard.attendance.late_today }}</div>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
                    <div class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">Terminated (Month)</div>
                    <div class="text-lg font-bold text-red-600">{{ dashboard.summary.terminated_this_month }}</div>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-6">
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
                    <h3 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">Department Headcount</h3>
                    <div class="space-y-2">
                        <div v-for="d in dashboard.department_breakdown" :key="d.id" class="flex items-center gap-3">
                            <span class="text-xs text-gray-500 w-32 truncate">{{ d.name }}</span>
                            <div class="flex-1 bg-gray-200 dark:bg-gray-700 rounded-full h-4 overflow-hidden">
                                <div class="bg-blue-500 h-full rounded-full" :style="{ width: barWidth(d.employee_count, maxDeptCount) + '%' }"></div>
                            </div>
                            <span class="text-xs font-medium text-gray-700 dark:text-gray-300 w-8 text-right">{{ d.employee_count }}</span>
                        </div>
                    </div>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
                    <h3 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">Employment Types</h3>
                    <div class="space-y-3">
                        <div v-for="e in dashboard.employment_type_breakdown" :key="e.employment_type" class="flex items-center justify-between">
                            <span class="text-sm text-gray-700 dark:text-gray-300 capitalize">{{ e.employment_type?.replace('_', ' ') }}</span>
                            <span class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ e.count }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-6">
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
                    <h3 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">Monthly Hires</h3>
                    <div class="space-y-2">
                        <div v-for="h in dashboard.monthly_hires" :key="h.month" class="flex items-center gap-3">
                            <span class="text-xs text-gray-500 w-14">{{ h.month }}</span>
                            <div class="flex-1 bg-gray-200 dark:bg-gray-700 rounded-full h-4 overflow-hidden">
                                <div class="bg-green-500 h-full rounded-full" :style="{ width: barWidth(h.count, maxHires) + '%' }"></div>
                            </div>
                            <span class="text-xs font-medium text-gray-700 dark:text-gray-300 w-8 text-right">{{ h.count }}</span>
                        </div>
                    </div>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
                    <h3 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">Leave by Type (6mo)</h3>
                    <div class="space-y-3">
                        <div v-for="l in dashboard.leave_by_type" :key="l.leave_type" class="flex items-center justify-between">
                            <span class="text-sm text-gray-700 dark:text-gray-300 capitalize">{{ l.leave_type }}</span>
                            <div class="flex items-center gap-3">
                                <span class="text-xs text-gray-500">{{ l.total_days }} days</span>
                                <span class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ l.count }}x</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div v-if="dashboard.recent_leave_requests?.length" class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
                <h3 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">Pending Leave Requests</h3>
                <div class="space-y-2">
                    <div v-for="r in dashboard.recent_leave_requests" :key="r.id" class="flex items-center justify-between py-2 border-b border-gray-100 dark:border-gray-700 last:border-0">
                        <div>
                            <span class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ r.employee?.first_name }} {{ r.employee?.last_name }}</span>
                            <span class="text-xs text-gray-500 ml-2 capitalize">{{ r.leave_type }}</span>
                        </div>
                        <div class="text-right">
                            <div class="text-xs text-gray-500">{{ r.start_date }} — {{ r.end_date }}</div>
                            <div class="text-xs font-medium text-gray-700 dark:text-gray-300">{{ r.total_days }} days</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import apiClient from '@/api/axios';
import type { HRDashboard } from '@/types';

const dashboard = ref<HRDashboard | null>(null);

const maxDeptCount = computed(() => Math.max(1, ...dashboard.value?.department_breakdown?.map(d => d.employee_count) ?? [1]));
const maxHires = computed(() => Math.max(1, ...dashboard.value?.monthly_hires?.map(h => h.count) ?? [1]));

function barWidth(value: number, max: number): number {
    return max > 0 ? Math.max(2, (value / max) * 100) : 0;
}

async function loadData() {
    try {
        const res = await apiClient.get('/hr/tracking/dashboard');
        dashboard.value = res.data?.data ?? null;
    } catch {
        dashboard.value = null;
    }
}

onMounted(loadData);
</script>
