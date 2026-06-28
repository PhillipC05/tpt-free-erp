<template>
    <div v-if="profile">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">My Dashboard</h1>
                <p class="text-sm text-gray-500">Welcome back, {{ profile.employee.first_name }}</p>
            </div>
            <div class="text-right text-sm text-gray-500">
                {{ profile.employee.employee_code }} &middot; {{ profile.employee.position ?? 'No position' }}
            </div>
        </div>

        <div class="grid grid-cols-4 gap-4 mb-6">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
                <div class="text-xs text-gray-500 dark:text-gray-400 mb-1">Attendance Rate</div>
                <div class="text-2xl font-bold" :class="profile.stats.attendance_rate >= 90 ? 'text-green-600' : 'text-orange-600'">{{ profile.stats.attendance_rate }}%</div>
                <div class="text-xs text-gray-400">Last 3 months</div>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
                <div class="text-xs text-gray-500 dark:text-gray-400 mb-1">Hours Worked</div>
                <div class="text-2xl font-bold text-blue-600">{{ profile.stats.total_hours_worked }}h</div>
                <div v-if="profile.stats.total_overtime_hours > 0" class="text-xs text-orange-600">{{ profile.stats.total_overtime_hours }}h overtime</div>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
                <div class="text-xs text-gray-500 dark:text-gray-400 mb-1">Leave Used</div>
                <div class="text-2xl font-bold text-purple-600">{{ profile.stats.total_leave_days_used }}d</div>
                <div v-if="profile.stats.pending_leave_requests" class="text-xs text-yellow-600">{{ profile.stats.pending_leave_requests }} pending</div>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
                <div class="text-xs text-gray-500 dark:text-gray-400 mb-1">Tenure</div>
                <div class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ profile.stats.tenure_years }} years</div>
                <div v-if="profile.recent_payslip" class="text-xs text-green-600">Last pay: ${{ Number(profile.recent_payslip.net_salary).toLocaleString() }}</div>
            </div>
        </div>

        <div class="flex items-center gap-4 mb-4">
            <button @click="tab = 'today'" :class="tabClass('today')">Today</button>
            <button @click="tab = 'attendance'" :class="tabClass('attendance')">Attendance</button>
            <button @click="tab = 'payslips'" :class="tabClass('payslips')">Payslips</button>
            <button @click="tab = 'leave'" :class="tabClass('leave')">Leave</button>
        </div>

        <div v-if="tab === 'today'" class="grid grid-cols-2 gap-6">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-5">
                <h3 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-4">Today's Status</h3>
                <div v-if="attendanceData?.today" class="space-y-3">
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-500">Status</span>
                        <span :class="statusClass(attendanceData.today.status)" class="font-medium capitalize">{{ attendanceData.today.status ?? 'Not clocked in' }}</span>
                    </div>
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-500">Clock In</span>
                        <span class="text-gray-900 dark:text-gray-100 font-mono">{{ attendanceData.today.clocked_in ?? '—' }}</span>
                    </div>
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-500">Clock Out</span>
                        <span class="text-gray-900 dark:text-gray-100 font-mono">{{ attendanceData.today.clocked_out ?? '—' }}</span>
                    </div>
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-500">Total Hours</span>
                        <span class="text-gray-900 dark:text-gray-100">{{ attendanceData.today.total_hours ? attendanceData.today.total_hours + 'h' : '—' }}</span>
                    </div>
                    <div v-if="attendanceData.today.overtime_hours > 0" class="flex justify-between text-sm">
                        <span class="text-gray-500">Overtime</span>
                        <span class="text-orange-600 font-medium">{{ attendanceData.today.overtime_hours }}h</span>
                    </div>
                </div>
                <div v-else class="text-sm text-gray-500">No clock-in today</div>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-5">
                <h3 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-4">Team on Leave Today</h3>
                <div v-if="profile.stats.subordinates_count > 0" class="text-sm text-gray-500">{{ profile.stats.subordinates_count }} direct reports</div>
                <div v-else class="text-sm text-gray-500">No direct reports</div>
            </div>
        </div>

        <div v-if="tab === 'attendance'" class="space-y-4">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-5 mb-4">
                <h3 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">Attendance Summary</h3>
                <div class="grid grid-cols-5 gap-4 text-center">
                    <div><div class="text-lg font-bold text-green-600">{{ attendanceData?.summary.present ?? 0 }}</div><div class="text-xs text-gray-500">Present</div></div>
                    <div><div class="text-lg font-bold text-yellow-600">{{ attendanceData?.summary.late ?? 0 }}</div><div class="text-xs text-gray-500">Late</div></div>
                    <div><div class="text-lg font-bold text-red-600">{{ attendanceData?.summary.absent ?? 0 }}</div><div class="text-xs text-gray-500">Absent</div></div>
                    <div><div class="text-lg font-bold text-blue-600">{{ attendanceData?.summary.overtime_hours ?? 0 }}h</div><div class="text-xs text-gray-500">Overtime</div></div>
                    <div><div class="text-lg font-bold text-gray-900 dark:text-gray-100">{{ attendanceData?.summary.total_hours ?? 0 }}h</div><div class="text-xs text-gray-500">Total Hours</div></div>
                </div>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs text-gray-500">Date</th>
                            <th class="px-4 py-3 text-left text-xs text-gray-500">In</th>
                            <th class="px-4 py-3 text-left text-xs text-gray-500">Out</th>
                            <th class="px-4 py-3 text-right text-xs text-gray-500">Hours</th>
                            <th class="px-4 py-3 text-right text-xs text-gray-500">OT</th>
                            <th class="px-4 py-3 text-left text-xs text-gray-500">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        <tr v-for="r in attendanceData?.records ?? []" :key="r.id" class="hover:bg-gray-50 dark:hover:bg-gray-700">
                            <td class="px-4 py-3 text-gray-900 dark:text-gray-100">{{ r.date }}</td>
                            <td class="px-4 py-3 font-mono text-gray-700 dark:text-gray-300">{{ r.clock_in ?? '—' }}</td>
                            <td class="px-4 py-3 font-mono text-gray-700 dark:text-gray-300">{{ r.clock_out ?? '—' }}</td>
                            <td class="px-4 py-3 text-right text-gray-700 dark:text-gray-300">{{ r.total_hours ? r.total_hours + 'h' : '—' }}</td>
                            <td class="px-4 py-3 text-right" :class="r.overtime_hours > 0 ? 'text-orange-600 font-medium' : 'text-gray-400'">{{ r.overtime_hours > 0 ? r.overtime_hours + 'h' : '—' }}</td>
                            <td class="px-4 py-3"><span :class="statusClass(r.status)" class="text-xs px-2 py-1 rounded-full capitalize">{{ r.status }}</span></td>
                        </tr>
                        <tr v-if="!attendanceData?.records?.length">
                            <td colspan="6" class="px-4 py-8 text-center text-gray-500">No records</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div v-if="tab === 'payslips'" class="space-y-4">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-5 mb-4">
                <div class="flex justify-between items-center">
                    <h3 class="text-sm font-medium text-gray-700 dark:text-gray-300">Total Earnings (Paid)</h3>
                    <span class="text-xl font-bold text-green-600">${{ totalPaid.toLocaleString() }}</span>
                </div>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs text-gray-500">Period</th>
                            <th class="px-4 py-3 text-right text-xs text-gray-500">Basic</th>
                            <th class="px-4 py-3 text-right text-xs text-gray-500">Allowances</th>
                            <th class="px-4 py-3 text-right text-xs text-gray-500">OT</th>
                            <th class="px-4 py-3 text-right text-xs text-gray-500">Deductions</th>
                            <th class="px-4 py-3 text-right text-xs text-gray-500">Net</th>
                            <th class="px-4 py-3 text-left text-xs text-gray-500">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        <tr v-for="p in payslips" :key="p.id" class="hover:bg-gray-50 dark:hover:bg-gray-700">
                            <td class="px-4 py-3 text-gray-900 dark:text-gray-100">{{ p.period_start }} — {{ p.period_end }}</td>
                            <td class="px-4 py-3 text-right text-gray-700 dark:text-gray-300">${{ Number(p.basic_salary).toLocaleString() }}</td>
                            <td class="px-4 py-3 text-right text-gray-700 dark:text-gray-300">${{ Number(p.allowances).toLocaleString() }}</td>
                            <td class="px-4 py-3 text-right text-orange-600">{{ p.overtime > 0 ? '$' + Number(p.overtime).toLocaleString() : '—' }}</td>
                            <td class="px-4 py-3 text-right text-red-600">${{ Number(p.deductions + p.tax_amount).toLocaleString() }}</td>
                            <td class="px-4 py-3 text-right font-medium text-green-600">${{ Number(p.net_salary).toLocaleString() }}</td>
                            <td class="px-4 py-3"><span :class="p.status === 'paid' ? 'text-green-600' : 'text-yellow-600'" class="text-xs capitalize">{{ p.status }}</span></td>
                        </tr>
                        <tr v-if="!payslips.length">
                            <td colspan="7" class="px-4 py-8 text-center text-gray-500">No payslips found</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div v-if="tab === 'leave'" class="space-y-4">
            <div class="grid grid-cols-2 gap-6">
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-5">
                    <h3 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">Leave Balance</h3>
                    <div class="space-y-3">
                        <div v-for="(info, type) in leaveBalance?.balances ?? {}" :key="type" class="flex items-center justify-between">
                            <span class="text-sm text-gray-700 dark:text-gray-300 capitalize">{{ type }}</span>
                            <div class="flex items-center gap-3">
                                <span class="text-xs text-gray-500">{{ info.used }}/{{ info.entitlement }}</span>
                                <span class="text-sm font-medium" :class="info.remaining === 0 ? 'text-red-600' : 'text-green-600'">{{ info.remaining }} left</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-5">
                    <div class="flex items-center justify-between mb-3">
                        <h3 class="text-sm font-medium text-gray-700 dark:text-gray-300">Upcoming Leave</h3>
                        <button @click="showLeaveModal = true" class="text-xs px-2 py-1 bg-blue-600 text-white rounded hover:bg-blue-700">+ Request</button>
                    </div>
                    <div class="space-y-2">
                        <div v-for="l in leaveBalance?.upcoming_leave ?? []" :key="l.id" class="flex items-center justify-between py-2 border-b border-gray-100 dark:border-gray-700 last:border-0">
                            <div>
                                <span class="text-sm text-gray-900 dark:text-gray-100 capitalize">{{ l.leave_type }}</span>
                                <span class="text-xs text-gray-500 ml-2">{{ l.start_date }} — {{ l.end_date }}</span>
                            </div>
                            <span class="text-xs text-gray-500">{{ l.total_days }}d</span>
                        </div>
                        <div v-if="!leaveBalance?.upcoming_leave?.length" class="text-sm text-gray-500">No upcoming leave</div>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
                <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-sm font-medium text-gray-700 dark:text-gray-300">Recent Requests</h3>
                </div>
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs text-gray-500">Type</th>
                            <th class="px-4 py-3 text-left text-xs text-gray-500">Dates</th>
                            <th class="px-4 py-3 text-right text-xs text-gray-500">Days</th>
                            <th class="px-4 py-3 text-left text-xs text-gray-500">Status</th>
                            <th class="px-4 py-3 text-right text-xs text-gray-500"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        <tr v-for="l in leaveBalance?.recent_requests ?? []" :key="l.id" class="hover:bg-gray-50 dark:hover:bg-gray-700">
                            <td class="px-4 py-3 capitalize text-gray-900 dark:text-gray-100">{{ l.leave_type }}</td>
                            <td class="px-4 py-3 text-gray-700 dark:text-gray-300">{{ l.start_date }} — {{ l.end_date }}</td>
                            <td class="px-4 py-3 text-right text-gray-700 dark:text-gray-300">{{ l.total_days }}d</td>
                            <td class="px-4 py-3"><span :class="leaveStatusClass(l.status)" class="text-xs px-2 py-1 rounded-full capitalize">{{ l.status }}</span></td>
                            <td class="px-4 py-3 text-right">
                                <button v-if="l.status === 'pending' || l.status === 'approved'" @click="cancelLeave(l.id)" class="text-xs text-red-600 hover:text-red-800">Cancel</button>
                            </td>
                        </tr>
                        <tr v-if="!leaveBalance?.recent_requests?.length">
                            <td colspan="5" class="px-4 py-8 text-center text-gray-500">No requests</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <ModalDialog v-model="showLeaveModal" title="Request Leave">
            <form @submit.prevent="submitLeave" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Type</label>
                    <select v-model="leaveForm.leave_type" required class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 text-sm">
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
                        <input v-model="leaveForm.start_date" type="date" required class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 text-sm" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">End</label>
                        <input v-model="leaveForm.end_date" type="date" required class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 text-sm" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Days</label>
                        <input v-model.number="leaveForm.total_days" type="number" min="0.5" step="0.5" required class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 text-sm" />
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Reason</label>
                    <textarea v-model="leaveForm.reason" rows="2" class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 text-sm"></textarea>
                </div>
                <div class="flex justify-end gap-2">
                    <button type="button" @click="showLeaveModal = false" class="px-4 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-md text-gray-700 dark:text-gray-300">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white text-sm rounded-md hover:bg-blue-700">Submit</button>
                </div>
            </form>
        </ModalDialog>
    </div>

    <div v-else class="flex items-center justify-center py-20">
        <div class="text-gray-500">Loading your dashboard...</div>
    </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted } from 'vue';
import ModalDialog from '@/components/ModalDialog.vue';
import apiClient from '@/api/axios';
import { useNotificationStore } from '@/stores/notification';

const notify = useNotificationStore();
const tab = ref('today');
const profile = ref<any>(null);
const attendanceData = ref<any>(null);
const payslips = ref<any[]>([]);
const totalPaid = ref(0);
const leaveBalance = ref<any>(null);
const showLeaveModal = ref(false);
const leaveForm = reactive({ leave_type: 'annual', start_date: '', end_date: '', total_days: 5, reason: '' });

function tabClass(t: string): string {
    return tab.value === t
        ? 'px-4 py-2 text-sm rounded-md bg-blue-600 text-white'
        : 'px-4 py-2 text-sm rounded-md bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 border border-gray-300 dark:border-gray-600';
}

function statusClass(status: string): string {
    const map: Record<string, string> = { present: 'bg-green-100 text-green-700', absent: 'bg-red-100 text-red-700', late: 'bg-yellow-100 text-yellow-700', half_day: 'bg-blue-100 text-blue-700' };
    return map[status] ?? 'bg-gray-100 text-gray-500';
}

function leaveStatusClass(status: string): string {
    const map: Record<string, string> = { pending: 'bg-yellow-100 text-yellow-700', approved: 'bg-green-100 text-green-700', rejected: 'bg-red-100 text-red-700', cancelled: 'bg-gray-100 text-gray-500' };
    return map[status] ?? 'bg-gray-100 text-gray-500';
}

async function loadProfile() {
    try {
        const res = await apiClient.get('/self-service/profile');
        profile.value = res.data?.data ?? null;
    } catch { notify.error('Failed to load profile'); }
}

async function loadAttendance() {
    try {
        const res = await apiClient.get('/self-service/attendance');
        attendanceData.value = res.data?.data ?? null;
    } catch { /* ignore */ }
}

async function loadPayslips() {
    try {
        const res = await apiClient.get('/self-service/payslips');
        payslips.value = res.data?.data?.payslips ?? [];
        totalPaid.value = res.data?.data?.total_paid ?? 0;
    } catch { /* ignore */ }
}

async function loadLeaveBalance() {
    try {
        const res = await apiClient.get('/self-service/leave-balance');
        leaveBalance.value = res.data?.data ?? null;
    } catch { /* ignore */ }
}

async function submitLeave() {
    try {
        await apiClient.post('/self-service/leave', leaveForm);
        showLeaveModal.value = false;
        notify.success('Leave request submitted');
        Object.assign(leaveForm, { leave_type: 'annual', start_date: '', end_date: '', total_days: 5, reason: '' });
        await loadLeaveBalance();
    } catch { notify.error('Failed to submit request'); }
}

async function cancelLeave(id: number) {
    if (!confirm('Cancel this leave request?')) return;
    try {
        await apiClient.post(`/self-service/leave/${id}/cancel`);
        notify.success('Leave cancelled');
        await loadLeaveBalance();
    } catch { notify.error('Failed to cancel'); }
}

onMounted(async () => {
    await loadProfile();
    loadAttendance();
    loadPayslips();
    loadLeaveBalance();
});
</script>
