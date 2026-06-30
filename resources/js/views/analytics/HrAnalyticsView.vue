<template>
    <div>
        <div class="flex items-center justify-between mb-6">
            <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">HR Analytics</h1>
        </div>

        <div v-if="loading" class="flex items-center justify-center py-12">
            <div class="w-8 h-8 border-2 border-blue-500 border-t-transparent rounded-full animate-spin" />
        </div>

        <template v-else>
            <div class="grid grid-cols-3 gap-4 mb-6">
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
                    <div class="text-xs text-gray-500 dark:text-gray-400 mb-1">Total Headcount</div>
                    <div class="text-xl font-bold text-gray-900 dark:text-gray-100">{{ totalHeadcount.toLocaleString() }}</div>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
                    <div class="text-xs text-gray-500 dark:text-gray-400 mb-1">Avg Attendance Rate</div>
                    <div class="text-xl font-bold text-green-600">{{ avgAttendance }}%</div>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
                    <div class="text-xs text-gray-500 dark:text-gray-400 mb-1">Turnover Rate (12mo)</div>
                    <div class="text-xl font-bold" :class="data.turnover_rate > 15 ? 'text-red-600' : data.turnover_rate > 10 ? 'text-yellow-600' : 'text-green-600'">{{ data.turnover_rate }}%</div>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-6 mb-6">
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
                    <h3 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">Headcount by Department</h3>
                    <div class="space-y-3">
                        <div v-for="d in data.headcount_by_department" :key="d.department_id ?? 'none'">
                            <div class="flex items-center justify-between text-sm mb-1">
                                <span class="text-gray-900 dark:text-gray-100">{{ d.department_name || 'Unassigned' }}</span>
                                <span class="text-gray-500">{{ d.headcount }}</span>
                            </div>
                            <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-3 overflow-hidden">
                                <div class="bg-blue-500 h-full rounded-full" :style="{ width: barWidth(d.headcount, maxHeadcount) + '%' }"></div>
                            </div>
                            <div v-if="d.avg_salary" class="text-xs text-gray-500 mt-0.5">Avg salary: ${{ Number(d.avg_salary).toLocaleString() }}</div>
                        </div>
                        <div v-if="!data.headcount_by_department?.length" class="text-sm text-gray-500 text-center py-4">No department data</div>
                    </div>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
                    <h3 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">Attendance Rate (Last 30 Days)</h3>
                    <div class="space-y-2">
                        <div v-for="d in recentAttendance" :key="d.date" class="flex items-center gap-3">
                            <span class="text-xs text-gray-500 w-20">{{ d.date }}</span>
                            <div class="flex-1 bg-gray-200 dark:bg-gray-700 rounded-full h-4 overflow-hidden">
                                <div class="h-full rounded-full" :class="d.rate >= 90 ? 'bg-green-500' : d.rate >= 75 ? 'bg-yellow-500' : 'bg-red-500'" :style="{ width: d.rate + '%' }"></div>
                            </div>
                            <span class="text-xs font-medium text-gray-700 dark:text-gray-300 w-12 text-right">{{ d.rate }}%</span>
                        </div>
                        <div v-if="!data.attendance_rate?.length" class="text-sm text-gray-500 text-center py-4">No attendance data</div>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-6 mb-6">
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
                    <h3 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">Leave Usage by Type</h3>
                    <div class="space-y-3">
                        <div v-for="l in data.leave_usage" :key="l.leave_type">
                            <div class="flex items-center justify-between text-sm mb-1">
                                <span class="text-gray-900 dark:text-gray-100 capitalize">{{ l.leave_type }}</span>
                                <span class="text-gray-500">{{ Number(l.total_days).toFixed(0) }} days ({{ l.request_count }} requests)</span>
                            </div>
                            <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-3 overflow-hidden">
                                <div class="bg-orange-500 h-full rounded-full" :style="{ width: barWidth(l.total_days, maxLeaveDays) + '%' }"></div>
                            </div>
                        </div>
                        <div v-if="!data.leave_usage?.length" class="text-sm text-gray-500 text-center py-4">No leave data</div>
                    </div>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
                    <h3 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">Attendance Summary</h3>
                    <div class="space-y-4">
                        <div v-for="d in attendanceSummary" :key="d.label">
                            <div class="flex items-center justify-between text-sm">
                                <span class="text-gray-500">{{ d.label }}</span>
                                <span class="font-medium text-gray-900 dark:text-gray-100">{{ d.value }}</span>
                            </div>
                        </div>
                        <div v-if="!attendanceSummary.length" class="text-sm text-gray-500 text-center py-4">No attendance data</div>
                    </div>
                </div>
            </div>
        </template>
    </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import apiClient from '@/api/axios';

const loading = ref(true);
const data = ref<any>({
    headcount_by_department: [],
    attendance_rate: [],
    leave_usage: [],
    turnover_rate: 0,
});

const totalHeadcount = computed(() => data.value.headcount_by_department.reduce((sum: number, d: any) => sum + d.headcount, 0));
const maxHeadcount = computed(() => Math.max(1, ...data.value.headcount_by_department.map((d: any) => d.headcount)));
const maxLeaveDays = computed(() => Math.max(1, ...data.value.leave_usage.map((l: any) => Number(l.total_days))));

const recentAttendance = computed(() => (data.value.attendance_rate ?? []).slice(-15));
const avgAttendance = computed(() => {
    const rates = data.value.attendance_rate ?? [];
    if (!rates.length) return 0;
    return Math.round(rates.reduce((sum: number, d: any) => sum + d.rate, 0) / rates.length);
});

const attendanceSummary = computed(() => {
    const rates = data.value.attendance_rate ?? [];
    if (!rates.length) return [];
    const totalPresent = rates.reduce((sum: number, d: any) => sum + d.present, 0);
    const totalAbsent = rates.reduce((sum: number, d: any) => sum + d.absent, 0);
    const totalLate = rates.reduce((sum: number, d: any) => sum + d.late, 0);
    return [
        { label: 'Total Present Days', value: totalPresent.toLocaleString() },
        { label: 'Total Absent Days', value: totalAbsent.toLocaleString() },
        { label: 'Total Late Days', value: totalLate.toLocaleString() },
        { label: 'Days Tracked', value: rates.length.toLocaleString() },
    ];
});

function barWidth(value: number, max: number): number {
    return max > 0 ? Math.max(2, (value / max) * 100) : 0;
}

async function loadData() {
    loading.value = true;
    try {
        const res = await apiClient.get('/analytics/hr');
        data.value = res.data?.data ?? data.value;
    } catch { /* ignore */ }
    loading.value = false;
}

onMounted(loadData);
</script>
