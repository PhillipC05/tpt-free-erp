<template>
    <div v-if="profile">
        <div class="flex items-center gap-4 mb-6">
            <button @click="router.back()" class="text-gray-500 hover:text-gray-700 dark:hover:text-gray-300">&larr; Back</button>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">
                {{ profile.employee.first_name }} {{ profile.employee.last_name }}
            </h1>
            <span :class="statusClass(profile.employee.status)" class="text-xs px-2 py-1 rounded-full font-medium">
                {{ profile.employee.status?.replace('_', ' ') }}
            </span>
        </div>

        <div class="grid grid-cols-4 gap-4 mb-6">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
                <div class="text-xs text-gray-500 dark:text-gray-400 mb-1">Tenure</div>
                <div class="text-xl font-bold text-gray-900 dark:text-gray-100">{{ profile.stats.tenure_years }} years</div>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
                <div class="text-xs text-gray-500 dark:text-gray-400 mb-1">Attendance Rate</div>
                <div class="text-xl font-bold" :class="profile.stats.attendance_rate >= 90 ? 'text-green-600' : 'text-orange-600'">{{ profile.stats.attendance_rate }}%</div>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
                <div class="text-xs text-gray-500 dark:text-gray-400 mb-1">Leave Used</div>
                <div class="text-xl font-bold text-blue-600">{{ profile.stats.total_leave_days }} days</div>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
                <div class="text-xs text-gray-500 dark:text-gray-400 mb-1">Direct Reports</div>
                <div class="text-xl font-bold text-gray-900 dark:text-gray-100">{{ profile.stats.subordinates_count }}</div>
            </div>
        </div>

        <div class="grid grid-cols-3 gap-6 mb-6">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-5 col-span-2">
                <h3 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-4">Employee Details</h3>
                <div class="grid grid-cols-2 gap-4 text-sm">
                    <div><span class="text-gray-500">Code:</span> <span class="text-gray-900 dark:text-gray-100 ml-1">{{ profile.employee.employee_code }}</span></div>
                    <div><span class="text-gray-500">Email:</span> <span class="text-gray-900 dark:text-gray-100 ml-1">{{ profile.employee.email }}</span></div>
                    <div><span class="text-gray-500">Phone:</span> <span class="text-gray-900 dark:text-gray-100 ml-1">{{ profile.employee.phone ?? '—' }}</span></div>
                    <div><span class="text-gray-500">Position:</span> <span class="text-gray-900 dark:text-gray-100 ml-1">{{ profile.employee.position ?? '—' }}</span></div>
                    <div><span class="text-gray-500">Department:</span> <span class="text-gray-900 dark:text-gray-100 ml-1">{{ profile.employee.department?.name ?? '—' }}</span></div>
                    <div><span class="text-gray-500">Manager:</span> <span class="text-gray-900 dark:text-gray-100 ml-1">{{ profile.employee.manager ? profile.employee.manager.first_name + ' ' + profile.employee.manager.last_name : '—' }}</span></div>
                    <div><span class="text-gray-500">Type:</span> <span class="text-gray-900 dark:text-gray-100 ml-1 capitalize">{{ profile.employee.employment_type?.replace('_', ' ') }}</span></div>
                    <div><span class="text-gray-500">Hire Date:</span> <span class="text-gray-900 dark:text-gray-100 ml-1">{{ profile.employee.hire_date }}</span></div>
                    <div><span class="text-gray-500">Salary:</span> <span class="text-gray-900 dark:text-gray-100 ml-1">{{ profile.employee.salary ? '$' + Number(profile.employee.salary).toLocaleString() : '—' }}</span></div>
                    <div><span class="text-gray-500">Address:</span> <span class="text-gray-900 dark:text-gray-100 ml-1">{{ profile.employee.address ?? '—' }}</span></div>
                </div>
                <div v-if="profile.employee.emergency_contact" class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
                    <div class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Emergency Contact</div>
                    <div class="grid grid-cols-2 gap-4 text-sm">
                        <div><span class="text-gray-500">Name:</span> <span class="text-gray-900 dark:text-gray-100 ml-1">{{ profile.employee.emergency_contact }}</span></div>
                        <div><span class="text-gray-500">Phone:</span> <span class="text-gray-900 dark:text-gray-100 ml-1">{{ profile.employee.emergency_phone }}</span></div>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-5">
                <h3 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-4">Recent Attendance</h3>
                <div class="space-y-2">
                    <div v-for="a in profile.employee.attendance ?? []" :key="a.id" class="flex items-center justify-between text-sm">
                        <span class="text-gray-500">{{ a.date }}</span>
                        <span :class="attendanceStatusClass(a.status)" class="text-xs capitalize">{{ a.status }}</span>
                    </div>
                    <div v-if="!profile.employee.attendance?.length" class="text-sm text-gray-500">No recent records</div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-2 gap-6">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-5">
                <h3 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-4">Recent Leave Requests</h3>
                <div class="space-y-3">
                    <div v-for="l in profile.employee.leave_requests ?? []" :key="l.id" class="flex items-center justify-between py-2 border-b border-gray-100 dark:border-gray-700 last:border-0">
                        <div>
                            <span class="text-sm text-gray-900 dark:text-gray-100 capitalize">{{ l.leave_type }}</span>
                            <span class="text-xs text-gray-500 ml-2">{{ l.start_date }} — {{ l.end_date }}</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="text-xs text-gray-500">{{ l.total_days }}d</span>
                            <span :class="leaveStatusClass(l.status)" class="text-xs px-2 py-0.5 rounded-full">{{ l.status }}</span>
                        </div>
                    </div>
                    <div v-if="!profile.employee.leave_requests?.length" class="text-sm text-gray-500">No leave requests</div>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-5">
                <h3 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-4">Direct Reports</h3>
                <div class="space-y-2">
                    <div v-for="s in profile.employee.subordinates ?? []" :key="s.id" class="flex items-center justify-between py-2 border-b border-gray-100 dark:border-gray-700 last:border-0">
                        <div>
                            <span class="text-sm text-gray-900 dark:text-gray-100">{{ s.first_name }} {{ s.last_name }}</span>
                            <span class="text-xs text-gray-500 ml-2">{{ s.position ?? 'No position' }}</span>
                        </div>
                        <button @click="viewProfile(s.id)" class="text-xs text-blue-600 hover:text-blue-800">View</button>
                    </div>
                    <div v-if="!profile.employee.subordinates?.length" class="text-sm text-gray-500">No direct reports</div>
                </div>
            </div>
        </div>
    </div>

    <div v-else-if="loading" class="flex items-center justify-center py-20">
        <div class="text-gray-500">Loading employee profile...</div>
    </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import apiClient from '@/api/axios';
import { useNotificationStore } from '@/stores/notification';

const router = useRouter();
const route = useRoute();
const notify = useNotificationStore();
const profile = ref<any>(null);
const loading = ref(true);

function statusClass(status: string): string {
    const map: Record<string, string> = {
        active: 'bg-green-100 text-green-700',
        on_leave: 'bg-yellow-100 text-yellow-700',
        terminated: 'bg-red-100 text-red-700',
    };
    return map[status] ?? 'bg-gray-100 text-gray-500';
}

function attendanceStatusClass(status: string): string {
    const map: Record<string, string> = {
        present: 'text-green-600',
        absent: 'text-red-600',
        late: 'text-orange-600',
        half_day: 'text-yellow-600',
    };
    return map[status] ?? 'text-gray-500';
}

function leaveStatusClass(status: string): string {
    const map: Record<string, string> = {
        pending: 'bg-yellow-100 text-yellow-700',
        approved: 'bg-green-100 text-green-700',
        rejected: 'bg-red-100 text-red-700',
        cancelled: 'bg-gray-100 text-gray-500',
    };
    return map[status] ?? 'bg-gray-100 text-gray-500';
}

function viewProfile(id: number) {
    router.push({ name: 'hr.employee-profile', params: { id } });
}

async function loadProfile() {
    try {
        const res = await apiClient.get(`/hr/employees/${route.params.id}/profile`);
        profile.value = res.data?.data ?? null;
    } catch {
        notify.error('Failed to load employee profile');
        router.back();
    } finally {
        loading.value = false;
    }
}

onMounted(loadProfile);
</script>
