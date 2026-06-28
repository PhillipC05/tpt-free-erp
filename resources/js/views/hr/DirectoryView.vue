<template>
    <div>
        <div class="flex items-center justify-between mb-6">
            <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">Employee Directory</h1>
            <div class="flex items-center gap-3">
                <input v-model="search" type="text" placeholder="Search employees..." class="px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 text-sm w-64" />
                <select v-model="filterDept" class="px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 text-sm">
                    <option :value="0">All Departments</option>
                    <option v-for="d in departments" :key="d.id" :value="d.id">{{ d.name }}</option>
                </select>
            </div>
        </div>

        <div class="flex items-center gap-4 mb-4">
            <button @click="view = 'directory'" :class="view === 'directory' ? 'bg-blue-600 text-white' : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 border border-gray-300 dark:border-gray-600'" class="px-4 py-2 text-sm rounded-md">Directory</button>
            <button @click="view = 'org'" :class="view === 'org' ? 'bg-blue-600 text-white' : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 border border-gray-300 dark:border-gray-600'" class="px-4 py-2 text-sm rounded-md">Org Chart</button>
        </div>

        <div v-if="view === 'directory'">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
                <div v-for="emp in employees" :key="emp.id" class="bg-white dark:bg-gray-800 rounded-lg shadow p-4 hover:shadow-md transition-shadow cursor-pointer" @click="selectedEmployee = emp">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-full bg-blue-100 dark:bg-blue-900 flex items-center justify-center text-blue-600 dark:text-blue-300 font-bold text-sm">
                            {{ emp.first_name?.[0] }}{{ emp.last_name?.[0] }}
                        </div>
                        <div class="min-w-0">
                            <div class="text-sm font-medium text-gray-900 dark:text-gray-100 truncate">{{ emp.first_name }} {{ emp.last_name }}</div>
                            <div class="text-xs text-gray-500 truncate">{{ emp.position ?? 'No position' }}</div>
                        </div>
                    </div>
                    <div class="mt-3 space-y-1 text-xs text-gray-500">
                        <div class="flex justify-between">
                            <span>{{ emp.department?.name ?? '—' }}</span>
                            <span>{{ emp.employee_code }}</span>
                        </div>
                        <div>{{ emp.email }}</div>
                    </div>
                </div>
            </div>
            <div v-if="employees.length === 0 && !loading" class="text-center py-12 text-gray-500">No employees found</div>
        </div>

        <div v-if="view === 'org'" class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 overflow-x-auto">
            <div v-if="orgStats" class="flex gap-6 mb-6 text-sm">
                <span class="text-gray-500">{{ orgStats.total_employees }} employees</span>
                <span class="text-gray-500">{{ orgStats.total_departments }} departments</span>
            </div>
            <div class="org-chart min-w-[800px]">
                <div v-for="root in orgChart" :key="root.id" class="org-node-wrapper">
                    <OrgNode :node="root" :depth="0" />
                </div>
            </div>
        </div>

        <ModalDialog v-model="showProfile" :title="selectedEmployee ? `${selectedEmployee.first_name} ${selectedEmployee.last_name}` : ''">
            <div v-if="selectedEmployee" class="space-y-3 text-sm">
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-12 h-12 rounded-full bg-blue-100 dark:bg-blue-900 flex items-center justify-center text-blue-600 dark:text-blue-300 font-bold">
                        {{ selectedEmployee.first_name?.[0] }}{{ selectedEmployee.last_name?.[0] }}
                    </div>
                    <div>
                        <div class="font-medium text-gray-900 dark:text-gray-100">{{ selectedEmployee.first_name }} {{ selectedEmployee.last_name }}</div>
                        <div class="text-xs text-gray-500">{{ selectedEmployee.position ?? 'No position' }}</div>
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div><span class="text-gray-500">Code:</span> <span class="text-gray-900 dark:text-gray-100 ml-1">{{ selectedEmployee.employee_code }}</span></div>
                    <div><span class="text-gray-500">Email:</span> <span class="text-gray-900 dark:text-gray-100 ml-1">{{ selectedEmployee.email }}</span></div>
                    <div><span class="text-gray-500">Department:</span> <span class="text-gray-900 dark:text-gray-100 ml-1">{{ selectedEmployee.department?.name ?? '—' }}</span></div>
                    <div><span class="text-gray-500">Type:</span> <span class="text-gray-900 dark:text-gray-100 ml-1 capitalize">{{ selectedEmployee.employment_type?.replace('_', ' ') }}</span></div>
                    <div><span class="text-gray-500">Hire Date:</span> <span class="text-gray-900 dark:text-gray-100 ml-1">{{ selectedEmployee.hire_date }}</span></div>
                    <div><span class="text-gray-500">Manager:</span> <span class="text-gray-900 dark:text-gray-100 ml-1">{{ selectedEmployee.manager ? selectedEmployee.manager.first_name + ' ' + selectedEmployee.manager.last_name : '—' }}</span></div>
                </div>
                <div class="flex justify-end pt-2">
                    <router-link :to="{ name: 'hr.employee-profile', params: { id: selectedEmployee.id } }" class="text-xs text-blue-600 hover:text-blue-800">View Full Profile &rarr;</router-link>
                </div>
            </div>
        </ModalDialog>
    </div>
</template>

<script setup lang="ts">
import { ref, watch, onMounted } from 'vue';
import ModalDialog from '@/components/ModalDialog.vue';
import OrgNode from '@/components/OrgNode.vue';
import apiClient from '@/api/axios';
import type { Employee, Department } from '@/types';

const view = ref<'directory' | 'org'>('directory');
const search = ref('');
const filterDept = ref(0);
const employees = ref<Employee[]>([]);
const departments = ref<Department[]>([]);
const orgChart = ref<any[]>([]);
const orgStats = ref<any>(null);
const selectedEmployee = ref<Employee | null>(null);
const showProfile = ref(false);
const loading = ref(false);

watch(selectedEmployee, (v) => { showProfile.value = !!v; });

async function loadDirectory() {
    loading.value = true;
    try {
        const params: Record<string, string> = { per_page: '100' };
        if (search.value) params.search = search.value;
        if (filterDept.value) params.department_id = String(filterDept.value);

        const res = await apiClient.get('/hr/directory', { params });
        employees.value = res.data?.data ?? [];
    } catch {
        employees.value = [];
    } finally {
        loading.value = false;
    }
}

async function loadOrgChart() {
    try {
        const res = await apiClient.get('/hr/directory/org-chart-full');
        orgChart.value = res.data?.data?.chart ?? [];
        orgStats.value = res.data?.data?.stats ?? null;
    } catch {
        orgChart.value = [];
    }
}

async function loadDepartments() {
    try {
        const res = await apiClient.get('/hr/departments');
        departments.value = res.data?.data ?? [];
    } catch { /* ignore */ }
}

watch([search, filterDept], () => {
    if (view.value === 'directory') loadDirectory();
});

watch(view, (v) => {
    if (v === 'org') loadOrgChart();
});

onMounted(() => {
    loadDirectory();
    loadDepartments();
});
</script>
